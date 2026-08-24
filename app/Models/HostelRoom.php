<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelRoom extends Model
{
    protected $fillable = [
        'hostel_id', 'room_number', 'floor', 'capacity',
        'available_beds', 'type', 'is_active'
    ];

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function beds()
    {
        return $this->hasMany(HostelBed::class);
    }

    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class);
    }

    /**
     * Live count of available beds, derived from the bed rows
     * themselves (single source of truth). Used by the student
     * dashboard to determine whether a room has any bed to apply
     * for. The denormalised `hostel_rooms.available_beds` column
     * can drift in old data; this accessor reads the live count
     * from `hostel_beds.status='available'`, so a room never shows
     * "Full" when there's actually a free bed.
     *
     * Falls back to the cached column when no relationship is loaded
     * to avoid an extra COUNT query on the dense admin listings.
     */
    public function getLiveAvailableBedsAttribute(): int
    {
        if ($this->relationLoaded('beds')) {
            return $this->beds->where('status', 'available')->count();
        }
        return $this->beds()->where('status', 'available')->count();
    }

    /**
     * Human-readable floor label. The `hostel_rooms.floor` column is a
     * tinyint (1 = Ground, 2 = First, 3 = Second, …). Student-facing
     * UI uses these names instead of bare numbers so the floor groups
     * in the apply modal read naturally ("Ground Floor", "First
     * Floor", "Second Floor", …).
     *
     * Floors 0 and below fall back to the raw integer (basements,
     * mezzanines, etc.).
     */
    public static function floorName(int $floor): string
    {
        $names = [
            1 => 'Ground Floor',
            2 => 'First Floor',
            3 => 'Second Floor',
            4 => 'Third Floor',
            5 => 'Fourth Floor',
            6 => 'Fifth Floor',
            7 => 'Sixth Floor',
            8 => 'Seventh Floor',
            9 => 'Eighth Floor',
            10 => 'Ninth Floor',
        ];

        return $names[$floor] ?? ('Floor ' . $floor);
    }
}