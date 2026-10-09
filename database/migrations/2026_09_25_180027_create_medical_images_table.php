<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->onDelete('cascade'); // السجل الطبي المرتبط

            $table->string('image_path');           // مسار تخزين الصورة على السيستم
            $table->string('image_type')->default('xray'); // نوع الصورة (مثل: xray, panorama, prescription_scan)
            $table->string('title')->nullable();    // عنوان توضيحي للصورة

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
};
