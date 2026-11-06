<?php

namespace App\Services;

use App\Models\MedicalRecord;
// استدعاء خدمة الـ AI
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MedicalRecordService
{
    protected AiDiagnosticService $aiService;

    // حقن خدمة الذكاء الاصطناعي عبر الـ Constructor
    public function __construct(AiDiagnosticService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function storeRecord(array $data, int $doctorId, array $images = []): MedicalRecord
    {
        $storedFilePaths = []; // مصفوفة لتتبع المسارات في حال احتجنا لحذفها عند الفشل

        try {
            // إذا تم إرفاق صور، نقوم بإرسال الصورة الأولى لخدمة الـ AI لتحليلها والحصول على النتيجة مسبقاً
            if (! empty($images)) {
                $primaryImage = $images[0];

                // استدعاء خدمة بايثون (FastAPI) عبر الـ Service
                $aiResult = $this->aiService->analyze($primaryImage);
                if ($aiResult) {
                    $data['ai_analysis_result'] = $aiResult;
                }
            }

            return DB::transaction(function () use ($data, $doctorId, $images, &$storedFilePaths) {

                /** @var MedicalRecord $record */
                $record = MedicalRecord::create([
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $doctorId,
                    'appointment_id' => $data['appointment_id'] ?? null,
                    'diagnosis' => $data['diagnosis'],
                    'prescription' => $data['prescription'] ?? null,
                    'doctor_notes' => $data['doctor_notes'] ?? ($data['notes'] ?? null),
                    'ai_analysis_result' => $data['ai_analysis_result'] ?? null,
                ]);

                if (! empty($images)) {
                    foreach ($images as $imageFile) {
                        // تخزين الملف على قرص خاص (local) لحمايته من الوصول المباشر
                        $path = $imageFile->store('medical_records', 'local');

                        // نحفظ المسار لنستطيع حذفه لو حدث خطأ لاحقاً
                        $storedFilePaths[] = $path;

                        $record->images()->create([
                            'image_path' => $path,
                            'image_type' => $data['image_type'] ?? 'xray',
                            'title' => $data['image_title'] ?? null,
                        ]);
                    }
                }

                return $record->load(['patient', 'doctor', 'appointment', 'images']);
            });

        } catch (\Exception $e) {
            // إذا فشلت المعاملة لأي سبب، نقوم بحذف الملفات التي تم رفعها من القرص لمنع تراكم الملفات اليتيمة
            foreach ($storedFilePaths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }

            // إعادة رمي الاستثناء ليتعامل معه الـ Controller
            throw $e;
        }
    }

    public function getPatientRecords($user, ?int $patientId = null)
    {
        $query = MedicalRecord::with(['patient', 'doctor', 'appointment', 'images'])
            ->latest();

        if ($user->hasRole('patient')) {
            return $query->where('patient_id', $user->id)->get();
        }

        if ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        }

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        return $query->get();
    }
}
