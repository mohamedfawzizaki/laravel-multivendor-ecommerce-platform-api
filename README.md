# Laravel Multi-Vendor E-commerce Platform API

A robust and scalable RESTful API built with Laravel for powering a multi-vendor e-commerce platform. It supports vendor onboarding, product management, inventory, order processing, settlements, and more.

## 🚀 Features

- 🔐 Secure multi-vendor authentication & authorization (Laravel Sanctum / Passport)
- 🛍️ Product & variation management (simple & configurable products)
- 🏬 Vendor store management
- 📦 Inventory & warehouse support
- 🧾 Orders, carts, and checkout workflows
- 💳 Payment & settlement system
- 📈 Reporting and analytics endpoints
- 🔄 Webhook support for third-party integrations
- 🧪 Fully testable API structure (Feature/Unit tests)

## 🧱 Tech Stack

- **Backend:** Laravel 10+
- **Database:** MySQL
- **Auth:** Sanctum
- **Caching:** Redis / Laravel Cache
- **Queue:** Laravel Queues (Redis, DB, or other drivers)
- **Testing:** PHPUnit / Pest

## 📦 Installation

```bash
git clone https://github.com/your-username/laravel-multivendor-api.git
cd laravel-multivendor-api

# Install dependencies
composer install

# Copy .env and configure
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate --seed

# (Optional) Set up storage and queue
php artisan storage:link
php artisan queue:work
