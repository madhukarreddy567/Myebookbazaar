<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Static dataset — no external API calls. ~249 countries.
        $countries = require __DIR__.'/data/countries.php';

        foreach ($countries as [$iso, $name, $phone]) {
            Country::updateOrCreate(
                ['iso_code' => $iso],
                ['name' => $name, 'phone_code' => $phone]
            );
        }
    }
}
