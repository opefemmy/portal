<?php

namespace Database\Seeders;

use App\Models\AdmissionCentre;
use Illuminate\Database\Seeder;

class AdmissionCentreSeeder extends Seeder
{
    public function run(): void
    {
        $centres = [
            [
                'name' => 'Main Campus',
                'code' => 'MAIN',
                'address' => 'Ekiti State College of Technology, Iyin Ekiti',
                'phone' => '08012345678',
                'email' => 'admission@ekscotech.edu.ng',
                'is_active' => true,
            ],
            [
                'name' => 'Ado Ekiti Centre',
                'code' => 'ADO',
                'address' => 'Ado Ekiti, Ekiti State',
                'phone' => '08023456789',
                'email' => 'ado@ekscotech.edu.ng',
                'is_active' => true,
            ],
            [
                'name' => 'Ikere Ekiti Centre',
                'code' => 'IKR',
                'address' => 'Ikere Ekiti, Ekiti State',
                'phone' => '08034567890',
                'email' => 'ikere@ekscotech.edu.ng',
                'is_active' => true,
            ],
            [
                'name' => 'Ilorin Study Centre',
                'code' => 'ILO',
                'address' => 'Ilorin, Kwara State',
                'phone' => '08045678901',
                'email' => 'ilorin@ekscotech.edu.ng',
                'is_active' => true,
            ],
            [
                'name' => 'Abuja Study Centre',
                'code' => 'ABJ',
                'address' => 'Abuja, FCT',
                'phone' => '08056789012',
                'email' => 'abuja@ekscotech.edu.ng',
                'is_active' => true,
            ],
        ];

        foreach ($centres as $centre) {
            AdmissionCentre::create($centre);
        }

        $this->command->info('Created ' . count($centres) . ' admission centres.');
    }
}
