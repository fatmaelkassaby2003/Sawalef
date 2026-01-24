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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'package_purchase']); // نوع العملية
            $table->decimal('amount', 10, 2); // المبلغ
            $table->decimal('balance_before', 10, 2); // الرصيد قبل العملية
            $table->decimal('balance_after', 10, 2); // الرصيد بعد العملية
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable(); // طريقة الدفع (card, vodafone, fawry, etc)
            $table->string('fawaterak_invoice_id')->nullable(); // معرف الفاتورة من فواتيرك
            $table->string('reference_number')->unique()->nullable(); // رقم مرجعي
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
