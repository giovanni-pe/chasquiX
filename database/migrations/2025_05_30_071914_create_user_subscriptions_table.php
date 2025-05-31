<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'suspended', 'cancelled', 'expired']);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->decimal('monthly_price', 8, 2);
            $table->decimal('discount_applied', 5, 2)->default(0.00);
            $table->enum('payment_method', ['yape', 'plin', 'card', 'cash']);
            $table->boolean('auto_renewal')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
