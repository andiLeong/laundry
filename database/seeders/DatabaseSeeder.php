<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Expense;
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
        $branch = \App\Models\Branch::factory()->create([
            'name' => 'Saint paul branch',
            'address' => 'Saint Paul str',
            'born_at' => today(),
        ]);

        $users = \App\Models\User::factory(200)->create();
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
            'discount' => 0.5,
        ]);

        \App\Models\Promotion::factory()->create([
            'name' => 'gift certificate reward',
            'description' => 'give gc to customers',
            'isolated' => 0,
            'start' => now()->subDay(),
            'until' => null,
            'class' => 'App\\Models\\Promotions\\RewardGiftCertificate',
            'discount' => 0,
        ]);

        \App\Models\Promotion::factory()->create([
            'name' => 'Wednesday washer promo',
            'description' => 'customer wash on wednesday gets discount',
            'isolated' => 0,
            'start' => now()->subDay(),
            'until' => null,
            'class' => 'App\Models\Promotions\WednesdayWasher',
            'discount' => 0.1,
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

        Expense::factory(100)->create();
    }
}
