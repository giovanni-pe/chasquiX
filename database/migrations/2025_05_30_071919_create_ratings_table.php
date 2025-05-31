<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rated_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->text('comment')->nullable();
            $table->enum('rating_type', ['passenger_to_driver', 'driver_to_passenger']);
            $table->timestamps();

            // Prevenir calificaciones duplicadas
            $table->unique(['trip_id', 'rater_id', 'rating_type']);
            $table->index(['rated_id', 'rating_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
};
