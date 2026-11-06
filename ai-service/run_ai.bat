@echo off
echo ==========================================
echo    Starting Clinic AI Service (FastAPI)
echo ==========================================

:: الانتقال إلى قرص E ومجلد بايثون
E:
cd E:\php_Projects\React-Project\Clinc_Project\back\clinic-system\ai-service

:: تفعيل بيئة الأكوندا clinic-ai وتشغيل السيرفر
call C:\Users\Lenovo\anaconda3\Scripts\activate.bat clinic-ai
uvicorn main:app --reload --port 8001

pause