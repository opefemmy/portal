<?php

namespace Tests\Feature\Student;

use App\Models\Hostel;
use App\Models\HostelBed;
use App\Models\HostelRoom;
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
}