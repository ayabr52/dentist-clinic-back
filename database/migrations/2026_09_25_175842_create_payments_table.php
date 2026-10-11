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
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade'); // ربط الدفعة بالفاتورة الأساسية
            $table->decimal('amount', 10, 2); // المبلغ المدفوع بهذه العملية بالذات
            $table->string('payment_method')->default('cash');
            $table->foreignId('collected_by')->constrained('users'); // الموظف الذي قبض المبلغ
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
