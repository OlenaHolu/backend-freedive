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
        $table->id('DiveId');

        $table->timestamp('StartTime');
        $table->integer('Duration');
        $table->decimal('MaxDepth', 8, 2);
        
        $table->smallInteger('SampleInterval')->nullable();
        $table->smallInteger('Mode')->default(3);
        $table->text('Source')->nullable();
        $table->text('Note')->nullable();
        $table->smallInteger('StartTemperature')->nullable();
        $table->smallInteger('BottomTemperature')->nullable();
        $table->smallInteger('EndTemperature')->nullable();
        $table->smallInteger('AltitudeMode')->nullable();
        $table->smallInteger('PersonalMode')->nullable();
        $table->integer('DiveNumberInSerie')->nullable();
        $table->integer('SurfaceTime')->nullable();
        $table->integer('SurfacePressure')->nullable();
        $table->decimal('PreviousMaxDepth', 8, 2)->nullable();
        $table->integer('DiveTime')->nullable();
        $table->boolean('Deleted')->default(false);
        $table->double('Weight')->nullable();
        $table->integer('Weather')->nullable();
        $table->integer('Visibility')->nullable();
        $table->double('AvgDepth')->nullable();
        $table->text('Software')->nullable();
        $table->string('SerialNumber')->default('');
        $table->integer('TimeFromReset')->nullable();
        $table->double('Battery')->nullable();
        $table->double('LastDecoStopDepth')->default(3.0);
        $table->smallInteger('AscentMode')->default(0);

        $table->timestamps();

        $table->foreignId('user_id')->constrained()->onDelete('cascade');
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
