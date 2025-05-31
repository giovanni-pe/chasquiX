<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->enum('vehicle_type', ['motorcycle', 'car', 'collective', 'mototaxi']);
            $table->string('brand', 50);
            $table->string('model', 50);
            $table->year('year');
            $table->string('license_plate', 10)->unique();
            $table->string('color', 30);
            $table->tinyInteger('passenger_capacity');
            $table->boolean('insurance_valid')->default(false);
            $table->date('insurance_expiry')->nullable();
            $table->boolean('technical_review')->default(false);
            $table->date('technical_review_expiry')->nullable();
            $table->enum('vehicle_status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->string('vehicle_photo_url')->nullable();
            $table->timestamps();

            $table->index(['vehicle_type', 'vehicle_status']);
            $table->index('driver_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
