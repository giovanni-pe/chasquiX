<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('real_time_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable(); // GPS accuracy in meters
            $table->decimal('speed', 8, 2)->nullable(); // km/h
            $table->decimal('bearing', 8, 2)->nullable(); // degrees
            $table->boolean('is_driver_available')->default(false);
            $table->foreignId('current_trip_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->timestamp('location_timestamp')->useCurrent();
            $table->timestamps();

            // Índices para consultas en tiempo real
            $table->index(['user_id', 'location_timestamp']);
            $table->index(['latitude', 'longitude', 'is_driver_available']);
            $table->index('current_trip_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('real_time_locations');
    }
};
