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
     * The denormalised `available_rooms` column was historically
     * written at creation with `0` (HostelController::store()) and
     * never recomputed when rooms were added or allocations changed.
     * The result was brand-new hostels showing "Full" even when no
     * student had applied. We now derive the value at read time from
     * the rooms / allocations tables — single source of truth.
     *
     * Override ONLY the read path: any code that still calls
     * `$hostel->available_rooms = …` should call `recomputeAndSave()`
     * (or just write to `rooms` / `allocations`) instead.
     */
    public function getAvailableRoomsAttribute(): int
    {
        // Has the relationship been eager-loaded? Use it; otherwise
        // count from the database. Both are cheap (one COUNT query).
        if (array_key_exists('rooms_with_beds', $this->relations)) {
            return $this->rooms_with_beds->count();
        }
        if ($this->relationLoaded('rooms')) {
            return $this->rooms->where('available_beds', '>', 0)->count();
        }
        return $this->rooms()->where('available_beds', '>', 0)->count();
    }

    /**
     * Force-recompute and persist the denormalised counter. Called
     * by HostelController after any change to rooms/allocations so
     * consumers that bypass the accessor (e.g. raw SQL, exports)
     * still see a sensible value.
     */
    public function recomputeAndSave(): int
    {
        $count = $this->rooms()->where('available_beds', '>', 0)->count();
        $this->available_rooms = $count;
        $this->saveQuietly();
        return $count;
    }
}