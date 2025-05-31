<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null');

            // Estados del viaje
            $table->enum('trip_status', [
                'requested', 'accepted', 'driver_arriving', 'in_progress',
                'completed', 'cancelled_by_passenger', 'cancelled_by_driver', 'no_driver_found'
            ])->default('requested');

            $table->enum('trip_type', ['individual', 'shared', 'collective'])->default('individual');

            // Ubicaciones (crítico para el negocio)
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->text('pickup_address');
            $table->decimal('destination_latitude', 10, 8);
            $table->decimal('destination_longitude', 11, 8);
            $table->text('destination_address');

            // Tiempos (para métricas y SLA)
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_duration')->nullable(); // minutes
            $table->integer('actual_duration')->nullable(); // minutes

            // Precios y pagos
            $table->decimal('base_fare', 8, 2)->nullable();
            $table->decimal('final_fare', 8, 2)->nullable();
            $table->decimal('discount_applied', 8, 2)->default(0.00);
            $table->decimal('chasqui_commission', 8, 2)->default(0.00);
            $table->enum('payment_method', ['cash', 'yape', 'plin', 'card'])->default('cash');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');

            // Información adicional
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->tinyInteger('passenger_count')->default(1);
            $table->text('passenger_notes')->nullable();
            $table->text('driver_notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            // Índices críticos para performance
            $table->index(['trip_status', 'requested_at']);
            $table->index(['passenger_id', 'trip_status']);
            $table->index(['driver_id', 'trip_status']);
            $table->index(['requested_at', 'trip_status']);

            // Índice geoespacial para búsquedas por ubicación
            $table->index(['pickup_latitude', 'pickup_longitude']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trips');
    }
};
