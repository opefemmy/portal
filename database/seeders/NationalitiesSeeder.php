<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nationality;

class NationalitiesSeeder extends Seeder
{
    public function run(): void
    {
        $nationalities = [
            ['name' => 'Nigerian', 'code' => 'NGA'],
            ['name' => 'Non-Nigerian', 'code' => 'NON'],
            // Common African nationalities
            ['name' => 'Ghanaian', 'code' => 'GHA'],
            ['name' => 'Togolese', 'code' => 'TGO'],
            ['name' => 'Beninese', 'code' => 'BEN'],
            ['name' => 'Nigerien', 'code' => 'NER'],
            ['name' => 'Chadian', 'code' => 'CHA'],
            ['name' => 'Cameroonian', 'code' => 'CMR'],
            ['name' => 'Ivorian', 'code' => 'CIV'],
            ['name' => 'Liberian', 'code' => 'LBR'],
            ['name' => 'Sierra Leonean', 'code' => 'SLE'],
            ['name' => 'Guinean', 'code' => 'GIN'],
            ['name' => 'Senegalese', 'code' => 'SEN'],
            ['name' => 'Malian', 'code' => 'MLI'],
            ['name' => 'Burkinabe', 'code' => 'BFA'],
            ['name' => 'Kenyan', 'code' => 'KEN'],
            ['name' => 'Tanzanian', 'code' => 'TAN'],
            ['name' => 'Ugandan', 'code' => 'UGA'],
            ['name' => 'South African', 'code' => 'ZAF'],
            // Other common nationalities
            ['name' => 'American', 'code' => 'USA'],
            ['name' => 'British', 'code' => 'GBR'],
            ['name' => 'Canadian', 'code' => 'CAN'],
            ['name' => 'Indian', 'code' => 'IND'],
            ['name' => 'Chinese', 'code' => 'CHN'],
            ['name' => 'Pakistani', 'code' => 'PAK'],
        ];

        foreach ($nationalities as $nationality) {
            Nationality::create($nationality);
        }
    }
}