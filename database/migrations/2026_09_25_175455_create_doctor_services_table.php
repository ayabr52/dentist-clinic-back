<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade'); // الطبيب
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade'); // الخدمة
            $table->decimal('custom_price', 10, 2)->nullable(); // سعر مخصص لهذه الخدمة عند هذا الطبيب (اختياري)
            $table->timestamps();

            // منع تكرار ربط نفس الخدمة لنفس الطبيب مرتين
            $table->unique(['doctor_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_services');
    }
};
