# Hierarchy Chat - Backend

Laravel API for hierarchical messaging with Docker support for Render deployment.

## Hierarchy

```
Super Admin → Admin → Manager → Incharge → Team Leader → Employee
```

Higher level can message lower levels. Lower level CANNOT message higher level.

## Local Development

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## Deploy to Render (Docker)

### Step 1: Create PostgreSQL Database
1. Go to Render Dashboard
2. Click **New +** → **PostgreSQL**
3. Name: `hierarchy-chat-db`
4. Plan: Free
5. Create and copy the **Internal Database URL**

### Step 2: Deploy Web Service
1. Click **New +** → **Web Service**
2. Connect your GitHub repo
3. Configure:
   - **Root Directory:** `backend`
   - **Runtime:** Docker
4. Add Environment Variables:

| Key | Value |
|-----|-------|
| DATABASE_URL | (Internal Database URL from Step 1) |
| DB_CONNECTION | pgsql |
| APP_KEY | Run `php artisan key:generate --show` locally |
| APP_ENV | production |
| APP_DEBUG | false |

5. Deploy!

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

## Files for Docker Deployment

```
backend/
├── Dockerfile
├── .dockerignore
├── render.yaml
├── scripts/
│   └── 00-laravel-deploy.sh
└── conf/nginx/
    └── nginx-site.conf
```
