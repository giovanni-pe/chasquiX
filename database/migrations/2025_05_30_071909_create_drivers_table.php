<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('license_number', 20)->unique();
            $table->date('license_expiry_date');
            $table->integer('experience_years')->default(0);
            $table->enum('driver_status', ['available', 'busy', 'offline'])->default('offline');
            $table->boolean('documents_verified')->default(false);
            $table->boolean('background_check')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->decimal('total_earnings', 10, 2)->default(0.00);
            $table->integer('completed_trips')->default(0);
            $table->timestamps();

            // Índices críticos para matching en tiempo real
            $table->index(['driver_status', 'documents_verified']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};
