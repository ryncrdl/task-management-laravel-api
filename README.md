# Task Management — Laravel API

RESTful API backend for the Task Management & Analytics Platform, built with **Laravel 11** and **JWT authentication**.

---

## Live URLs

| Service | URL |
|---|---|
| **API Base** | `https://task-management-laravel-api.onrender.com/api` |
| **Health Check** | `https://task-management-laravel-api.onrender.com/up` |
| **GitHub** | `https://github.com/ryncrdl/task-management-laravel-api` |
| **Frontend** | `https://task-management-react-e9ni.onrender.com` |

---

## Companion Repositories

| Service | GitHub |
|---|---|
| Node.js Services | `https://github.com/ryncrdl/task-management-node-services` |
| React Frontend | `https://github.com/ryncrdl/task-management-react` |

---

## Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| PHP | ^8.2 | Runtime |
| Laravel | ^11.0 | Framework |
| tymon/jwt-auth | ^2.1 | JWT Authentication |
| PostgreSQL | 15+ | Database |
| PHPUnit | ^11 | Testing |

---

## Local Setup

### Prerequisites
- PHP 8.2+
- Composer
- PostgreSQL (running locally)

### 1 · Clone & Install

```bash
git clone https://github.com/ryncrdl/task-management-laravel-api.git
cd task-management-laravel-api
composer install
```

### 2 · Environment

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edit `.env` with your local values (at minimum):

```dotenv
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=task_management
DB_USERNAME=postgres
DB_PASSWORD=your_password

NODE_SERVICE_URL=http://localhost:3000
NODE_SERVICE_SECRET=your-inter-service-secret-key
```

### 3 · Database

```bash
# Create the database (first time only)
# psql -U postgres -c "CREATE DATABASE task_management;"

# Run all migrations
php artisan migrate

# Seed users, teams, and sample tasks
php artisan db:seed
```

### 4 · Start the Server

```bash
php artisan serve
# API: http://localhost:8000/api
```

---

## Test Credentials

```
Admin:   admin@test.com    / password123
Manager: manager@test.com  / password123
Member:  member@test.com   / password123
```

---

## Running Tests

```bash
php artisan test                       # All tests
php artisan test --testsuite=Feature   # Feature tests only
php artisan test --testsuite=Unit      # Unit tests only
php artisan test --verbose             # Verbose output
```

---

## API Reference

### Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | — | Register new user |
| POST | `/api/auth/login` | — | Login — returns JWT |
| POST | `/api/auth/logout` | JWT | Invalidate token |
| POST | `/api/auth/refresh` | JWT | Refresh JWT |
| GET  | `/api/auth/me` | JWT | Current user profile |

### Users *(Admin / Manager)*

| Method | Endpoint | Description |
|---|---|---|
| GET    | `/api/users` | List users (filters: role, status) |
| POST   | `/api/users` | Create user |
| GET    | `/api/users/{id}` | Get user details |
| PATCH  | `/api/users/{id}` | Update user (name, email, role) |
| PATCH  | `/api/users/{id}/status` | Toggle active / inactive |

### Teams

| Method | Endpoint | Description |
|---|---|---|
| GET    | `/api/teams` | List teams |
| POST   | `/api/teams` | Create team |
| GET    | `/api/teams/{id}` | Team + members |
| POST   | `/api/teams/{id}/members` | Add member |
| DELETE | `/api/teams/{id}/members/{user_id}` | Remove member |

### Tasks

| Method | Endpoint | Description |
|---|---|---|
| GET    | `/api/teams/{id}/tasks` | List team tasks |
| POST   | `/api/teams/{id}/tasks` | Create task |
| GET    | `/api/tasks/mine` | My assigned tasks |
| GET    | `/api/tasks/{id}` | Task details |
| PATCH  | `/api/tasks/{id}` | Update task |
| DELETE | `/api/tasks/{id}` | Soft-delete task |
| PATCH  | `/api/tasks/{id}/status` | Status transition |
| GET    | `/api/tasks/{id}/activity` | Activity log |
| DELETE | `/api/tasks/{id}/archive` | Archive cancelled task (cron) |

### Internal *(Node.js — X-Service-Secret)*

| Method | Endpoint | Description |
|---|---|---|
| POST   | `/api/internal/jobs` | Queue notification job |
| GET    | `/api/internal/jobs/pending` | Claim pending jobs |
| PATCH  | `/api/internal/jobs/{id}` | Update job status |
| GET    | `/api/internal/tasks/upcoming-deadlines` | Tasks due in 24 h |
| GET    | `/api/internal/tasks/incomplete-by-user` | Grouped incomplete tasks |
| GET    | `/api/internal/teams` | All teams |
| GET    | `/api/internal/teams/{team}/tasks` | Team tasks |
| DELETE | `/api/internal/tasks/{task}/archive` | Soft-archive task |

### Admin *(React UI — JWT + admin role)*

| Method | Endpoint | Description |
|---|---|---|
| GET    | `/api/admin/notification-jobs` | List notification jobs |
| GET    | `/api/admin/notification-jobs/stats` | Queue stats |
| POST   | `/api/admin/notification-jobs/{id}/retry` | Retry failed job |
| DELETE | `/api/admin/notification-jobs/{id}` | Delete job record |

---

## Role Permissions

| Role | Permissions |
|---|---|
| **Admin** | Full access: all users, all teams, all tasks, analytics, cron UI |
| **Manager** | Own team tasks, manage team members, view team analytics |
| **Member** | View / edit own assigned tasks only |

---

## Status Transition Rules

```
pending     → in_progress | cancelled
in_progress → completed   | pending
completed   → (terminal — no transitions)
cancelled   → (terminal — no transitions)
```

Returns `422` for invalid transitions.

---

## Bonus Features Implemented

- ✅ Activity log (full task change history with actor names)
- ✅ Comment system on tasks
- ✅ Rate limiting on API endpoints
- ✅ Request / response logging middleware
- ✅ Custom task filter presets (save / load)
- ✅ Soft deletes on tasks and users
- ✅ HTML email templates (sent via Node.js service)
- ✅ PHPUnit tests (Feature + Unit suites)

---

## Deployment (Render.com)

1. Create a **Web Service** and connect `ryncrdl/task-management-laravel-api`
2. **Build Command:** `composer install --optimize-autoloader --no-dev`
3. **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
4. Add all environment variables from `.env.example` (fill in real values)
5. Create a **PostgreSQL** instance on Render; set `DB_*` vars from the connection string
6. **Post-Deploy Command:** `php artisan migrate --force && php artisan db:seed --force`

> Free-tier services sleep after 15 min of inactivity.
> Use [cron-job.org](https://cron-job.org) to ping `GET /up` every 10 min.

---

## Security Notes

- Passwords hashed with `bcrypt` via Laravel's `hashed` cast
- JWT expires after 60 min (configurable via `JWT_TTL`)
- Throttle middleware on all routes
- Input validated via Form Request classes
- SQL injection prevented via Eloquent parameterised queries
- Soft deletes preserve audit trail
- No secrets hardcoded — all via `.env`
