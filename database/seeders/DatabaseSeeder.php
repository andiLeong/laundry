<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = \App\Models\User::factory(10)->create();
        \App\Models\Service::factory()->create([
            'name' => 'Full service',
            'price' => 200
        ]);

        \App\Models\Service::factory()->create([
            'name' => 'Washer',
            'price' => 65
        ]);

        \App\Models\Promotion::factory()->create([
            'name' => 'Welcome Discount',
            'description' => 'for new sign up users',
            'isolated' => 1,
            'start' => now()->subDay(),
            'until' => null,
            'class' => 'App\\Models\\Promotions\\SignUpDiscount',
        ]);

        $users->each(function ($user) {
            $service = Service::find(rand(1,2));
            Order::factory()->create([
                'user_id' => $user->id,
                'amount' => $service->price,
                'service_id' => $service->id,
                'created_at' => now()->subDays(rand(1,7)),
            ]);
        });

    }
}
