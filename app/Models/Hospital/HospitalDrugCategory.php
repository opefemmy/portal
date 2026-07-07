<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalDrugCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function drugs(): HasMany
    {
        return $this->hasMany(HospitalDrug::class, 'category_id');
    }
}