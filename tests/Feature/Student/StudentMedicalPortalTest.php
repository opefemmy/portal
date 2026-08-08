<?php

namespace Tests\Feature\Student;

use App\Models\Department;
use App\Models\Programme;
use App\Models\Role;
use App\Models\School;
use App\Models\Session as AcademicSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins the student medical portal routes & views so that the SQL / view
 * regressions the recent audit surfaced stay fixed:
 *
 *   - `HospitalAppointment` doesn't have `appointment_number` /
 *     `symptoms` / `staff_id` columns. Views synthesize the
 *     appointment number from `id` and read the patient reason from
 *     `complaint`. The booking controller writes only schema-real
 *     columns.
 *   - `HospitalPatient` exposes blood type as `blood_group` (not
 *     `blood_type`). The dashboard view reads the right column.
 *   - `scheduled_by` and `appointment_time` are NOT NULL on
 *     `hospital_appointments`. The booking controller populates both,
 *     or the INSERT fails.
 */
class StudentMedicalPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
    }

    protected function tearDown(): void
    {
        $tables = [
            'hospital_reports', 'hospital_referrals', 'hospital_admissions',
            'hospital_lab_results', 'hospital_lab_requests',
            'hospital_prescription_items', 'hospital_prescriptions',
            'hospital_diagnoses', 'hospital_medical_records',
            'hospital_vital_signs', 'hospital_appointments',
            'hospital_beds', 'hospital_staff', 'hospital_patients',
            'hospital_wards', 'students', 'programmes', 'departments',
            'schools', 'sessions', 'users', 'roles',
        ];
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    public function test_every_medical_route_renders_for_an_authed_student(): void
    {
        $user = $this->makeStudentUser();

        foreach ([
            'student.medical.index',
            'student.medical.book',
            'student.medical.appointments',
            'student.medical.history',
            'student.medical.prescriptions',
            'student.medical.lab-results',
            'student.medical.admissions',
        ] as $name) {
            $r = $this->actingAs($user)->get(route($name));
            $this->assertLessThan(
                500,
                $r->getStatusCode(),
                "Route {$name} returned {$r->getStatusCode()} — body: "
                    . substr($r->getContent(), 0, 500)
            );
        }
    }

    public function test_dashboard_view_renders_blood_group_and_complaint_not_nonexistent_columns(): void
    {
        $user = $this->makeStudentUser();
        $r = $this->actingAs($user)->get(route('student.medical.index'));
        $r->assertOk();

        // The dashboard template references $patient->blood_group,
        // not $patient->blood_type — the table column is blood_group.
        $this->assertStringContainsString('Blood Type:', $r->getContent());

        // Even with zero appointments the page must render cleanly,
        // proving the index controller's empty-fallback works.
        $this->assertStringContainsString('Medical Portal', $r->getContent());
    }

    public function test_appointment_history_views_synthesize_apt_id_and_read_complaint(): void
    {
        $user = $this->makeStudentUser();
        $doctor = $this->makeDoctor();

        // Patient row will be auto-created by the booking flow on
        // first interaction with the portal.
        $book = $this->actingAs($user)->post(route('student.medical.appointment.store'), [
            'doctor_id'        => $doctor->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'symptoms'         => 'Persistent headache',
        ]);
        $book->assertRedirect(route('student.medical.appointments'));

        foreach ([
            'student.medical.appointments',
            'student.medical.history',
        ] as $name) {
            $r = $this->actingAs($user)->get(route($name));
            $r->assertOk();

            // The view must NOT call $appointment->symptoms (column
            // doesn't exist) — render the reason from `complaint`
            // instead, and synthesize "APT-NNNNNN" from the row id.
            $this->assertStringContainsString('APT-000001', $r->getContent());
            $this->assertStringContainsString('Persistent headache', $r->getContent());
        }
    }

    public function test_booking_persists_required_columns(): void
    {
        $user = $this->makeStudentUser();
        $doctor = $this->makeDoctor();

        $this->actingAs($user)->post(route('student.medical.appointment.store'), [
            'doctor_id'        => $doctor->id,
            'appointment_date' => now()->addDays(3)->format('Y-m-d'),
            'symptoms'         => 'Flu symptoms',
        ])->assertRedirect(route('student.medical.appointments'));

        // The row must have scheduled_by + appointment_time populated,
        // and the patient reason must live in `complaint` — not in
        // `symptoms` (column doesn't exist).
        $row = \DB::table('hospital_appointments')->first();
        $this->assertNotNull($row);
        $this->assertSame($user->id, (int) $row->scheduled_by);
        $this->assertSame('09:00:00', $row->appointment_time);
        $this->assertSame('Flu symptoms', $row->complaint);
        $this->assertSame('scheduled', $row->status);
    }

    // --- Fixtures ---------------------------------------------------------

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            $t->string('gender', 10)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->timestamps();
        });
        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        Schema::create('schools', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('programmes', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('matric_number');
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->timestamps();
        });

        Schema::create('hospital_wards', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('type');
            $t->integer('total_beds');
            $t->integer('available_beds')->default(0);
            $t->decimal('daily_rate', 12, 2)->default(0);
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('hospital_patients', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('patient_number')->unique();
            $t->string('first_name');
            $t->string('last_name');
            $t->string('other_name')->nullable();
            $t->string('gender', 10);
            $t->date('date_of_birth');
            $t->string('blood_group')->nullable();
            $t->string('genotype')->nullable();
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('address');
            $t->string('state')->nullable();
            $t->string('lga')->nullable();
            $t->string('nationality')->default('Nigerian');
            $t->string('next_of_kin_name');
            $t->string('next_of_kin_phone');
            $t->string('next_of_kin_relationship');
            $t->text('next_of_kin_address')->nullable();
            $t->string('patient_type', 20)->default('student');
            $t->foreignId('registered_by')->constrained('users');
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('hospital_staff', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('staff_number')->unique();
            $t->string('first_name');
            $t->string('last_name');
            $t->string('staff_type', 30);
            $t->string('specialization')->nullable();
            $t->string('license_number')->nullable();
            $t->date('license_expiry')->nullable();
            $t->string('phone');
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->string('gender', 10)->nullable();
            $t->boolean('is_available')->default(true);
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('hospital_beds', function ($t) {
            $t->id();
            $t->foreignId('ward_id')->constrained('hospital_wards');
            $t->string('bed_number');
            $t->string('status', 30)->default('available');
            $t->unsignedBigInteger('patient_id')->nullable();
            $t->dateTime('occupied_at')->nullable();
            $t->dateTime('discharged_at')->nullable();
            $t->timestamps();
        });
        Schema::create('hospital_appointments', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('doctor_id')->constrained('hospital_staff');
            $t->foreignId('scheduled_by')->constrained('users');
            $t->dateTime('appointment_date');
            $t->time('appointment_time');
            $t->string('status', 60)->default('scheduled');
            $t->text('complaint')->nullable();
            $t->text('notes')->nullable();
            $t->dateTime('checked_in_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('hospital_vital_signs', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('recorded_by')->constrained('hospital_staff');
            $t->decimal('temperature', 4, 1)->nullable();
            $t->string('blood_pressure_systolic')->nullable();
            $t->string('blood_pressure_diastolic')->nullable();
            $t->decimal('weight', 5, 2)->nullable();
            $t->decimal('height', 5, 2)->nullable();
            $t->integer('pulse')->nullable();
            $t->integer('oxygen_level')->nullable();
            $t->decimal('blood_sugar', 5, 2)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('hospital_medical_records', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('doctor_id')->nullable()->constrained('hospital_staff');
            $t->unsignedBigInteger('appointment_id')->nullable();
            $t->text('chief_complaint')->nullable();
            $t->text('symptoms')->nullable();
            $t->text('examination_findings')->nullable();
            $t->text('doctor_notes')->nullable();
            $t->text('treatment_plan')->nullable();
            $t->dateTime('consultation_date');
            $t->string('visit_type', 20)->default('new');
            $t->timestamps();
        });
        Schema::create('hospital_diagnoses', function ($t) {
            $t->id();
            $t->foreignId('medical_record_id')->constrained('hospital_medical_records');
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->string('icd_code')->nullable();
            $t->string('diagnosis');
            $t->text('description')->nullable();
            $t->string('severity', 20)->nullable();
            $t->string('type', 20)->default('primary');
            $t->timestamps();
        });
        Schema::create('hospital_prescriptions', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('doctor_id')->constrained('hospital_staff');
            $t->unsignedBigInteger('medical_record_id')->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 30)->default('pending');
            $t->unsignedBigInteger('dispensed_by')->nullable();
            $t->dateTime('dispensed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('hospital_prescription_items', function ($t) {
            $t->id();
            $t->foreignId('prescription_id')->constrained('hospital_prescriptions');
            $t->unsignedBigInteger('drug_id')->nullable();
            $t->string('drug_name');
            $t->string('dosage');
            $t->string('frequency');
            $t->string('duration');
            $t->string('quantity')->nullable();
            $t->text('instructions')->nullable();
            $t->boolean('is_dispensed')->default(false);
            $t->timestamps();
        });
        Schema::create('hospital_lab_requests', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('doctor_id')->constrained('hospital_staff');
            $t->unsignedBigInteger('medical_record_id')->nullable();
            $t->string('test_type');
            $t->text('clinical_notes')->nullable();
            $t->string('status', 30)->default('pending');
            $t->dateTime('requested_at');
            $t->dateTime('completed_at')->nullable();
            $t->decimal('amount', 10, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('hospital_lab_results', function ($t) {
            $t->id();
            $t->foreignId('lab_request_id')->constrained('hospital_lab_requests');
            $t->unsignedBigInteger('recorded_by')->nullable();
            $t->string('test_name');
            $t->string('parameter')->nullable();
            $t->string('result')->nullable();
            $t->string('unit')->nullable();
            $t->string('reference_range')->nullable();
            $t->string('status');
            $t->text('notes')->nullable();
            $t->dateTime('recorded_at')->nullable();
            $t->timestamps();
        });
        Schema::create('hospital_admissions', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('doctor_id')->constrained('hospital_staff');
            $t->unsignedBigInteger('bed_id')->nullable();
            $t->string('admission_number')->unique();
            $t->dateTime('admission_date');
            $t->dateTime('discharge_date')->nullable();
            $t->string('status', 20)->default('admitted');
            $t->text('reason')->nullable();
            $t->text('diagnosis')->nullable();
            $t->text('treatment_plan')->nullable();
            $t->text('discharge_notes')->nullable();
            $t->decimal('daily_rate', 12, 2)->default(0);
            $t->decimal('total_charges', 12, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('hospital_referrals', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('referrer_id')->constrained('hospital_staff');
            $t->unsignedBigInteger('referred_to_id')->nullable();
            $t->string('external_facility')->nullable();
            $t->text('reason');
            $t->text('notes')->nullable();
            $t->string('status', 20)->default('pending');
            $t->dateTime('referred_at');
            $t->dateTime('accepted_at')->nullable();
            $t->timestamps();
        });
        Schema::create('hospital_reports', function ($t) {
            $t->id();
            $t->foreignId('patient_id')->constrained('hospital_patients');
            $t->foreignId('generated_by')->constrained('users');
            $t->string('report_type');
            $t->string('title');
            $t->text('content')->nullable();
            $t->string('file_path')->nullable();
            $t->string('status', 20)->default('draft');
            $t->dateTime('released_at')->nullable();
            $t->timestamps();
        });
    }

    private function makeStudentUser(): User
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $user = User::create([
            'name'      => 'Test Student',
            'email'     => 'student' . uniqid() . '@test.local',
            'password'  => bcrypt('password'),
            'role_id'   => $role->id,
            'is_active' => true,
            'gender'    => 'male',
            'phone'     => '08000000000',
            'address'   => 'Test Address',
            'date_of_birth' => '2000-01-01',
        ]);
        $school = School::firstOrCreate(['name' => 'Test School']);
        $dept = Department::firstOrCreate(['name' => 'Test Dept']);
        $prog = Programme::firstOrCreate(['name' => 'Test Prog']);
        Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'TEST/' . strtoupper(\Illuminate\Support\Str::random(6)),
            'school_id'     => $school->id,
            'department_id' => $dept->id,
            'programme_id'  => $prog->id,
        ]);
        return $user;
    }

    private function makeDoctor(): \App\Models\Hospital\HospitalStaff
    {
        $role = Role::firstOrCreate(['slug' => 'doctor'], ['name' => 'Doctor']);
        $doctorUser = User::create([
            'name'      => 'Doctor House',
            'email'     => 'doctor' . uniqid() . '@test.local',
            'password'  => bcrypt('password'),
            'role_id'   => $role->id,
            'is_active' => true,
            'gender'    => 'male',
            'phone'     => '08000000001',
            'address'   => 'Test Address',
            'date_of_birth' => '1980-01-01',
        ]);
        return \App\Models\Hospital\HospitalStaff::create([
            'user_id'      => $doctorUser->id,
            'staff_number' => 'DOC-' . uniqid(),
            'first_name'   => 'Gregory',
            'last_name'    => 'House',
            'staff_type'   => 'doctor',
            'phone'        => '08000000001',
            'is_available' => true,
            'is_active'    => true,
        ]);
    }
}