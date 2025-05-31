<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained('user_subscriptions')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->decimal('chasqui_commission', 10, 2)->default(0.00);
            $table->decimal('driver_amount', 10, 2)->default(0.00);
            $table->enum('payment_type', ['trip', 'subscription', 'penalty', 'bonus']);
            $table->enum('payment_method', ['cash', 'yape', 'plin', 'card']);
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded']);
            $table->string('external_reference')->nullable(); // Para integraciones con pasarelas
            $table->json('payment_details')->nullable(); // Para metadata adicional
            $table->timestamps();

            $table->index(['user_id', 'payment_status']);
            $table->index(['payment_type', 'payment_status']);
            $table->index('external_reference');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
