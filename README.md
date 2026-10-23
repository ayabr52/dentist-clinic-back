# 🏥 Enterprise Clinic Management System - Backend (Laravel 12 API)

A robust, enterprise-grade RESTful API built with **Laravel 12**, designed to streamline clinic workflows, doctor scheduling, multi-role RBAC security, precise financial processing, and patient appointment lifecycles.

---

## 🌟 Key Architecture & Highlights
- **Unified User Architecture & RBAC:** Powered by **Spatie Laravel-Permission** with a single `User` model supporting guard-specific roles (`Admin`, `Doctor`, `Patient`).
- **Concurrency-Safe Financials:** Cent-based rounding and database locking (`lockForUpdate()`) to prevent race conditions during partial/full payments.
- **Strict Data Integrity:** FormRequests, Policies, and strict database foreign-key constraints preventing unauthorized cross-patient data access.
- **Automated CI/CD:** Fully integrated GitHub Actions workflow running automated Pest/PHPUnit tests and code style checks.

---

## 🛠️ Tech Stack
- **Framework:** Laravel 12.x
- **PHP:** ^8.2
- **Database:** SQLite (CI/Testing) / MySQL / PostgreSQL (Production)
- **Authentication:** Laravel Sanctum
- **Authorization:** Spatie Laravel-Permission

---

## 🚀 Getting Started & Installation

### 1. Prerequisites
- PHP >= 8.2 & Composer
- SQLite or MySQL

### 2. Setup Guide
```bash
# Clone the repository
git clone [https://github.com/ayabr52/clinic-system-backend.git](https://github.com/ayabr52/clinic-system-backend.git)

# Navigate into the project directory
cd clinic-system-backend

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seeders with complete demo data
php artisan migrate:fresh --seed

# Start local server
php artisan serve

The API will be accessible at: http://127.0.0.1:8000/api/v1

🔑 Seeded Demo Credentials
Role            Email        Password
Admin          admin@clinic.com    password123
Doctor         doctor@clinic.com    password123
Patient        patient@clinic.com    password123

🧪 Running Tests
php artisan test

📌 Author
Aya Barghouth (آيه برغوث )

Full-Stack & Backend Software Engineer
