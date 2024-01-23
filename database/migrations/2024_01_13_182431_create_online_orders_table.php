<?php

use App\Models\Enum\OnlineOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('address_id');
            $table->unsignedTinyInteger('status')->default(OnlineOrderStatus::PENDING_PICKUP->value);
            $table->unsignedTinyInteger('delivery_fee')->default(0);
            $table->timestamp('delivery')->nullable();
            $table->timestamp('pickup');
            $table->timestamp('pickup_at')->nullable();
            $table->timestamp('deliver_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_orders');
    }
};
