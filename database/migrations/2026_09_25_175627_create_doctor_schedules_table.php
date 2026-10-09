<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0 (الأحد) إلى 6 (السبت) أو حسب نظام الأيام المعتمد
            $table->time('start_time'); // ساعة بداية الدوام (مثال: 09:00:00)
            $table->time('end_time');   // ساعة نهاية الدوام (مثال: 17:00:00)
            $table->boolean('is_working_day')->default(true); // لتعطيل يوم معين بسرعة بدون حذف السجل
            $table->timestamps();

            // منع تكرار جدول نفس اليوم لنفس الطبيب
            $table->unique(['doctor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
