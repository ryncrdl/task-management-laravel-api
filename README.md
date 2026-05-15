# Task Management Laravel API

RESTful API backend for the Task Management & Analytics Platform, built with **Laravel 11** and **JWT authentication**.

## Live Deployment

> Update with your Render/Railway URL after deployment.

- **API Base URL:** `https://your-app.render.com/api`
- **Health Check:** `https://your-app.render.com/up`

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
- PostgreSQL (or MySQL)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-username/task-management-laravel-api.git
cd task-management-laravel-api

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Generate JWT secret
php artisan jwt:secret

# 6. Configure database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=task_management
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# 7. Run migrations
php artisan migrate

# 8. Seed initial data (roles, teams, sample tasks)
php artisan db:seed

# 9. Start the development server
php artisan serve
# API available at http://localhost:8000/api
```

---

## Environment Variables

See `.env.example` for all required variables.

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel encryption key (auto-generated) |
| `JWT_SECRET` | JWT signing secret (`php artisan jwt:secret`) |
| `DB_*` | Database connection details |
| `NODE_SERVICE_URL` | URL of the Node.js service |

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | No | Register new user |
| POST | `/api/auth/login` | No | Login, returns JWT |
| POST | `/api/auth/logout` | Yes | Invalidate token |
| POST | `/api/auth/refresh` | Yes | Refresh JWT token |
| GET | `/api/auth/me` | Yes | Get own profile |

### Users (Admin/Manager only)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/users` | List users (filters: role, status) |
| POST | `/api/users` | Create user |
| GET | `/api/users/{id}` | Get user details |
| PATCH | `/api/users/{id}` | Update user |
| PATCH | `/api/users/{id}/status` | Toggle active/inactive |

### Teams

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/teams` | List teams |
| POST | `/api/teams` | Create team |
| GET | `/api/teams/{id}` | Team + members |
| POST | `/api/teams/{id}/members` | Add member |
| DELETE | `/api/teams/{id}/members/{user_id}` | Remove member |

### Tasks

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/teams/{id}/tasks` | List team tasks |
| POST | `/api/teams/{id}/tasks` | Create task |
| GET | `/api/tasks/{id}` | Task details |
| PATCH | `/api/tasks/{id}` | Update task |
| DELETE | `/api/tasks/{id}` | Soft-delete task |
| PATCH | `/api/tasks/{id}/status` | Update status (with transition validation) |
| DELETE | `/api/tasks/{id}/archive` | Archive old cancelled tasks |

---

## Role Permissions

| Role | Permissions |
|---|---|
| **Admin** | Full access: manage users, all teams, all tasks, analytics |
| **Manager** | Manage own team's tasks, add/remove members, view team analytics |
| **Member** | View/edit only own assigned tasks, cannot delete or reassign |

---

## Status Transition Rules

```
pending → in_progress | cancelled
in_progress → completed | pending
completed → (terminal)
cancelled → (terminal)
```

---

## Test Credentials

```
Admin:   admin@test.com    / password123
Manager: manager@test.com  / password123
Member:  member@test.com   / password123
```

---

## Deployment (Render.com)

1. Create a new **Web Service** on Render
2. Connect your GitHub repository
3. Set **Build Command:** `composer install --optimize-autoloader --no-dev`
4. Set **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
5. Add all environment variables from `.env.example`
6. Create a PostgreSQL instance on Render and link it
7. Add **Post-Deploy Hook:** `php artisan migrate --force && php artisan db:seed --force`

---

## Security Considerations

- All passwords hashed with `bcrypt` via Laravel's `hashed` cast
- JWT tokens expire in 60 minutes (configurable via `JWT_TTL`)
- Rate limiting applied via middleware
- Input sanitized via Form Request validation
- SQL injection prevention via Eloquent ORM parameterized queries
- Soft deletes prevent accidental data loss
- No sensitive data in logs
