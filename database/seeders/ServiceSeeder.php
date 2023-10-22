<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::factory()->create([
            'name' => 'Full service up to 8kg',
            'slug' => Str::slug('full service up to 8kg'),
            'price' => 170,
            'full_service' => 1,
            'up_to' => 8,
            'description' => 'Wash, Dry, Fold: Enjoy the convenience of our full-service package. Simply drop off your laundry, and we\'ll take care of the rest. ',
        ]);

        Service::factory()->create([
            'name' => 'Full service up to 10kg',
            'slug' => Str::slug('full service up to 10kg'),
            'price' => 210,
            'full_service' => 1,
            'up_to' => 10,
            'description' => 'Wash, Dry, Fold: at our max capacity just 40 peso more.',
        ]);

        Service::factory()->create([
            'name' => 'Full service up to 7kg',
            'slug' => Str::slug('full service up to 7kg'),
            'price' => 160,
            'full_service' => 1,
            'up_to' => 7,
            'description' => 'Don\'t always get 8kg to wash, no worry, we are 10 peso off if your clothes is lower than 8kg',
        ]);

        Service::factory()->create([
            'name' => 'Self service wash up to 8kg',
            'slug' => Str::slug('wash up to 8kg'),
            'price' => 60,
            'full_service' => 0,
            'up_to' => 8,
            'description' => 'Want to be in control? Use our self-service washers with high speed wifi'
        ]);

        Service::factory()->create([
            'name' => 'Self service dry up to 8kg',
            'slug' => Str::slug('dry up to 8kg'),
            'price' => 60,
            'full_service' => 0,
            'up_to' => 8,
            'description' => 'You can take charge of your dryer even folding it your way.'
        ]);

        Service::factory()->create([
            'name' => 'Self service wash up to 10kg',
            'slug' => Str::slug('wash up to 10kg'),
            'price' => 80,
            'full_service' => 0,
            'up_to' => 10,
            'description' => 'add 20 peso you can get all your 10kg dirty clothes clean'
        ]);

        Service::factory()->create([
            'name' => 'Self service dry up to 10kg',
            'slug' => Str::slug('dry up to 10kg'),
            'price' => 80,
            'full_service' => 0,
            'up_to' => 10,
            'description' => 'just 20 peso more you can add 2kg more while enjoy undisrupted wifi'
        ]);
    }
}
