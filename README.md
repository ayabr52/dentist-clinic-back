# 🏥 Clinic Management System - Backend (Laravel 11 API)

A robust, enterprise-grade RESTful API built with **Laravel 11**, designed to streamline clinic workflows, doctor scheduling, service management, and patient appointments with strict medical data integrity.

---

## 🌟 Key Features

* **Unified User Architecture & RBAC:** Multi-role user management (`Admin`, `Doctor`, `Patient`) using **Spatie Laravel-Permission** on a unified `User` model for maximum efficiency.
* **Doctor-Service Association:** Pivot relationship allowing flexible doctor-to-service mapping with support for **custom pricing** per doctor.
* **Batch Scheduling:** Dynamic doctor schedule creation supporting whole-week shift definitions in a single atomic payload.
* **Doctor Leave Management:** Leave handling linked seamlessly with user audit models.
* **Audit Trails & Data Integrity:** Implemented `SoftDeletes` across core medical and user records.
* **Sanctum Authentication:** Secure token-based API authentication.
* **Comprehensive Testing:** Postman collection configured with automated dynamic test scripts for seamless variable handling (`token`, `user_id`).

---

## 🛠️ Tech Stack & Dependencies

* **Framework:** Laravel 11.x
* **PHP:** ^8.2
* **Database:** SQLite / PostgreSQL / MySQL
* **Authentication:** Laravel Sanctum
* **Authorization:** Spatie Laravel-Permission
* **API Documentation:** OpenAPI / PHPDoc standard

---

## 🚀 Getting Started

### 1. Prerequisites
* PHP >= 8.2
* Composer
* SQLite or MySQL

### 2. Installation & Setup

```bash
# Clone the repository
git clone [https://github.com/ayabr52/clinic-system-backend.git](https://github.com/ayabr52/clinic-system-backend.git)

# Navigate into the project directory
cd clinic-system-backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

3. Database & Seeding

Run fresh migrations along with database seeders to populate initial roles, admin, doctor, patient users, and services:

Bashphp artisan migrate:fresh --seed

4. Running the ApplicationBashphp artisan serve
The API will be accessible at: http://127.0.0.1:8000/api/v1

🔑 Default Credentials (Seeded)
Role             Email             Password
Admin        admin@clinic.com      password123 
Doctor        doctor@clinic.com    password123
Patient       patient@clinic.com   password123

📄 Postman Collection & TestingA complete Postman collection is included under the /postman directory (or ready for import):
Auto-stores token and user_id upon successful login.
Endpoints structured for Auth, Services, Doctor Schedules, Doctor Leaves, Appointments, Medical Records, and Payments.
📌 Author
Aya Barghouth 
