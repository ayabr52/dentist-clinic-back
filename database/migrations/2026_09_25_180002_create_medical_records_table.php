<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');     // المريض
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');      // الطبيب المعالج
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null'); // الموعد (إن وجد)

            $table->text('diagnosis');              // التشخيص الطبي
            $table->text('prescription')->nullable(); // الوصفة الطبية (الأدوية والجرعات)
            $table->text('doctor_notes')->nullable(); // ملاحظات خاصة للطبيب

            // حقل مقترح لتحليل الذكاء الاصطناعي (لو حبينا نربطه بسكربت البايثون لاحقاً محلياً)
            $table->text('ai_analysis_result')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
