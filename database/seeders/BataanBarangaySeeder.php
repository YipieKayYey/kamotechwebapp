<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class BataanBarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bataan = Province::firstOrCreate([
            'name' => 'Bataan',
        ], [
            'psgc_code' => '030000000',
        ]);

        $bataanBarangays = [
            'Abucay' => [
                'Bangkal', 'Calaylayan', 'Capitangan', 'Gabon', 'Laon',
                'Mabatang', 'Omboy', 'Salian', 'Wawa',
            ],
            'Bagac' => [
                'Atilano L. Ricardo', 'Bagumbayan', 'Banawang', 'Binuangan',
                'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa', 'Parang', 'Paysawan',
                'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog',
            ],
            'Balanga' => [
                'Bagong Silang', 'Bagumbayan', 'Cabog-Cabog', 'Camacho',
                'Cataning', 'Central', 'Cupang North', 'Cupang Proper',
                'Cupang West', 'Dangcol', 'Doña Francisca', 'Ibayo', 'Lote',
                'Malabia', 'Munting Batangas', 'Poblacion', 'Pto. Rivas Ibaba',
                'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Talisay', 'Tanato',
                'Tenejero', 'Tortugas', 'Tuyo',
            ],
            'Dinalupihan' => [
                'Aquino', 'Bangal', 'Bayan-bayanan', 'Bonifacio', 'Burgos', 'Colo',
                'Daang Bago', 'Dalao', 'Del Pilar', 'Gen. Luna', 'Gomez', 'Happy Valley',
                'Jose C. Payumo, Jr.', 'Kataasan', 'Layac', 'Luacan', 'Mabini Ext.',
                'Mabini Proper', 'Magsaysay', 'Maligaya', 'Naparing', 'New San Jose',
                'Old San Jose', 'Padre Dandan', 'Pag-asa', 'Pagalanggang', 'Payangan',
                'Pentor', 'Pinulot', 'Pita', 'Rizal', 'Roosevelt', 'Roxas', 'Saguing',
                'San Benito', 'San Isidro', 'San Pablo', 'San Ramon', 'San Simon',
                'Santa Isabel', 'Santo Niño', 'Sapang Balas', 'Torres Bugauen',
                'Tubo-tubo', 'Tucop', 'Zamora',
            ],
            'Hermosa' => [
                'A. Rivera', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman',
                'Cataning', 'Culis', 'Daungan', 'Judge Roman Cruz Sr.', 'Mabiga', 'Mabuco',
                'Maite', 'Mambog-Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba',
                'Sacrifice Valley', 'San Pedro', 'Santo Cristo', 'Sumalo', 'Tipo',
            ],
            'Limay' => [
                'Alangan', 'Duale', 'Kitang 2 & Luz', 'Kitang I', 'Lamao', 'Landing',
                'Poblacion', 'Reformista', 'Saint Francis II', 'San Francisco de Asis',
                'Townsite', 'Wawa',
            ],
            'Mariveles' => [
                'Alas-asin', 'Alion', 'Balon-Anito', 'Baseco Country', 'Batangas II',
                'Biaan', 'Cabcaben', 'Camaya', 'Ipag', 'Lucanin', 'Malaya', 'Maligaya',
                'Mt. View', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Townsite',
            ],
            'Morong' => [
                'Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang',
            ],
            'Orani' => [
                'Apollo', 'Bagong Paraiso', 'Balut', 'Bayan', 'Calero', 'Centro I',
                'Centro II', 'Dona', 'Kabalutan', 'Kaparangan', 'Maria Fe', 'Masantol',
                'Mulawin', 'Pag-asa', 'Paking-Carbonero', 'Palihan', 'Pantalan Bago',
                'Pantalan Luma', 'Parang Parang', 'Puksuan', 'Sibul', 'Silahis',
                'Tagumpay', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero', 'Tugatog', 'Wawa',
            ],
            'Orion' => [
                'Arellano', 'Bagumbayan', 'Balagtas', 'Balut', 'Bantan', 'Bilolo',
                'Calungusan', 'Camachile', 'Daang Bago', 'Daang Bilolo', 'Daang Pare',
                'General Lim', 'Kapunitan', 'Lati', 'Lusungan', 'Puting Buhangin',
                'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo',
                'Villa Angeles', 'Wakas', 'Wawa',
            ],
            'Pilar' => [
                'Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti',
                'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liyang', 'Nagwaling',
                'Panilao', 'Pantingan', 'Poblacion', 'Rizal', 'Santa Rosa',
                'Wakas North', 'Wakas South', 'Wawa',
            ],
            'Samal' => [
                'East Calaguiman', 'East Daang Bago', 'Gugo', 'Ibaba', 'Imelda',
                'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia',
                'Sapa', 'Tabing Ilog', 'West Calaguiman', 'West Daang Bago',
            ],
        ];

        foreach ($bataanBarangays as $cityName => $barangays) {
            // Handle Balanga vs Balanga City naming
            $lookupNames = $cityName === 'Balanga' ? ['Balanga City', 'Balanga'] : [$cityName];

            $city = City::where('province_id', $bataan->id)
                ->where(function ($q) use ($lookupNames) {
                    foreach ($lookupNames as $n) {
                        $q->orWhere('name', $n);
                    }
                })
                ->first();

            if (! $city) {
                $city = City::create([
                    'province_id' => $bataan->id,
                    'name' => $cityName,
                    'is_city' => $cityName === 'Balanga' ? 1 : 0,
                ]);
            }

            $baseCode = $city->psgc_code ?: sprintf('TMP%06d', $city->id);

            foreach (array_values($barangays) as $index => $barangayName) {
                $code = $baseCode.sprintf('%03d', $index + 1);

                Barangay::updateOrCreate(
                    ['psgc_code' => $code],
                    [
                        'city_id' => $city->id,
                        'name' => $barangayName,
                    ]
                );
            }
        }
    }
}
