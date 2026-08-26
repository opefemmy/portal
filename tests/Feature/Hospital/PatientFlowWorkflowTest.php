<?php

namespace Tests\Feature\Hospital;

use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaffNote;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * End-to-end regression for the 2026-08 hospital patient-flow slice.
 *
 * The flow:
 *   1. Patient (or receptionist) books an appointment. On a first
 *      visit the doctor is optional — the records officer picks an
 *      available doctor at the desk.
 *   2. Records officer certifies the chart is on file (POST
 *      /hospital/appointments/{id}/certify).
 *   3. Records officer assigns a doctor (POST .../assign-doctor). If
 *      none was pinned at booking time, the controller picks the
 *      on-call doctor with the lightest appointment load.
 *   4. Nurse records vitals (POST .../vitals).
 *   5. Doctor consults (existing flow), and may refer out (lab /
 *      pharmacy / radiology / nurse / follow-up). Each referral
 *      drops a HospitalStaffNote addressed to the next station.
 *   6. Doctor or consultant signs the patient out at end of day
 *      (POST .../sign-out), closing the visit and writing the
 *      final handover summary note.
 *
 * Each station's role must have the matching permission slug; users
 * without it get 403. The patient show page must surface staff
 * notes pinned to the top.
 */
class PatientFlowWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
        PermissionService::flush();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('hospital_staff_notes');
        Schema::dropIfExists('hospital_appointments');
        Schema::dropIfExists('hospital_patients');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::enableForeignKeyConstraints();
        PermissionService::flush();
        parent::tearDown();
    }

    public function test_full_records_officer_to_nurse_to_doctor_to_signout_flow(): void
    {
        $patient = HospitalPatient::create([
            'patient_number' => 'PAT2026001',
            'first_name'     => 'Aisha',
            'last_name'      => 'Bello',
            'gender'         => 'female',
            'date_of_birth'  => '2000-05-12',
            'phone'          => '08030000001',
            'address'        => 'Hostel B, Room 12',
            'patient_type'   => 'student',
            'registered_by'  => 1,
        ]);

        $recordsOfficer = $this->makeUser('medical_records_officer');
        $nurse          = $this->makeUser('nurse');
        $doctor         = $this->makeUser('doctor');

        // 1. Book appointment — first visit, no doctor pinned.
        $appointment = HospitalAppointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => null,
            'scheduled_by'     => $recordsOfficer->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00:00',
            'status'           => 'scheduled',
            'complaint'        => 'Fever and cough',
        ]);

        $this->assertNull($appointment->doctor_id, 'first-visit appointment should not require a doctor');
        $this->assertNull($appointment->certified_at);

        // 2. Records officer certifies chart.
        $recordsOfficer->permissions;
        $this->actingAs($recordsOfficer)
            ->post("/hospital/appointments/{$appointment->id}/certify")
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertNotNull($appointment->certified_at);
        $this->assertSame($recordsOfficer->id, $appointment->certified_by);

        // 3. Records officer assigns a doctor.
        $this->actingAs($recordsOfficer)
            ->post("/hospital/appointments/{$appointment->id}/assign-doctor", [
                'doctor_id' => $doctor->id,
            ])
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame($doctor->id, $appointment->doctor_id, 'records officer should have pinned the doctor they passed in');
        $this->assertNotNull($appointment->assigned_doctor_at);
        $this->assertSame($recordsOfficer->id, $appointment->assigned_by);

        // 4. Nurse records vitals (only allowed after certify).
        $this->actingAs($nurse)
            ->post("/hospital/appointments/{$appointment->id}/vitals")
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertNotNull($appointment->vitals_recorded_at);
        $this->assertSame($nurse->id, $appointment->vitals_recorded_by);

        // 5. Doctor signs out at end of day.
        $this->actingAs($doctor)
            ->post("/hospital/appointments/{$appointment->id}/sign-out", [
                'summary' => 'Vitals normal; malaria RDT negative; discharged home.',
            ])
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertNotNull($appointment->sign_out_at);
        $this->assertSame($doctor->id, $appointment->sign_out_by);
        $this->assertStringContainsString('malaria RDT', $appointment->sign_out_summary);
    }

    public function test_nurse_cannot_vitals_before_records_certify(): void
    {
        $patient = HospitalPatient::create([
            'patient_number' => 'PAT2026002',
            'first_name'     => 'Tunde',
            'last_name'      => 'Lawal',
            'gender'         => 'male',
            'date_of_birth'  => '1999-03-21',
            'phone'          => '08030000002',
            'address'        => 'Hostel A',
            'patient_type'   => 'student',
            'registered_by'  => 1,
        ]);

        $nurse = $this->makeUser('nurse');

        $appointment = HospitalAppointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => 99,
            'scheduled_by'     => 1,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'scheduled',
        ]);

        // The patient has no certified_at yet; the route must refuse.
        $this->actingAs($nurse)
            ->post("/hospital/appointments/{$appointment->id}/vitals")
            ->assertSessionHasErrors();
    }

    public function test_nurse_cannot_certify_chart(): void
    {
        $patient = HospitalPatient::create([
            'patient_number' => 'PAT2026003',
            'first_name'     => 'Ngozi',
            'last_name'      => 'Eze',
            'gender'         => 'female',
            'date_of_birth'  => '2001-09-09',
            'phone'          => '08030000003',
            'address'        => 'Hostel C',
            'patient_type'   => 'staff',
            'registered_by'  => 1,
        ]);

        $nurse = $this->makeUser('nurse');

        $appointment = HospitalAppointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => 99,
            'scheduled_by'     => 1,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '11:00:00',
            'status'           => 'scheduled',
        ]);

        // Nurse must NOT have appointments.certify — they get 403.
        $this->actingAs($nurse)
            ->post("/hospital/appointments/{$appointment->id}/certify")
            ->assertStatus(403);
    }

    public function test_doctor_can_drop_a_pinned_staff_note(): void
    {
        $patient = HospitalPatient::create([
            'patient_number' => 'PAT2026004',
            'first_name'     => 'Emeka',
            'last_name'      => 'Okoro',
            'gender'         => 'male',
            'date_of_birth'  => '1998-12-12',
            'phone'          => '08030000004',
            'address'        => 'Off-campus',
            'patient_type'   => 'visitor',
            'registered_by'  => 1,
        ]);

        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)
            ->post("/hospital/patients/{$patient->id}/notes", [
                'audience'  => 'nurse',
                'note_type' => 'instruction',
                'body'      => 'Re-check BP in 30 mins; patient on amlodipine.',
                'is_pinned' => true,
            ])
            ->assertSessionHas('success');

        $note = HospitalStaffNote::where('patient_id', $patient->id)->first();
        $this->assertNotNull($note);
        $this->assertSame('instruction', $note->note_type);
        $this->assertSame('nurse', $note->audience);
        $this->assertTrue($note->is_pinned);
        $this->assertSame($doctor->id, $note->author_id);
    }

    public function test_guest_cannot_reach_hospital_routes(): void
    {
        $patient = HospitalPatient::create([
            'patient_number' => 'PAT2026005',
            'first_name'     => 'Bola',
            'last_name'      => 'Ade',
            'gender'         => 'male',
            'date_of_birth'  => '2000-01-01',
            'phone'          => '08030000005',
            'address'        => 'Hostel D',
            'patient_type'   => 'student',
            'registered_by'  => 1,
        ]);

        // Guests should hit the login redirect, not get the form.
        $this->get("/hospital/patients/{$patient->id}/edit")
            ->assertRedirect();
    }

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('permissions', function ($t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('group');
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('role_permissions', function ($t) {
            $t->id();
            $t->foreignId('role_id');
            $t->foreignId('permission_id');
        });

        Schema::create('role_user', function ($t) {
            $t->id();
            $t->foreignId('role_id');
            $t->foreignId('user_id');
            $t->timestamps();
        });

        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('hospital_patients', function ($t) {
            $t->id();
            $t->string('patient_number');
            $t->string('first_name');
            $t->string('last_name');
            $t->enum('gender', ['male', 'female']);
            $t->date('date_of_birth');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('address');
            $t->string('patient_type');
            $t->foreignId('registered_by');
            $t->foreignId('user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('hospital_appointments', function ($t) {
            $t->id();
            $t->foreignId('patient_id');
            $t->foreignId('doctor_id')->nullable();
            $t->foreignId('scheduled_by');
            $t->foreignId('certified_by')->nullable();
            $t->timestamp('certified_at')->nullable();
            $t->foreignId('assigned_by')->nullable();
            $t->timestamp('assigned_doctor_at')->nullable();
            $t->foreignId('vitals_recorded_by')->nullable();
            $t->timestamp('vitals_recorded_at')->nullable();
            $t->foreignId('sign_out_by')->nullable();
            $t->timestamp('sign_out_at')->nullable();
            $t->text('sign_out_summary')->nullable();
            $t->date('appointment_date');
            $t->time('appointment_time');
            $t->string('status')->default('scheduled');
            $t->text('complaint')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('hospital_staff_notes', function ($t) {
            $t->id();
            $t->foreignId('patient_id');
            $t->foreignId('author_id');
            $t->foreignId('appointment_id')->nullable();
            $t->string('audience');
            $t->string('note_type');
            $t->text('body');
            $t->boolean('is_pinned')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('audit_logs', function ($t) {
            $t->id();
            $t->foreignId('user_id')->nullable();
            $t->string('module')->nullable();
            $t->string('action')->nullable();
            $t->text('description')->nullable();
            $t->string('entity_type')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->string('ip_address')->nullable();
            $t->string('user_agent')->nullable();
            $t->string('computer_name')->nullable();
            $t->string('status')->default('success');
            $t->text('error_message')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function seedFixtures(): void
    {
        $slugs = [
            'appointments.view', 'appointments.create', 'appointments.certify',
            'appointments.assign-doctor', 'appointments.vitals',
            'appointments.check-in', 'appointments.start',
            'patients.view', 'patients.search', 'patients.edit', 'patients.create',
            'notes.create', 'notes.pin', 'notes.delete',
            'signout.complete',
            'referrals.send.lab', 'referrals.send.pharmacy',
            'referrals.send.radiology', 'referrals.send.nurse',
            'consultations.create', 'consultations.view',
        ];
        foreach ($slugs as $slug) {
            Permission::create([
                'slug'  => $slug,
                'group' => 'hospital',
                'name'  => ucwords(str_replace(['.', '-'], ' ', $slug)),
            ]);
        }

        $matrix = [
            'medical_records_officer' => [
                'appointments.view', 'appointments.certify',
                'appointments.assign-doctor',
                'patients.view', 'patients.search', 'patients.edit',
                'notes.create', 'notes.pin', 'notes.delete',
                'signout.complete',
            ],
            'nurse' => [
                'appointments.view', 'appointments.vitals',
                'patients.view',
                'notes.create', 'notes.pin',
            ],
            'doctor' => [
                'appointments.view', 'appointments.start',
                'patients.view', 'patients.search',
                'consultations.create', 'consultations.view',
                'notes.create', 'notes.pin', 'notes.delete',
                'signout.complete',
                'referrals.send.lab', 'referrals.send.pharmacy',
                'referrals.send.radiology', 'referrals.send.nurse',
            ],
            'hospital_receptionist' => [
                'patients.create', 'patients.view',
                'appointments.view', 'appointments.create',
            ],
        ];

        foreach ($matrix as $roleSlug => $perms) {
            $role = Role::firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => ucwords(str_replace('_', ' ', $roleSlug)), 'is_active' => true],
            );
            $ids = Permission::whereIn('slug', $perms)->pluck('id')->all();
            DB::table('role_permissions')->where('role_id', $role->id)->delete();
            foreach ($ids as $pid) {
                DB::table('role_permissions')->insert([
                    'role_id' => $role->id, 'permission_id' => $pid,
                ]);
            }
        }
    }

    private function makeUser(string $roleSlug): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        return User::create([
            'name'     => $roleSlug . ' User',
            'email'    => $roleSlug . '_' . uniqid('', true) . '@test.local',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
            'is_active' => true,
        ]);
    }
}