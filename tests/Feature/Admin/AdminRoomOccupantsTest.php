<?php

namespace Tests\Feature\Admin;

use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins that the admin can see who occupies each bed in a hostel room.
 *
 * User requirement: "the admin should be able to see the occupants of
 * each rooms allocated to students". The admin hostel show page
 * previously listed capacity + available bed count but not the
 * student occupying each bed.
 *
 * Coverage:
 *  - route GET /admin/hostels/{hostel}/rooms/{room} renders the per-bed
 *    occupant table
 *  - occupied beds render the student name + matric + check-in date
 *  - available beds render "—" (no student)
 *  - checked-out / cancelled allocations are NOT shown as current occupants
 *  - cross-hostel room lookup 404s
 *  - the rooms.show link on the hostel's index page exists
 */
class AdminRoomOccupantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        PermissionService::flush();
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_beds');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permissions');
        parent::tearDown();
    }

    public function test_room_show_renders_occupied_and_available_beds(): void
    {
        $hostel = Hostel::create($this->hostelAttrs());
        $room = HostelRoom::create($this->roomAttrs($hostel->id, capacity: 3));

        // Two occupied beds, one available bed.
        $bedA = HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'Bed 1', 'status' => 'occupied', 'student_id' => null]);
        $bedB = HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'Bed 2', 'status' => 'occupied', 'student_id' => null]);
        $bedC = HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'Bed 3', 'status' => 'available']);

        $studentA = $this->makeStudent('Alice Adesina', 'AAA/001');
        $studentB = $this->makeStudent('Bola Badmus',   'BBB/002');

        // Bed A and B have both a denormalised student_id on the bed row
        // (set by Admin\HostelController::storeAllocation) and an active
        // HostelAllocation row.
        $bedA->update(['student_id' => $studentA->id]);
        $bedB->update(['student_id' => $studentB->id]);

        HostelAllocation::create([
            'hostel_id'       => $hostel->id,
            'hostel_room_id'  => $room->id,
            'student_id'      => $studentA->id,
            'bed_id'          => $bedA->id,
            'session_id'      => $this->session->id,
            'check_in_date'   => '2025-09-01',
            'status'          => 'active',
        ]);
        HostelAllocation::create([
            'hostel_id'       => $hostel->id,
            'hostel_room_id'  => $room->id,
            'student_id'      => $studentB->id,
            'bed_id'          => $bedB->id,
            'session_id'      => $this->session->id,
            'check_in_date'   => '2025-09-02',
            'status'          => 'active',
        ]);

        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)
            ->get(route('admin.hostels.rooms.show', [$hostel, $room]));

        $response->assertOk();

        $html = $response->getContent();

        // Each bed number appears.
        $this->assertStringContainsString('Bed 1', $html);
        $this->assertStringContainsString('Bed 2', $html);
        $this->assertStringContainsString('Bed 3', $html);

        // Occupied beds show the student name + matric.
        $this->assertStringContainsString('Alice Adesina', $html);
        $this->assertStringContainsString('AAA/001', $html);
        $this->assertStringContainsString('Bola Badmus', $html);
        $this->assertStringContainsString('BBB/002', $html);

        // Check-in dates render.
        $this->assertStringContainsString('Sep 01, 2025', $html);
        $this->assertStringContainsString('Sep 02, 2025', $html);

        // The available bed (Bed 3) has the Available badge but no student.
        $this->assertMatchesRegularExpression(
            '/Bed 3[\s\S]*?Available[\s\S]*?<\/tr>/',
            $html,
            'The available bed row must show the Available status badge.'
        );
    }

    public function test_checked_out_allocations_are_not_shown_as_current_occupants(): void
    {
        $hostel = Hostel::create($this->hostelAttrs());
        $room = HostelRoom::create($this->roomAttrs($hostel->id, capacity: 1));

        $bed = HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'Bed 1', 'status' => 'available']);
        $student = $this->makeStudent('Charles Checkedout', 'CCC/003');

        // A checked-out allocation row exists for this student — the bed
        // row has been reset to available. The page must NOT render the
        // student as a current occupant.
        HostelAllocation::create([
            'hostel_id'       => $hostel->id,
            'hostel_room_id'  => $room->id,
            'student_id'      => $student->id,
            'bed_id'          => $bed->id,
            'session_id'      => $this->session->id,
            'check_in_date'   => '2025-01-15',
            'check_out_date'  => '2025-06-15',
            'status'          => 'checked_out',
        ]);

        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)
            ->get(route('admin.hostels.rooms.show', [$hostel, $room]));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('Charles Checkedout', $html);
    }

    public function test_cross_hostel_room_lookup_returns_404(): void
    {
        $hostelA = Hostel::create($this->hostelAttrs(name: 'Hostel A', code: 'HA'));
        $hostelB = Hostel::create($this->hostelAttrs(name: 'Hostel B', code: 'HB'));
        $roomOfB = HostelRoom::create($this->roomAttrs($hostelB->id, capacity: 1));

        $admin = $this->makeAdmin();
        // Pass hostel A in the URL but B's room id — controller must 404.
        $response = $this->actingAs($admin)
            ->get(route('admin.hostels.rooms.show', [$hostelA, $roomOfB]));

        $response->assertStatus(404);
    }

    public function test_hostel_show_lists_view_occupants_link_for_each_room(): void
    {
        $hostel = Hostel::create($this->hostelAttrs());
        $room = HostelRoom::create($this->roomAttrs($hostel->id, capacity: 2));

        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)
            ->get(route('admin.hostels.show', $hostel));

        $response->assertOk();
        $html = $response->getContent();

        // The room row in the hostel's room table must carry a link to
        // the new occupant view.
        $this->assertStringContainsString(
            route('admin.hostels.rooms.show', [$hostel, $room]),
            $html,
            'The rooms table on hostel.show must link to the per-room occupant view.'
        );
        $this->assertStringContainsString('View Occupants', $html);
    }

    /* --- helpers --- */

    private function makeStudent(string $name, string $matric): Student
    {
        $user = User::create([
            'name'     => $name,
            'email'    => strtolower(str_replace(' ', '.', $name)) . '@example.test',
            'password' => bcrypt('password'),
        ]);
        return Student::create([
            'user_id'       => $user->id,
            'matric_number' => $matric,
            'status'        => 'active',
        ]);
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $perm = Permission::firstOrCreate(
            ['slug' => 'admin.hostels.manage'],
            ['name' => 'Manage hostels', 'group' => 'admin'],
        );
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        PermissionService::flush();

        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);
        return $user;
    }

    private function hostelAttrs(string $name = 'Test Hostel', string $code = 'TH'): array
    {
        return [
            'name'            => $name,
            'code'            => $code,
            'type'            => 'Male',
            'gender'          => 'Male',
            'capacity'        => 10,
            'available_rooms' => 1,
            'is_active'       => true,
        ];
    }

    private function roomAttrs(int $hostelId, int $capacity): array
    {
        return [
            'hostel_id'      => $hostelId,
            'room_number'    => 'R-' . uniqid(),
            'floor'          => 1,
            'capacity'       => $capacity,
            'available_beds' => $capacity,
            'type'           => 'Standard',
            'is_active'      => true,
        ];
    }

    private Session $session;

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('permissions', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('group', 50)->nullable();
            $t->timestamps();
        });
        Schema::create('role_permissions', function ($t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });
        Schema::create('role_user', function ($t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('user_id');
            $t->timestamps();
            $t->primary(['role_id', 'user_id']);
        });
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->unsignedBigInteger('role_id')->nullable();
            $t->timestamps();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });
        Schema::create('hostels', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->string('type');
            $t->integer('capacity');
            $t->integer('available_rooms')->default(0);
            $t->text('description')->nullable();
            $t->string('location')->nullable();
            $t->string('gender');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('hostel_rooms', function ($t) {
            $t->id();
            $t->unsignedBigInteger('hostel_id');
            $t->string('room_number');
            $t->integer('floor');
            $t->integer('capacity');
            $t->integer('available_beds')->default(0);
            $t->string('type')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('hostel_beds', function ($t) {
            $t->id();
            $t->unsignedBigInteger('hostel_room_id');
            $t->string('bed_number');
            $t->string('status')->default('available');
            $t->unsignedBigInteger('student_id')->nullable();
            $t->timestamps();
        });
        Schema::create('hostel_allocations', function ($t) {
            $t->id();
            $t->unsignedBigInteger('hostel_id');
            $t->unsignedBigInteger('hostel_room_id');
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('bed_id')->nullable();
            $t->unsignedBigInteger('session_id');
            $t->date('check_in_date')->nullable();
            $t->date('check_out_date')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        $this->session = Session::create(['name' => '2025/2026', 'is_current' => true]);
    }
}
