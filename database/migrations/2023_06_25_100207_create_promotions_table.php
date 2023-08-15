<?php

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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name',100);
            $table->text('description');
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedTinyInteger('isolated')->default(0);
            $table->dateTime('start');
            $table->dateTime('until')->nullable();
            $table->decimal('discount',3)->default(0);
            $table->string('class');
            $table->string('image')->nullable();
            $table->string('thumbnail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
