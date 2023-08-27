<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
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

        \App\Models\Product::factory()->create([
            'name' => 'Detergent',
            'stock' => 100,
            'price' => 50,
        ]);

        \App\Models\Product::factory()->create([
            'name' => 'Conditioner',
            'stock' => 100,
            'price' => 70,
        ]);

        \App\Models\Product::factory(10)->create();
        $users = \App\Models\User::factory(200)->create();
        User::first()->update(['phone' => '09272714285']);
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
            'image' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-4-big.jpeg',
            'thumbnail' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-4.png',
        ]);

        \App\Models\Promotion::factory()->create([
            'name' => 'gift certificate reward',
            'description' => 'give gc to customers',
            'isolated' => 0,
            'start' => now()->subDay(),
            'until' => null,
            'class' => 'App\\Models\\Promotions\\RewardGiftCertificate',
            'discount' => 0,
            'image' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-3-big.jpeg',
            'thumbnail' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-3.png',
        ]);

        \App\Models\Promotion::factory()->create([
            'name' => 'Wednesday washer promo',
            'description' => 'customer wash on wednesday gets discount',
            'isolated' => 0,
            'start' => now()->subDay(),
            'until' => null,
            'class' => 'App\Models\Promotions\WednesdayWasher',
            'discount' => 0.1,
            'image' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-2-big.jpeg',
            'thumbnail' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-2.png',
        ]);

        \App\Models\Promotion::factory(20)->create([
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
