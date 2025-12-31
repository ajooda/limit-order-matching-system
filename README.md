# Limit Order Matching System

Laravel 12 API + Vue 3 SPA setup for VirgoSoft assessment.

## Requirements

- PHP 8.2+
- Composer
- Node.js (for npm)
- MySQL/PostgreSQL/SQLite database

## Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. Copy environment file:
   ```bash
   cp .env.example .env
   ```

3. Generate application key:
   ```bash
   php artisan key:generate
   ```

4. Configure database in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Install frontend dependencies:
   ```bash
   npm install
   ```

7. Start development servers:
   ```bash
   composer dev
   ```
   
   This will start:
   - Laravel development server (http://localhost:8000)
   - Queue worker
   - Vite dev server

   Alternatively, you can run them separately:
   ```bash
   php artisan serve
   php artisan queue:listen
   npm run dev
   ```

## Architecture

### Frontend (Vue 3 SPA)

- **Entry Point**: `resources/js/app.js`
- **Router**: Vue Router configured in `resources/js/router/index.js`
- **Pages**: 
  - `/` - Home page
  - `/orders` - Orders page
- **Styling**: Tailwind CSS v4
- **HTTP Client**: Axios configured for Sanctum cookie authentication

The Vue SPA is served from the root route `/` via a catch-all route in `routes/web.php`.

### Authentication (Sanctum)

- Sanctum is configured for SPA cookie-based authentication
- Before making authenticated API requests, call `GET /sanctum/csrf-cookie` to enable CSRF protection
- Axios is configured with `withCredentials: true` to send cookies with requests
- Sanctum middleware is enabled via `statefulApi()` in `bootstrap/app.php`

### Real-time (Pusher)

- Broadcasting configuration supports Pusher
- Environment variables in `.env.example`:
  - `BROADCAST_CONNECTION=pusher`
  - `PUSHER_APP_ID=`
  - `PUSHER_APP_KEY=`
  - `PUSHER_APP_SECRET=`
  - `PUSHER_APP_CLUSTER=`
  - `VITE_PUSHER_APP_KEY=` (for frontend)
  - `VITE_PUSHER_APP_CLUSTER=` (for frontend)

## Testing

Run the test suite:
```bash
composer test
```

## Development Scripts

- `composer setup` - Full setup (install dependencies, generate key, migrate, build assets)
- `composer dev` - Start all development servers (Laravel, queue, Vite)
- `composer test` - Run tests
- `npm run dev` - Start Vite dev server
- `npm run build` - Build production assets
