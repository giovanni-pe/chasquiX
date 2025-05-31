<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('avatar_url')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F', 'Other'])->nullable();
            $table->text('bio')->nullable();
            $table->json('emergency_contacts')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
};
