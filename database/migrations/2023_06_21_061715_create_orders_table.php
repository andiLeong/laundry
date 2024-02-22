<?php

use App\Models\Enum\OrderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id')->index();
            $table->unsignedTinyInteger('type')->index()->default(OrderType::WALKIN->value);
//            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id')->index()->nullable();
//            $table->unsignedBigInteger('company_id')->nullable();
//            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('description')->nullable();
            $table->unsignedDecimal('amount');
            $table->unsignedDecimal('total_amount');
            $table->unsignedBigInteger('product_amount')->default(0);
            $table->unsignedTinyInteger('paid')->default(1);
            $table->unsignedTinyInteger('payment')->default(1);
            $table->unsignedTinyInteger('issued_invoice')->default(0);
            $table->unsignedBigInteger('creator_id');
            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
