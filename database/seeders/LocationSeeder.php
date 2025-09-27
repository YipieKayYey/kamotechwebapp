<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Expect CSVs placed in storage/app/seeds/{provinces,cities,barangays}.csv
        // Format:
        // provinces.csv: psgc_code,name
        // cities.csv: psgc_code,province_psgc_code,name,is_city
        // barangays.csv: psgc_code,city_psgc_code,name

        $hasCsv = Storage::exists('seeds/provinces.csv')
            && Storage::exists('seeds/cities.csv')
            && Storage::exists('seeds/barangays.csv');

        if (! $hasCsv) {
            $this->command?->warn('PSGC CSVs not found. Seeding a minimal sample dataset (Bataan) for testing...');
            $this->seedSample();

            return;
        }

        DB::transaction(function (): void {
            $this->seedProvinces();
            $this->seedCities();
            $this->seedBarangays();
        });
    }

    protected function seedProvinces(): void
    {
        $path = 'seeds/provinces.csv';
        if (! Storage::exists($path)) {
            $this->command?->warn('provinces.csv not found, skipping provinces.');

            return;
        }

        $rows = $this->readCsv($path);
        $payload = $rows->map(fn (array $r) => [
            'psgc_code' => $r['psgc_code'],
            'name' => $r['name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($payload->chunk(1000) as $chunk) {
            Province::query()->upsert($chunk->all(), ['psgc_code'], ['name', 'updated_at']);
        }
    }

    protected function seedCities(): void
    {
        $path = 'seeds/cities.csv';
        if (! Storage::exists($path)) {
            $this->command?->warn('cities.csv not found, skipping cities.');

            return;
        }

        $provinceCodeToId = Province::query()->pluck('id', 'psgc_code');
        $rows = $this->readCsv($path);

        $payload = $rows->map(function (array $r) use ($provinceCodeToId) {
            $provinceId = $provinceCodeToId[$r['province_psgc_code']] ?? null;
            if (! $provinceId) {
                return null; // skip unknown mappings
            }

            return [
                'psgc_code' => $r['psgc_code'],
                'province_id' => $provinceId,
                'name' => $r['name'],
                'is_city' => filter_var($r['is_city'] ?? true, FILTER_VALIDATE_BOOL) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->filter();

        foreach ($payload->chunk(1000) as $chunk) {
            City::query()->upsert($chunk->all(), ['psgc_code'], ['province_id', 'name', 'is_city', 'updated_at']);
        }
    }

    protected function seedBarangays(): void
    {
        $path = 'seeds/barangays.csv';
        if (! Storage::exists($path)) {
            $this->command?->warn('barangays.csv not found, skipping barangays.');

            return;
        }

        $cityCodeToId = City::query()->pluck('id', 'psgc_code');
        $rows = $this->readCsv($path);

        $payload = $rows->map(function (array $r) use ($cityCodeToId) {
            $cityId = $cityCodeToId[$r['city_psgc_code']] ?? null;
            if (! $cityId) {
                return null;
            }

            return [
                'psgc_code' => $r['psgc_code'],
                'city_id' => $cityId,
                'name' => $r['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->filter();

        foreach ($payload->chunk(2000) as $chunk) {
            Barangay::query()->upsert($chunk->all(), ['psgc_code'], ['city_id', 'name', 'updated_at']);
        }
    }

    protected function readCsv(string $path): Collection
    {
        $content = Storage::get($path);
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $header = [];
        $rows = collect();

        foreach ($lines as $i => $line) {
            $cols = str_getcsv($line);
            if ($i === 0) {
                $header = $cols;

                continue;
            }
            $rows->push(array_combine($header, $cols));
        }

        return $rows;
    }

    protected function seedSample(): void
    {
        DB::transaction(function (): void {
            // Focus: Bataan complete-looking subset, plus a few nearby provinces to enhance autosuggest realism
            $sample = [
                [
                    'province' => ['code' => '030000000', 'name' => 'Bataan'],
                    'cities' => [
                        ['code' => '030400000', 'name' => 'Balanga City', 'is_city' => 1, 'barangays' => [
                            ['code' => '030401001', 'name' => 'Poblacion'],
                            ['code' => '030401002', 'name' => 'Bagong Silang'],
                            ['code' => '030401003', 'name' => 'Cupang Proper'],
                        ]],
                        ['code' => '030401000', 'name' => 'Abucay', 'is_city' => 0, 'barangays' => [
                            ['code' => '030401101', 'name' => 'Calaylayan'],
                            ['code' => '030401102', 'name' => 'Wawa'],
                        ]],
                        ['code' => '030404000', 'name' => 'Bagac', 'is_city' => 0, 'barangays' => [
                            ['code' => '030404101', 'name' => 'Parang'],
                            ['code' => '030404102', 'name' => 'Pag-asa'],
                        ]],
                        ['code' => '030402000', 'name' => 'Dinalupihan', 'is_city' => 0, 'barangays' => [
                            ['code' => '030402001', 'name' => 'San Ramon'],
                            ['code' => '030402002', 'name' => 'New Dinalupihan'],
                        ]],
                        ['code' => '030403000', 'name' => 'Hermosa', 'is_city' => 0, 'barangays' => [
                            ['code' => '030403001', 'name' => 'A. Rivera'],
                            ['code' => '030403002', 'name' => 'Palihan'],
                        ]],
                        ['code' => '030404500', 'name' => 'Limay', 'is_city' => 0, 'barangays' => [
                            ['code' => '030404501', 'name' => 'Alangan'],
                            ['code' => '030404502', 'name' => 'Kitang 2 & Luz'],
                        ]],
                        ['code' => '030406000', 'name' => 'Mariveles', 'is_city' => 0, 'barangays' => [
                            ['code' => '030406001', 'name' => 'Alion'],
                            ['code' => '030406002', 'name' => 'Batangas II'],
                        ]],
                        ['code' => '030407000', 'name' => 'Morong', 'is_city' => 0, 'barangays' => [
                            ['code' => '030407001', 'name' => 'Poblacion'],
                            ['code' => '030407002', 'name' => 'Sabang'],
                        ]],
                        ['code' => '030408000', 'name' => 'Orani', 'is_city' => 0, 'barangays' => [
                            ['code' => '030408001', 'name' => 'Puksuan'],
                            ['code' => '030408002', 'name' => 'Mulawin'],
                        ]],
                        ['code' => '030405000', 'name' => 'Orion', 'is_city' => 0, 'barangays' => [
                            ['code' => '030405001', 'name' => 'Wakas'],
                            ['code' => '030405002', 'name' => 'Puting Buhangin'],
                        ]],
                        ['code' => '030409000', 'name' => 'Pilar', 'is_city' => 0, 'barangays' => [
                            ['code' => '030409001', 'name' => 'Santa Rosa'],
                            ['code' => '030409002', 'name' => 'Del Pilar'],
                        ]],
                        ['code' => '030410000', 'name' => 'Samal', 'is_city' => 0, 'barangays' => [
                            ['code' => '030410001', 'name' => 'San Roque'],
                            ['code' => '030410002', 'name' => 'Santa Lucia'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '035400000', 'name' => 'Pampanga'],
                    'cities' => [
                        ['code' => '035401000', 'name' => 'Angeles City', 'is_city' => 1, 'barangays' => [
                            ['code' => '035401001', 'name' => 'Balibago'],
                            ['code' => '035401002', 'name' => 'Pampang'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '037100000', 'name' => 'Zambales'],
                    'cities' => [
                        ['code' => '037102000', 'name' => 'Olongapo City', 'is_city' => 1, 'barangays' => [
                            ['code' => '037102001', 'name' => 'East Tapinac'],
                            ['code' => '037102002', 'name' => 'Barretto'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '031400000', 'name' => 'Bulacan'],
                    'cities' => [
                        ['code' => '031412000', 'name' => 'Malolos City', 'is_city' => 1, 'barangays' => [
                            ['code' => '031412001', 'name' => 'Bagong Bayan'],
                            ['code' => '031412002', 'name' => 'Longos'],
                        ]],
                    ],
                ],
                // Extra A- and B- provinces for a richer autosuggest feel
                [
                    'province' => ['code' => '140100000', 'name' => 'Abra'],
                    'cities' => [
                        ['code' => '140101000', 'name' => 'Bangued', 'is_city' => 0, 'barangays' => [
                            ['code' => '140101001', 'name' => 'Poblacion'],
                            ['code' => '140101002', 'name' => 'Zone 5'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '060400000', 'name' => 'Aklan'],
                    'cities' => [
                        ['code' => '060401000', 'name' => 'Kalibo', 'is_city' => 0, 'barangays' => [
                            ['code' => '060401001', 'name' => 'Andagao'],
                            ['code' => '060401002', 'name' => 'Poblacion'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '050500000', 'name' => 'Albay'],
                    'cities' => [
                        ['code' => '050501000', 'name' => 'Legazpi City', 'is_city' => 1, 'barangays' => [
                            ['code' => '050501001', 'name' => 'Bogtong'],
                            ['code' => '050501002', 'name' => 'Bagacay'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '148100000', 'name' => 'Apayao'],
                    'cities' => [
                        ['code' => '148101000', 'name' => 'Kabugao', 'is_city' => 0, 'barangays' => [
                            ['code' => '148101001', 'name' => 'Ekbobbog'],
                            ['code' => '148101002', 'name' => 'Poblacion'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '060600000', 'name' => 'Antique'],
                    'cities' => [
                        ['code' => '060601000', 'name' => 'San Jose de Buenavista', 'is_city' => 0, 'barangays' => [
                            ['code' => '060601001', 'name' => 'Atabay'],
                            ['code' => '060601002', 'name' => 'Barangay 3'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '037700000', 'name' => 'Aurora'],
                    'cities' => [
                        ['code' => '037701000', 'name' => 'Baler', 'is_city' => 0, 'barangays' => [
                            ['code' => '037701001', 'name' => 'Poblacion'],
                            ['code' => '037701002', 'name' => 'Sabang'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '041000000', 'name' => 'Batangas'],
                    'cities' => [
                        ['code' => '041001000', 'name' => 'Batangas City', 'is_city' => 1, 'barangays' => [
                            ['code' => '041001001', 'name' => 'Alangilan'],
                            ['code' => '041001002', 'name' => 'Poblacion'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '020900000', 'name' => 'Batanes'],
                    'cities' => [
                        ['code' => '020901000', 'name' => 'Basco', 'is_city' => 0, 'barangays' => [
                            ['code' => '020901001', 'name' => 'Kayhuvukan'],
                            ['code' => '020901002', 'name' => 'San Antonio'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '141100000', 'name' => 'Benguet'],
                    'cities' => [
                        ['code' => '141101000', 'name' => 'La Trinidad', 'is_city' => 0, 'barangays' => [
                            ['code' => '141101001', 'name' => 'Balili'],
                            ['code' => '141101002', 'name' => 'Poblacion'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '087800000', 'name' => 'Biliran'],
                    'cities' => [
                        ['code' => '087801000', 'name' => 'Naval', 'is_city' => 0, 'barangays' => [
                            ['code' => '087801001', 'name' => 'Atipolo'],
                            ['code' => '087801002', 'name' => 'P.S. Eamiguel'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '153800000', 'name' => 'Basilan'],
                    'cities' => [
                        ['code' => '153801000', 'name' => 'Isabela City', 'is_city' => 1, 'barangays' => [
                            ['code' => '153801001', 'name' => 'Binuangan'],
                            ['code' => '153801002', 'name' => 'Kansanggot'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '071200000', 'name' => 'Bohol'],
                    'cities' => [
                        ['code' => '071201000', 'name' => 'Tagbilaran City', 'is_city' => 1, 'barangays' => [
                            ['code' => '071201001', 'name' => 'Cogon'],
                            ['code' => '071201002', 'name' => 'Poblacion I'],
                        ]],
                    ],
                ],
                [
                    'province' => ['code' => '101300000', 'name' => 'Bukidnon'],
                    'cities' => [
                        ['code' => '101301000', 'name' => 'Malaybalay City', 'is_city' => 1, 'barangays' => [
                            ['code' => '101301001', 'name' => 'Aglayan'],
                            ['code' => '101301002', 'name' => 'Sumpong'],
                        ]],
                    ],
                ],
            ];

            foreach ($sample as $prov) {
                $province = Province::query()->updateOrCreate(
                    ['psgc_code' => $prov['province']['code']],
                    ['name' => $prov['province']['name']]
                );

                foreach ($prov['cities'] as $c) {
                    $city = City::query()->updateOrCreate(
                        ['psgc_code' => $c['code']],
                        [
                            'province_id' => $province->id,
                            'name' => $c['name'],
                            'is_city' => (int) $c['is_city'],
                        ]
                    );

                    foreach ($c['barangays'] as $b) {
                        Barangay::query()->updateOrCreate(
                            ['psgc_code' => $b['code']],
                            [
                                'city_id' => $city->id,
                                'name' => $b['name'],
                            ]
                        );
                    }
                }
            }
        });
    }
}
