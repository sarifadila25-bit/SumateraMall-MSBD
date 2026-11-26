<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\City;
use App\Models\Mall;
use App\Models\Province;
use App\Models\Store;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Signature('app:import-sumatera-mall-data')]
#[Description('Import data RDF SumateraMall ke PostgreSQL')]
class ImportSumateraMallData extends Command
{
    public function handle(): int
    {
        $path = storage_path('app/rdf_import.json');

        if (! file_exists($path)) {
            $this->error('File rdf_import.json tidak ditemukan.');

            return self::FAILURE;
        }

        try {
            $data = json_decode(
                file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $counts = DB::transaction(function () use ($data) {

                // 1. Import provinsi
                $provinceByUri = [];

                foreach ($data['provinces'] as $item) {
                    $province = Province::updateOrCreate(
                        ['resource_uri' => $item['resource_uri']],
                        ['name' => $item['name']]
                    );

                    $provinceByUri[$item['resource_uri']] = $province;
                }

                // 2. Import kota
                $cityByUri = [];
                $cityByName = [];

                foreach ($data['cities'] as $item) {
                    $province = $provinceByUri[$item['province_uri']] ?? null;

                    if (! $province) {
                        throw new RuntimeException(
                            "Provinsi tidak ditemukan: {$item['province_uri']}"
                        );
                    }

                    $city = City::updateOrCreate(
                        ['resource_uri' => $item['resource_uri']],
                        [
                            'name' => $item['name'],
                            'province_id' => $province->id,
                        ]
                    );

                    $cityByUri[$item['resource_uri']] = $city;
                    $cityByName[strtolower(trim($item['name']))] = $city;
                }

                // 3. Alias URI kota dari namespace ns1
                $cityUriAliases = [
                    'http://sumateramall.org/resource/Kota_Palembang' => 'Palembang',
                ];

                // 4. Import mall
                $mallByUri = [];

                foreach ($data['malls'] as $item) {
                    $city = $cityByUri[$item['city_uri']] ?? null;

                    if (! $city && isset($cityUriAliases[$item['city_uri']])) {
                        $cityName = strtolower(
                            trim($cityUriAliases[$item['city_uri']])
                        );

                        $city = $cityByName[$cityName] ?? null;
                    }

                    if (! $city) {
                        throw new RuntimeException(
                            "Kota mall tidak ditemukan: {$item['name']}"
                        );
                    }

                    $mall = Mall::updateOrCreate(
                        ['resource_uri' => $item['resource_uri']],
                        [
                            'city_id' => $city->id,
                            'name' => $item['name'],
                            'address' => $item['address'],
                            'opening_hours' => $item['opening_hours'],
                            'floors' => $item['floors'],
                            'category' => $item['category'],
                            'area' => $item['area'],
                            'website' => $item['website'],
                            'description' => $item['description'],
                            'source_namespace' => $item['source_namespace'],
                        ]
                    );

                    $mallByUri[$item['resource_uri']] = $mall;
                }

                // 5. Import toko
                $storeByUri = [];

                foreach ($data['stores'] as $item) {
                    $mall = $mallByUri[$item['mall_uri']] ?? null;

                    if (! $mall) {
                        throw new RuntimeException(
                            "Mall toko tidak ditemukan: {$item['name']}"
                        );
                    }

                    $store = Store::updateOrCreate(
                        ['resource_uri' => $item['resource_uri']],
                        [
                            'mall_id' => $mall->id,
                            'name' => $item['name'],
                            'category_text' => $item['category_text'],
                            'logo_url' => $item['logo_url'],
                            'location' => $item['location'],
                            'description' => $item['description'],
                            'status' => $item['status'],
                            'opening_hours' => $item['opening_hours'],
                            'phone' => $item['phone'],
                            'rating' => $item['rating'],
                            'source_namespace' => $item['source_namespace'],
                        ]
                    );

                    $storeByUri[$item['resource_uri']] = $store;
                }

                // 6. Import kategori
                $categoryByUri = [];

                foreach ($data['categories'] as $item) {
                    $category = Category::updateOrCreate(
                        ['resource_uri' => $item['resource_uri']],
                        ['name' => $item['name']]
                    );

                    $categoryByUri[$item['resource_uri']] = $category;
                }

                // 7. Import relasi toko-kategori
                $categoryIdsByStoreUri = [];

                foreach ($data['store_categories'] as $item) {
                    $store = $storeByUri[$item['store_uri']] ?? null;
                    $category = $categoryByUri[$item['category_uri']] ?? null;

                    if (! $store || ! $category) {
                        throw new RuntimeException(
                            'Relasi toko-kategori tidak valid.'
                        );
                    }

                    $categoryIdsByStoreUri[$item['store_uri']][] = $category->id;
                }

                foreach ($storeByUri as $uri => $store) {
                    $store->categories()->sync(
                        $categoryIdsByStoreUri[$uri] ?? []
                    );
                }

                return [
                    'provinces' => count($provinceByUri),
                    'cities' => count($cityByUri),
                    'malls' => count($mallByUri),
                    'stores' => count($storeByUri),
                    'categories' => count($categoryByUri),
                    'store_categories' => count($data['store_categories']),
                ];
            });

            $this->info('Import data SumateraMall berhasil!');

            foreach ($counts as $name => $total) {
                $this->line(ucwords(str_replace('_', ' ', $name)).": {$total}");
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Import gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
