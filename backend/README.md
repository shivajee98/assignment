# Hierarchy Chat - Backend

A Laravel API for hierarchical messaging where messages flow downward only.

## Hierarchy

```
Super Admin → Admin → Manager → Incharge → Team Leader → Employee
```

- Higher level can message lower level
- Lower level CANNOT message higher level
- Super Admin can broadcast to everyone

## Deployment on Render

1. Push code to GitHub
2. Create new Web Service on Render
3. Connect your repo
4. Set Build Command: `composer install --no-dev && php artisan migrate --force`
5. Set Start Command: `php artisan serve --host=0.0.0.0 --port=$PORT`

## Environment Variables (Render)

```
APP_KEY=base64:xHNfLJZPNfXp8JmVZq2X8k5v+E3dq5LMq3gQ7tD5Hzs=
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=sqlite
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/login | Login |
| POST | /api/logout | Logout |
| GET | /api/profile | Get profile |
| GET | /api/users/messageable | Users you can message |
| GET | /api/messages/conversations | List conversations |
| GET | /api/messages/private/{userId} | Get messages |
| POST | /api/messages/private/{userId} | Send message |
| POST | /api/messages/broadcast | Broadcast to all below |

## Test Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@example.com | password123 |
| Admin | admin1@example.com | password123 |
| Employee | employee1@example.com | password123 |

## Local Development

```bash
composer install
php artisan migrate --seed
php artisan serve
```
