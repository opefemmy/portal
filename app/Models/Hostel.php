<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'capacity', 'available_rooms',
        'description', 'location', 'gender', 'is_active'
    ];

    public function rooms()
    {
        return $this->hasMany(HostelRoom::class);
    }

    public function beds()
    {
        return $this->hasManyThrough(HostelBed::class, HostelRoom::class);
    }

    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class);
    }

    /**
     * Computed at read time from the live `hostel_rooms.available_beds`
     * column (which itself is maintained by HostelController on every
     * allocation / checkout). The denormalised `hostels.available_rooms`
     * column was historically written with `0` at creation time and
     * never refreshed, causing a brand-new hostel to render "Full" on
     * the student dashboard even when no student had applied.
     *
     * The accessor also no longer relies on a never-loaded
     * `rooms_with_beds` relationship — that branch was dead code that
     * always fell through to the eager-load check below.
     *
     * If `rooms` was eager-loaded via `with(['rooms' => …])` (filtered
     * to rooms with available beds), counting that collection is the
     * answer; otherwise we issue one COUNT against the database.
     */
    public function getAvailableRoomsAttribute(): int
    {
        if ($this->relationLoaded('rooms')) {
            return $this->rooms->where('available_beds', '>', 0)->count();
        }

        return $this->rooms()->where('available_beds', '>', 0)->count();
    }

    /**
     * Force-recompute and persist the denormalised counter. Called by
     * HostelController after any change to rooms/allocations so consumers
     * that bypass the accessor (raw SQL, exports) still see a sensible
     * value.
     */
    public function recomputeAndSave(): int
    {
        $count = $this->rooms()->where('available_beds', '>', 0)->count();
        $this->available_rooms = $count;
        $this->saveQuietly();
        return $count;
    }
}