<?php

use App\Models\Enum\LaundryType;
use App\Models\Enum\WashMode;
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
        Schema::create('order_laundry_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('weight')->default(8);
            $table->unsignedTinyInteger('dried_minutes')->default(40);
            $table->unsignedTinyInteger('type')->default(LaundryType::mixed_clothes->value);
            $table->unsignedTinyInteger('wash_mode')->default(WashMode::normal->value);
            $table->boolean('extra_wash')->default(false);
            $table->boolean('extra_rinse')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_laundry_details');
    }
};
