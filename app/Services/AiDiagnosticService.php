<?php

/**
 * خدمة تحليل الصور الطبية باستخدام الذكاء الاصطناعي.
 * هذه الخدمة تتواصل مع خدمة بايثون (FastAPI) لتحليل الصور الطبية مثل الأشعة السينية.
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiDiagnosticService
{
    protected string $serviceUrl;

    public function __construct()
    {
        // جلب رابط خدمة بايثون من ملف الإعدادات أو ملف البيئة .env
        $this->serviceUrl = config('services.ai.url', 'http://ai-service:8001/api/v1/analyze-image');
    }

    /**
     * إرسال الصورة إلى خدمة FastAPI للتحليل
     *
     * @param  string  $path  مسار الصورة المخزنة محلياً (ضمن قرص private)
     * @return string|null نتيجة التحليل بصيغة JSON أو null عند الفشل
     */
    public function analyze(string $path): ?string
    {
        try {
            // التأكد من أن الملف موجود على القرص
            if (! Storage::disk('private')->exists($path)) {
                Log::error('AI Service Error: Image file not found at path: '.$path);

                return null;
            }

            // قراءة محتوى الملف الثنائي (Binary content)
            $fileContent = Storage::disk('private')->get($path);
            $fileName = basename($path);

            // إرسال طلب HTTP من نوع Multipart عبر Laravel Http Client
            $response = Http::timeout(30)
                ->attach('file', $fileContent, $fileName)
                ->post($this->serviceUrl);

            if ($response->successful()) {
                // إرجاع النتيجة كنص JSON لتخزينها في قاعدة البيانات
                return json_encode($response->json(), JSON_UNESCAPED_UNICODE);
            }

            Log::error('AI Service Failed with status code '.$response->status().': '.$response->body());

            return null;

        } catch (\Exception $e) {
            Log::error('AI Service Connection Exception: '.$e->getMessage());

            return null;
        }
    }
}
