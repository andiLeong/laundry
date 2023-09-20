<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('branch_id');
            $table->dateTime('from');
            $table->dateTime('to');
            $table->date('date');
            $table->boolean('late')->default(false);
            $table->boolean('absence')->default(false);
            $table->boolean('early_leave')->default(false);
            $table->boolean('reviewed')->default(false);
        });

        // 2023-09-12 22:00 - 6:00
        // 2023-09-13 off

        // 2023-09-14 22:00 - 6:00
        // punchin 20:00 21：00

        // how to calcualte as late
        //if punch in record is found between the last shift end date and current shift start date --- no late, otherwise late

        //if punchin between shift start and end mark as late, else find from last shift end and this shift start
        //if no punch in from last shift end and this shift start , possible absence
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
