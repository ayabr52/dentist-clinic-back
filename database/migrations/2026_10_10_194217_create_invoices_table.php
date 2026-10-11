<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');

            $table->decimal('total_amount', 10, 2); // المبلغ الإجمالي
            $table->decimal('discount', 10, 2)->default(0.00); // الخصم
            $table->decimal('final_amount', 10, 2); // المبلغ النهائي
            $table->decimal('paid_amount', 10, 2)->default(0.00); // قديش دفع لغاية الآن

            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
