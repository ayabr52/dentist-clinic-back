<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade'); // الموعد المرتبط بالفاتورة
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');     // المريض (لتسهيل التقارير المالية)

            $table->decimal('amount', 10, 2);       // المبلغ الأساسي
            $table->decimal('discount', 10, 2)->default(0.00); // قيمة الخصم إن وجد
            $table->decimal('final_amount', 10, 2); // المبلغ النهائي الواجب دفعه

            // طريقة الدفع وحالة الدفع
            $table->string('payment_method')->default('cash'); // نقداً، أو أي طريقة أخرى
            $table->enum('status', ['paid', 'partial', 'pending'])->default('pending');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
