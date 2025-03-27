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
        Schema::create('dive_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dive_id')->constrained()->onDelete('cascade'); 
            $table->integer('time'); // seconds
            $table->decimal('depth', 8, 2)->nullable(); // metros
            $table->decimal('temperature', 5, 2)->nullable(); // °C
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dive_samples');
    }
};
