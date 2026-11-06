FROM python:3.10-slim

WORKDIR /app

# تثبيت الحزم الأساسية لنظام التشغيل التي تحتاجها مكتبة OpenCV لمعالجة الصور
RUN apt-get update && apt-get install -y \
    libgl1-mesa-glx \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

# نسخ ملف التبعيات وتثبيتها أولاً للاستفادة من الـ Docker Cache
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# نسخ باقي ملفات تطبيق بايثون
COPY . .

EXPOSE 8001

# تشغيل سيرفر FastAPI باستخدام uvicorn
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001"]
