<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 200);
            $table->text('message');
            $table->enum('notification_type', ['trip', 'payment', 'promotion', 'system', 'subscription']);
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['notification_type', 'sent_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_notifications');
    }
};
