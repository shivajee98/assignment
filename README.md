# Hierarchy Chat System

A hierarchical messaging system where messages flow downward only.

## Architecture

```
Super Admin
    ↓ can message
Admin
    ↓ can message
Manager
    ↓ can message
Incharge
    ↓ can message
Team Leader
    ↓ can message
Employee
```

**Key Rule:** Higher roles can message lower roles. Lower roles CANNOT message higher roles.

## Components

- **Backend:** Laravel API with SQLite (`/backend`)
- **Android App:** Native Kotlin (`/android-app`)

## Quick Start

### Backend

```bash
cd backend
composer install
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

### Android App

1. Open `/android-app` in Android Studio
2. Update `API_BASE_URL` in `app/build.gradle`
3. Build and run

## Deployment

### Backend on Render

1. Push to GitHub
2. Create Web Service on Render
3. Build: `composer install --no-dev && php artisan migrate --force`
4. Start: `php artisan serve --host=0.0.0.0 --port=$PORT`

### Android App

```bash
cd android-app
./gradlew assembleDebug
```

## Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@example.com | password123 |
| Admin | admin1@example.com | password123 |
| Manager | manager1@example.com | password123 |
| Team Leader | tl1@example.com | password123 |
| Employee | employee1@example.com | password123 |
