<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('dives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamp('StartTime');
            $table->integer('Duration');
            $table->decimal('MaxDepth', 8, 2);
            $table->decimal('AvgDepth', 8, 2)->nullable();
            $table->smallInteger('SampleInterval')->nullable();
            $table->decimal('PreviousMaxDepth', 8, 2)->nullable();
            $table->integer('DiveTime')->nullable();
            $table->integer('DiveNumberInSerie')->nullable();

            $table->decimal('StartTemperature', 5, 2)->nullable();
            $table->decimal('BottomTemperature', 5, 2)->nullable();
            $table->decimal('EndTemperature', 5, 2)->nullable();

            $table->integer('SurfaceTime')->nullable();
            $table->integer('SurfacePressure')->nullable();
            $table->smallInteger('AltitudeMode')->nullable();

            $table->text('Source')->nullable();
            $table->smallInteger('Mode')->default(3);
            $table->text('Note')->nullable();
            $table->smallInteger('PersonalMode')->nullable();
            $table->string('SerialNumber')->default('');

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dives');
    }
};
