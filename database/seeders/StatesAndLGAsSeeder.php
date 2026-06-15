<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\LocalGovernment;

class StatesAndLGAsSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['name' => 'Abia', 'code' => 'AB'],
            ['name' => 'Adamawa', 'code' => 'AD'],
            ['name' => 'Akwa Ibom', 'code' => 'AK'],
            ['name' => 'Anambra', 'code' => 'AN'],
            ['name' => 'Bauchi', 'code' => 'BA'],
            ['name' => 'Bayelsa', 'code' => 'BY'],
            ['name' => 'Benue', 'code' => 'BE'],
            ['name' => 'Borno', 'code' => 'BO'],
            ['name' => 'Cross River', 'code' => 'CR'],
            ['name' => 'Delta', 'code' => 'DE'],
            ['name' => 'Ebonyi', 'code' => 'EB'],
            ['name' => 'Edo', 'code' => 'ED'],
            ['name' => 'Ekiti', 'code' => 'EK'],
            ['name' => 'Enugu', 'code' => 'EN'],
            ['name' => 'Gombe', 'code' => 'GO'],
            ['name' => 'Imo', 'code' => 'IM'],
            ['name' => 'Jigawa', 'code' => 'JI'],
            ['name' => 'Kaduna', 'code' => 'KD'],
            ['name' => 'Kano', 'code' => 'KN'],
            ['name' => 'Katsina', 'code' => 'KT'],
            ['name' => 'Kebbi', 'code' => 'KE'],
            ['name' => 'Kogi', 'code' => 'KG'],
            ['name' => 'Kwara', 'code' => 'KW'],
            ['name' => 'Lagos', 'code' => 'LA'],
            ['name' => 'Nasarawa', 'code' => 'NA'],
            ['name' => 'Niger', 'code' => 'NI'],
            ['name' => 'Ogun', 'code' => 'OG'],
            ['name' => 'Ondo', 'code' => 'ON'],
            ['name' => 'Osun', 'code' => 'OS'],
            ['name' => 'Oyo', 'code' => 'OY'],
            ['name' => 'Plateau', 'code' => 'PL'],
            ['name' => 'Rivers', 'code' => 'RI'],
            ['name' => 'Sokoto', 'code' => 'SO'],
            ['name' => 'Taraba', 'code' => 'TA'],
            ['name' => 'Yobe', 'code' => 'YO'],
            ['name' => 'Zamfara', 'code' => 'ZA'],
            ['name' => 'Federal Capital Territory', 'code' => 'FC'],
        ];

        foreach ($states as $state) {
            State::create($state);
        }

        // Sample LGAs - in production, you would import from Excel
        $lgas = [
            // Lagos
            ['state_id' => 24, 'name' => 'Alimosho'],
            ['state_id' => 24, 'name' => 'Ajeromi-Ifelodun'],
            ['state_id' => 24, 'name' => 'Kosofe'],
            ['state_id' => 24, 'name' => 'Mushin'],
            ['state_id' => 24, 'name' => 'Oshodi-Isolo'],
            ['state_id' => 24, 'name' => 'Ojo'],
            ['state_id' => 24, 'name' => 'Ikorodu'],
            ['state_id' => 24, 'name' => 'Surulere'],
            ['state_id' => 24, 'name' => 'Ifako-Ijaye'],
            ['state_id' => 24, 'name' => 'Shomolu'],
            // Kano
            ['state_id' => 19, 'name' => 'Kano Municipal'],
            ['state_id' => 19, 'name' => 'Dala'],
            ['state_id' => 19, 'name' => 'Gwale'],
            ['state_id' => 19, 'name' => 'Kumbotso'],
            ['state_id' => 19, 'name' => 'Tarauni'],
            ['state_id' => 19, 'name' => 'Nigerian Army'],
            // Rivers
            ['state_id' => 32, 'name' => 'Port Harcourt'],
            ['state_id' => 32, 'name' => 'Obio-Akpor'],
            ['state_id' => 32, 'name' => 'Okrika'],
            ['state_id' => 32, 'name' => 'Eleme'],
            ['state_id' => 32, 'name' => 'Tai'],
        ];

        foreach ($lgas as $lga) {
            LocalGovernment::create($lga);
        }
    }
}