<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addresses = [
            ['localaddressid' => 1, 'province' => 'Abra', 'city' => 'Bangued', 'latlong' => '17.600; 120.617', 'latitude' => 17.600000, 'longitude' => 120.617000],
            ['localaddressid' => 2, 'province' => 'Abra', 'city' => 'Boliney', 'latlong' => '17.400; 120.800', 'latitude' => 17.400000, 'longitude' => 120.800000],
            ['localaddressid' => 3, 'province' => 'Abra', 'city' => 'Bucay', 'latlong' => '17.533; 120.717', 'latitude' => 17.533000, 'longitude' => 120.717000],
            ['localaddressid' => 4, 'province' => 'Abra', 'city' => 'Bucloc', 'latlong' => '17.450; 120.850', 'latitude' => 17.450000, 'longitude' => 120.850000],
            ['localaddressid' => 5, 'province' => 'Abra', 'city' => 'Daguioman', 'latlong' => '17.450; 120.917', 'latitude' => 17.450000, 'longitude' => 120.917000],
            ['localaddressid' => 6, 'province' => 'Abra', 'city' => 'Danglas', 'latlong' => '17.700; 120.650', 'latitude' => 17.700000, 'longitude' => 120.650000],
            ['localaddressid' => 7, 'province' => 'Abra', 'city' => 'Dolores', 'latlong' => '17.64667; 120.71083', 'latitude' => 17.646670, 'longitude' => 120.710830],
            ['localaddressid' => 8, 'province' => 'Abra', 'city' => 'La Paz', 'latlong' => '17.70556; 120.69306', 'latitude' => 17.705560, 'longitude' => 120.693060],
            ['localaddressid' => 9, 'province' => 'Abra', 'city' => 'Lacub', 'latlong' => '17.667; 120.950', 'latitude' => 17.667000, 'longitude' => 120.950000],
            ['localaddressid' => 10, 'province' => 'Abra', 'city' => 'Lagangilang', 'latlong' => '17.617; 120.783', 'latitude' => 17.617000, 'longitude' => 120.783000],
            ['localaddressid' => 11, 'province' => 'Abra', 'city' => 'Lagayan', 'latlong' => '17.717; 120.700', 'latitude' => 17.717000, 'longitude' => 120.700000],
            ['localaddressid' => 12, 'province' => 'Abra', 'city' => 'Langiden', 'latlong' => '17.583; 120.567', 'latitude' => 17.583000, 'longitude' => 120.567000],
            ['localaddressid' => 13, 'province' => 'Abra', 'city' => 'Licuan-Baay', 'latlong' => '17.62694; 120.83222', 'latitude' => 17.626940, 'longitude' => 120.832220],
            ['localaddressid' => 14, 'province' => 'Abra', 'city' => 'Luba', 'latlong' => '17.34917; 120.69111', 'latitude' => 17.349170, 'longitude' => 120.691110],
            ['localaddressid' => 15, 'province' => 'Abra', 'city' => 'Malibcong', 'latlong' => '17.567; 120.983', 'latitude' => 17.567000, 'longitude' => 120.983000],
            ['localaddressid' => 16, 'province' => 'Abra', 'city' => 'Manabo', 'latlong' => '17.467; 120.700', 'latitude' => 17.467000, 'longitude' => 120.700000],
            ['localaddressid' => 17, 'province' => 'Abra', 'city' => 'Peñarrubia', 'latlong' => '17.567; 120.650', 'latitude' => 17.567000, 'longitude' => 120.650000],
            ['localaddressid' => 18, 'province' => 'Abra', 'city' => 'Pidigan', 'latlong' => '17.567; 120.583', 'latitude' => 17.567000, 'longitude' => 120.583000],
            ['localaddressid' => 19, 'province' => 'Abra', 'city' => 'Pilar', 'latlong' => '17.41750; 120.59417', 'latitude' => 17.417500, 'longitude' => 120.594170],
            ['localaddressid' => 20, 'province' => 'Abra', 'city' => 'Sallapadan', 'latlong' => '17.467; 120.767', 'latitude' => 17.467000, 'longitude' => 120.767000],
            ['localaddressid' => 21, 'province' => 'Abra', 'city' => 'San Isidro', 'latlong' => '17.467; 120.600', 'latitude' => 17.467000, 'longitude' => 120.600000],
            ['localaddressid' => 22, 'province' => 'Basilan', 'city' => 'Tipo-Tipo', 'latlong' => '6.533; 122.167', 'latitude' => 6.533000, 'longitude' => 122.167000],
            ['localaddressid' => 23, 'province' => 'Basilan', 'city' => 'Tuburan', 'latlong' => '6.600; 122.200', 'latitude' => 6.600000, 'longitude' => 122.200000],
            ['localaddressid' => 24, 'province' => 'Basilan', 'city' => 'Ungkaya Pukan', 'latlong' => '6.500; 122.117', 'latitude' => 6.500000, 'longitude' => 122.117000],
            ['localaddressid' => 25, 'province' => 'Bataan', 'city' => 'Balanga City', 'latlong' => null, 'latitude' => null, 'longitude' => null],
        ];

        foreach ($addresses as $data) {
            DB::table('localaddress')->updateOrInsert(
                ['localaddressid' => $data['localaddressid']],
                array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}