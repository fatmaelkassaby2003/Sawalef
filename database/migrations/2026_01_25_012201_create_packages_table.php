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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الباقة
            $table->text('description')->nullable(); // وصف الباقة
            $table->integer('gems'); // عدد الجواهر
            $table->decimal('price', 10, 2); // السعر بالجنيه المصري
            $table->boolean('is_active')->default(true); // هل الباقة نشطة
            $table->integer('order')->default(0); // ترتيب العرض
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
