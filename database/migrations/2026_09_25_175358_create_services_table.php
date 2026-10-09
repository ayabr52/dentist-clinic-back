<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الخدمة (مثل: تنظيف أسنان، حشو عصب، استشارة عامة)
            $table->text('description')->nullable(); // وصف مبسط للخدمة
            $table->decimal('price', 10, 2); // السعر الافتراضي للخدمة
            $table->integer('duration_minutes')->default(30); // المدة الزمنية المتوقعة بالدقائق لحجز الموعد
            $table->boolean('is_active')->default(true); // لتفعيل أو إيقاف الخدمة
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
