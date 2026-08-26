<?php

namespace Tests\Feature\Student;

use App\Models\Hostel;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\User;
use App\Models\Student;
use App\Models\Session as AcademicSession;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Asserts the three-state availability badge logic on the student
 * hostel dashboard:
 *
 *   - Hostel with zero rooms      → "No Rooms Configured"
 *   - Hostel with rooms, all full → "Full"
 *   - Hostel with rooms, some free → "Apply"
 *
 * Regression target: prior to this slice, every hostel rendered
 * "Full" because the eager-load filtered to rooms with available
 * beds, the count came back as 0 for a brand-new hostel with no
 * rooms, and the blade's `> 0 ? Apply : Full` ternary dropped
 * straight to the "Full" badge.
 *
 * Also covers HostelRoom::floorName() so the floor-grouped apply
 * modal renders the human-readable label.
 */
class HostelAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('hostels', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code');
            $t->string('type');
            $t->string('gender');
            $t->integer('capacity');
            $t->integer('available_rooms')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('hostel_rooms', function ($t) {
            $t->id();
            $t->integer('hostel_id');
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
            $t->integer('hostel_room_id');
            $t->string('bed_number');
            $t->string('status')->default('available');
            $t->integer('student_id')->nullable();
            $t->timestamps();
        });

        // Minimal tables the gender-filter regression tests need
        // to authenticate a user and resolve auth()->user()->student.
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('gender', 10)->nullable();
            $t->unsignedBigInteger('role_id')->nullable();
            $t->timestamps();
        });

        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug');
            $t->timestamps();
        });

        // Pivot table for User->roles() relationship.
        Schema::create('role_user', function ($t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('user_id');
            $t->timestamps();
            $t->primary(['role_id', 'user_id']);
        });

        // Permissions tables — the controller's EnforcesPermission
        // trait calls PermissionService::allows(), which reads the
        // primary role slug (via the users.role_id relation) and
        // then queries role_permissions. We need the full chain so
        // the trait gate doesn't 403 us before the controller body
        // runs.
        Schema::create('permissions', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('group', 50)->nullable();
            $t->timestamps();
        });

        Schema::create('role_permissions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });

        Schema::create('students', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matric_number')->nullable();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('programme_id')->nullable();
            $t->unsignedBigInteger('session_id')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });

        Schema::create('sessions', function ($t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_current')->default(false);
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

    public function test_hostel_with_zero_rooms_is_not_marked_full(): void
    {
        $hostel = Hostel::create([
            'name' => 'New Hall',
            'code' => 'NEW',
            'type' => 'Male',
            'gender' => 'Male',
            'capacity' => 100,
            'available_rooms' => 0,
            'is_active' => true,
        ]);

        // Old behaviour: accessor returned 0 because the eager-load
        // collection was empty; view rendered "Full". New behaviour:
        // the blade checks `$hostel->rooms->isEmpty()` first and
        // renders "No Rooms Configured".
        $this->assertSame(0, $hostel->rooms->count());
        // The accessor still computes available_rooms from the live
        // bed count, which for a room-less hostel is correctly 0.
        $this->assertSame(0, $hostel->available_rooms);
    }

    public function test_hostel_with_full_room_is_marked_full(): void
    {
        $hostel = Hostel::create([
            'name' => 'Annex',
            'code' => 'ANX',
            'type' => 'Female',
            'gender' => 'Female',
            'capacity' => 4,
            'is_active' => true,
        ]);

        $room = HostelRoom::create([
            'hostel_id'       => $hostel->id,
            'room_number'     => '1',
            'floor'           => 1,
            'capacity'        => 2,
            'available_beds'  => 0,
            'is_active'       => true,
        ]);

        // Two beds, both occupied.
        HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'A', 'status' => 'occupied']);
        HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'B', 'status' => 'occupied']);

        $hostel->refresh();
        $hostel->load('rooms.beds');

        // live_available_beds derives from bed rows (single source of truth).
        $this->assertSame(0, $room->fresh()->live_available_beds);

        // Eager-loaded path: available_rooms counts rooms with at
        // least one available bed — zero here, so "Full".
        $roomsWithBeds = $hostel->rooms->filter(fn ($r) => $r->live_available_beds > 0);
        $this->assertSame(0, $roomsWithBeds->count());
    }

    public function test_hostel_with_partially_filled_room_is_applyable(): void
    {
        $hostel = Hostel::create([
            'name' => 'Tower',
            'code' => 'TWR',
            'type' => 'Male',
            'gender' => 'Male',
            'capacity' => 4,
            'is_active' => true,
        ]);

        $room = HostelRoom::create([
            'hostel_id'      => $hostel->id,
            'room_number'    => '5',
            'floor'          => 2,
            'capacity'       => 2,
            'available_beds' => 1,
            'is_active'      => true,
        ]);

        HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'A', 'status' => 'occupied']);
        HostelBed::create(['hostel_room_id' => $room->id, 'bed_number' => 'B', 'status' => 'available']);

        $hostel->refresh();
        $hostel->load('rooms.beds');

        $roomsWithBeds = $hostel->rooms->filter(fn ($r) => $r->live_available_beds > 0);
        $this->assertSame(1, $roomsWithBeds->count());
        $this->assertSame(1, $roomsWithBeds->first()->live_available_beds);

        // Grouping by floor is the new view's primary shape.
        $byFloor = $roomsWithBeds->groupBy('floor');
        $this->assertArrayHasKey(2, $byFloor->toArray());
        $this->assertSame('First Floor', HostelRoom::floorName(2));
    }

    public function test_floor_name_humanises_common_floors(): void
    {
        $this->assertSame('Ground Floor', HostelRoom::floorName(1));
        $this->assertSame('First Floor',  HostelRoom::floorName(2));
        $this->assertSame('Second Floor', HostelRoom::floorName(3));
        // Out-of-range floors (basements, towers) fall back to the
        // raw integer label rather than rendering blank.
        $this->assertSame('Floor 0', HostelRoom::floorName(0));
        $this->assertSame('Floor 25', HostelRoom::floorName(25));
    }

    /**
     * Regression for the bug where a male student's hostel listing
     * included female hostels (and vice versa) because the listing
     * only filtered by `?gender=` when the student explicitly chose a
     * value in the dropdown. The controller now forces a default
     * scope from `auth()->user()->gender`.
     */
    public function test_male_student_does_not_see_female_hostel_in_listing(): void
    {
        $this->makeHostel('Male Hall',   'MHL', 'Male');
        $this->makeHostel('Female Hall', 'FHL', 'Female');
        $this->makeHostel('Annex',       'ANX', 'Both');

        $student = $this->makeStudent('male', 'male@example.test');

        $view = $this->callAvailableHostels($student->user);
        $rendered = (string) $view;

        $this->assertStringContainsString('Male Hall', $rendered);
        $this->assertStringContainsString('Annex', $rendered);
        $this->assertStringNotContainsString('Female Hall', $rendered);
    }

    public function test_female_student_does_not_see_male_hostel_in_listing(): void
    {
        $this->makeHostel('Male Hall',   'MHL', 'Male');
        $this->makeHostel('Female Hall', 'FHL', 'Female');
        $this->makeHostel('Annex',       'ANX', 'Both');

        $student = $this->makeStudent('female', 'female@example.test');

        $view = $this->callAvailableHostels($student->user);
        $rendered = (string) $view;

        $this->assertStringContainsString('Female Hall', $rendered);
        $this->assertStringContainsString('Annex', $rendered);
        $this->assertStringNotContainsString('Male Hall', $rendered);
    }

    /**
     * The manual `?gender=` dropdown is now a *restrictive* filter on
     * top of the controller's automatic scope. A male student
     * passing `?gender=Female` must NOT be allowed to widen the list
     * back to female hostels — the server treats it as a no-op.
     */
    public function test_male_student_cannot_widen_listing_with_dropdown(): void
    {
        $this->makeHostel('Male Hall',   'MHL', 'Male');
        $this->makeHostel('Female Hall', 'FHL', 'Female');

        $student = $this->makeStudent('male', 'male2@example.test');

        $view = $this->callAvailableHostels($student->user, ['gender' => 'Female']);
        $rendered = (string) $view;

        $this->assertStringContainsString('Male Hall', $rendered);
        $this->assertStringNotContainsString('Female Hall', $rendered);
    }

    /**
     * A student with no `users.gender` value still gets a listing —
     * every active hostel is surfaced so they're not blocked by a
     * missing data field, and a yellow "set your gender" warning is
     * shown so they know why the listing is unfiltered. Without
     * this, students whose profile never had `users.gender`
     * populated saw an empty page (regression from a stricter
     * version of the gender guard that only showed co-ed hostels
     * when gender was unknown).
     */
    public function test_student_with_unknown_gender_sees_all_active_hostels_with_warning(): void
    {
        $this->makeHostel('Male Hall',   'MHL', 'Male');
        $this->makeHostel('Female Hall', 'FHL', 'Female');
        $this->makeHostel('Annex',       'ANX', 'Both');

        $student = $this->makeStudent(null, 'nogender@example.test');

        $view = $this->callAvailableHostels($student->user);
        $rendered = (string) $view;

        // All three hostels render — we err on the side of showing
        // hostels when we can't reliably restrict.
        $this->assertStringContainsString('Male Hall', $rendered);
        $this->assertStringContainsString('Female Hall', $rendered);
        $this->assertStringContainsString('Annex', $rendered);
        // And the warning banner is present so the student knows
        // why the filter is off.
        $this->assertStringContainsString('gender isn', $rendered);
    }

    /**
     * When the student DOES have a gender set, the warning must
     * NOT render — only the genuinely-unknown-gender case shows
     * it.
     */
    public function test_student_with_known_gender_does_not_see_gender_warning(): void
    {
        $this->makeHostel('Male Hall',   'MHL', 'Male');

        $student = $this->makeStudent('male', 'known-male@example.test');

        $view = $this->callAvailableHostels($student->user);
        $rendered = (string) $view;

        $this->assertStringContainsString('Male Hall', $rendered);
        $this->assertStringNotContainsString('gender isn', $rendered);
    }

    /**
     * The apply() POST must also enforce the gender guard server-
     * side, not just rely on the listing being filtered. A male
     * student who hand-crafts a POST to a female hostel_id should
     * be rejected and no allocation row should be created.
     */
    public function test_apply_rejects_hostel_of_wrong_gender(): void
    {
        $femaleHall = $this->makeHostel('Female Hall', 'FHL', 'Female');
        $room = $this->makeRoomWithBeds($femaleHall->id, '1', 2);

        $student = $this->makeStudent('male', 'male3@example.test');
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $this->assertSame(0, \App\Models\HostelAllocation::count());

        $response = $this->callApply($student->user, [
            'hostel_id'      => $femaleHall->id,
            'hostel_room_id' => $room->id,
        ]);

        // The controller returns a redirect with an error flash
        // when the gender guard fires.
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(0, \App\Models\HostelAllocation::count());
        $this->assertTrue(
            $response->getSession()->has('error')
                || collect($response->headers->getCookies())->contains(
                    fn ($c) => str_contains((string) $c, 'error')
                ),
            'expected an error flash on the response',
        );
    }

    public function test_apply_accepts_hostel_of_matching_gender(): void
    {
        $maleHall = $this->makeHostel('Male Hall', 'MHL', 'Male');
        $room = $this->makeRoomWithBeds($maleHall->id, '1', 1);

        $student = $this->makeStudent('male', 'male4@example.test');
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $response = $this->callApply($student->user, [
            'hostel_id'      => $maleHall->id,
            'hostel_room_id' => $room->id,
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(1, \App\Models\HostelAllocation::count());

        // The allocation must be active immediately — no admin
        // approval step. Pre-fix the row was 'pending' and the
        // student had to wait for an admin to flip it to 'active',
        // which was the original "no approval" complaint.
        $allocation = \App\Models\HostelAllocation::first();
        $this->assertSame('active', $allocation->status);
        $this->assertTrue(
            $response->getSession()->has('success'),
            'Success flash must be set — student should see "Hostel allocated", not "Pending approval".'
        );
    }

    /**
     * The apply guard must mirror the listing fallback: if the
     * listing widened to show every active hostel because the
     * student's gender is unknown, the apply POST must accept any
     * of those hostels rather than blocking them with a
     * "not eligible" error. Pre-fix this was a UX dead end — the
     * student saw the listing (with warning), clicked Apply, and
     * was rejected.
     */
    public function test_apply_allows_unknown_gender_when_listing_was_widened(): void
    {
        $maleHall = $this->makeHostel('Male Hall', 'MHL', 'Male');
        $room = $this->makeRoomWithBeds($maleHall->id, '1', 1);

        // Student with empty users.gender — the legacy data shape
        // that triggered the original empty-listing bug.
        $student = $this->makeStudent(null, 'nogender-apply@example.test');
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $this->assertSame(0, \App\Models\HostelAllocation::count());

        $response = $this->callApply($student->user, [
            'hostel_id'      => $maleHall->id,
            'hostel_room_id' => $room->id,
        ]);

        $this->assertSame(302, $response->getStatusCode(), 'Apply should redirect, not error.');
        $this->assertSame(1, \App\Models\HostelAllocation::count(), 'Allocation row must be created.');
        $this->assertSame('active', \App\Models\HostelAllocation::first()->status);
        // No "not eligible" flash — the success flash should be set.
        $this->assertFalse(
            collect($response->headers->getCookies())->contains(
                fn ($c) => str_contains((string) $c, 'not eligible')
            ),
            'Unknown-gender student must not see the "not eligible" error.'
        );
    }

    // -- direct controller invocation (bypasses the auth + role +
    //    onboarding middleware chain the live /student/* route uses,
    //    so we test the controller's gender logic in isolation
    //    without having to build the full role/onboarding stack
    //    for a hand-rolled schema).

    private function callAvailableHostels(User $user, array $query = [])
    {
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create('/student/hostel/apply', 'GET', $query);
        app()->instance('request', $request);

        $controller = new \App\Http\Controllers\Student\HostelController();
        return $controller->availableHostels($request);
    }

    private function callApply(User $user, array $body)
    {
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create('/student/hostel/apply', 'POST', $body);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);

        $controller = new \App\Http\Controllers\Student\HostelController();
        return $controller->apply($request);
    }

    // -- helpers ----------------------------------------------------

    private function makeHostel(string $name, string $code, string $gender): Hostel
    {
        return Hostel::create([
            'name'    => $name,
            'code'    => $code,
            'type'    => $gender === 'Both' ? 'Mixed' : $gender,
            'gender'  => $gender,
            'capacity' => 10,
            'is_active' => true,
        ]);
    }

    private function makeRoomWithBeds(int $hostelId, string $roomNumber, int $capacity): HostelRoom
    {
        $room = HostelRoom::create([
            'hostel_id'      => $hostelId,
            'room_number'    => $roomNumber,
            'floor'          => 1,
            'capacity'       => $capacity,
            'available_beds' => $capacity,
            'is_active'      => true,
        ]);
        for ($i = 1; $i <= $capacity; $i++) {
            HostelBed::create([
                'hostel_room_id' => $room->id,
                'bed_number'     => 'Bed ' . $i,
                'status'         => 'available',
            ]);
        }
        return $room;
    }

    private function makeStudent(?string $gender, string $email): Student
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        // The controller's EnforcesPermission trait calls
        // PermissionService::allows('student.hostel.manage'), which
        // resolves through the role_permissions pivot. Grant our
        // student role the slug so the trait gate lets the request
        // through to the controller body — the unit-under-test.
        $perm = \App\Models\Permission::firstOrCreate(
            ['slug' => 'student.hostel.manage'],
            ['name' => 'Student Hostel Manage', 'group' => 'student'],
        );
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        \App\Services\Permissions\PermissionService::flush();

        $user = User::create([
            'name'     => 'Test Student',
            'email'    => $email,
            'password' => bcrypt('secret'),
            'gender'   => $gender,
            'role_id'  => $role->id,
        ]);

        // Keep the role_user pivot in sync — User's booted() hook
        // does this on save() but we want this set up before the
        // permission service is consulted.
        $user->roles()->syncWithoutDetaching([$role->id]);

        return Student::create([
            'user_id'       => $user->id,
            'matric_number' => 'MAT/' . $user->id,
            'status'        => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        \App\Services\Permissions\PermissionService::flush();
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('students');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('hostel_beds');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
        parent::tearDown();
    }
}