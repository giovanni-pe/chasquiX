<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->enum('target_user', ['passenger', 'driver', 'both']);
            $table->decimal('monthly_price', 8, 2);
            $table->decimal('annual_price', 8, 2)->nullable();
            $table->integer('max_trips_per_month')->default(-1); // -1 = unlimited
            $table->decimal('trip_discount_percentage', 5, 2)->default(0.00);
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->boolean('priority_requests')->default(false);
            $table->boolean('premium_support')->default(false);
            $table->boolean('advanced_analytics')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['target_user', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
};
