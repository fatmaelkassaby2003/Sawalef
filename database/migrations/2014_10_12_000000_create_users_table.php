<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('nickname')->nullable();
            $table->integer('age')->nullable();
            $table->string('country')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('profile_image')->nullable();
            $table->string('last_otp')->nullable();
            $table->timestamp('last_otp_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
