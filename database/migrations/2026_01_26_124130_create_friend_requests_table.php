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
        Schema::create('friend_requests', function (Blueprint $綱) {
            $綱->id();
            $綱->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $綱->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $綱->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $綱->timestamps();

            $綱->unique(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friend_requests');
    }
};
