# Limit Order Matching System

A real-time cryptocurrency exchange platform built with Laravel 12 and Vue 3, featuring automated order matching, real-time updates, and comprehensive wallet management.

## Overview

This application implements a limit order matching engine for cryptocurrency trading. Users can place buy and sell orders for BTC and ETH, with automatic matching when compatible orders are found. The system handles order execution, fee calculation, balance management, and provides real-time updates via WebSocket broadcasting.

## Features

- **Limit Order System**: Place buy and sell orders with custom price and amount
- **Automated Matching**: Automatic order matching based on price-time priority
- **Real-time Updates**: Live orderbook and wallet updates via Laravel Echo and Pusher
- **Wallet Management**: Track USD balance and cryptocurrency assets (BTC, ETH)
- **Order History**: View open, filled, and cancelled orders
- **Fee Calculation**: Automatic 1.5% trading fee applied to buyers
- **Multi-asset Support**: Trade BTC and ETH with symbol-based filtering

## Tech Stack

### Backend
- **PHP 8.2+** with **Laravel 12**
- **MySQL/PostgreSQL/SQLite** database
- **Laravel Sanctum** for API authentication
- **Laravel Queue** for asynchronous order matching
- **Laravel Broadcasting** with **Pusher** for real-time events
- **Pest** for testing

### Frontend
- **Vue 3** with Composition API
- **Vue Router** for navigation
- **Pinia** for state management
- **Axios** for HTTP requests
- **Laravel Echo** with **Pusher JS** for real-time updates
- **Tailwind CSS v4** for styling
- **Vite** for asset bundling

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/ajooda/limit-order-matching-system.git
cd limit-order-matching
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the environment file and configure your settings:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure the following:

#### Database Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Pusher Configuration (for real-time features)

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

Get your Pusher credentials from [pusher.com/channels](https://dashboard.pusher.com/channels)

#### Queue Configuration

```env
QUEUE_CONNECTION=database
```

### 4. Run Database Migrations

```bash
php artisan migrate
```

### 5. Seed the Database (Optional)

The seeder creates test users with initial balances for testing:

```bash
php artisan db:seed
```

This creates the following test accounts (all passwords: `password`):
- `buyer1@test.com` (Buyer A) - $10,000 USD
- `buyer2@test.com` (Buyer B) - $10,000 USD
- `seller1@test.com` (Seller A) - $10,000 USD, 1.0 BTC, 2.0 ETH
- `seller2@test.com` (Seller B) - $0 USD, 1.0 ETH

### 6. Install Frontend Dependencies

```bash
npm install
```

### 7. Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

## Running the Application

### Quick Start (All Services)

Start all development servers with a single command:

```bash
composer dev
```

This starts:
- Laravel development server (http://localhost:8000)
- Queue worker (for order matching)
- Vite dev server (for hot module replacement)

### Manual Start (Individual Services)

If you prefer to run services separately:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:work
```

**Terminal 3 - Vite Dev Server:**
```bash
npm run dev
```

### Access the Application

Open your browser and navigate to:
```
http://localhost:8000
```

## Matching Rules

- **Buy Order** matches with **Sell Order** when: `sell.price <= buy.price` AND `amounts are exactly equal`
- **Sell Order** matches with **Buy Order** when: `buy.price >= sell.price` AND `amounts are exactly equal`
- Orders are sorted by: **price** (priority), then **created_at**, then **id**
- **Trade Price**: Uses the counter order's price (price-time priority)
- **Fee**: 1.5% of trade volume, charged to buyer
- **Partial Fills**: Not supported - amounts must match exactly

### Database Structure

- **users**: User accounts with USD balance
- **assets**: Cryptocurrency holdings (BTC, ETH) per user
- **orders**: Buy/sell orders with status tracking
- **trades**: Executed trades linking buyer and seller orders

## Real-time Features

The application uses Laravel Echo with Pusher for real-time updates:

- **Order Matched Events**: Broadcast to `user.{userId}` channel when orders match
- **Event Payload**: Includes trade details, updated user balance, assets, and order statuses
- **Frontend Listener**: Automatically updates wallet, orderbook, and order status without page refresh

### Pusher Setup

1. Create a free account at [pusher.com](https://pusher.com)
2. Create a new Channels app
3. Copy your credentials to `.env`
4. Ensure `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` are set for frontend

## Development Scripts

Essential commands:

```bash
composer install
php artisan migrate
php artisan queue:work
npm run dev
```

## Project Structure

```
app/
├── Domain/Exchange/          # Domain logic for exchange features
│   ├── DTO/                   # Data Transfer Objects
│   └── Services/              # Business logic services
├── Enums/                     # OrderSide, OrderStatus enums
├── Events/                    # OrderMatchedEvent
├── Http/
│   ├── Controllers/Api/       # API controllers
│   ├── Requests/              # Form request validation
│   └── Resources/             # API resources
├── Jobs/                      # MatchOrderJob
├── Models/                    # Eloquent models
└── Support/                   # Helper classes (Money, FeeCalculator)

resources/
├── js/
│   ├── api/                   # API client functions
│   ├── components/exchange/   # Vue components
│   ├── pages/                 # Vue pages
│   ├── router/                # Vue Router configuration
│   ├── stores/                # Pinia stores
│   └── echo.js                # Laravel Echo setup

database/
├── migrations/                # Database migrations
├── factories/                 # Model factories
└── seeders/                   # Database seeders
```

## Troubleshooting

### Queue Not Processing

Ensure the queue worker is running:
```bash
php artisan queue:work
```

### Real-time Events Not Working

1. Verify Pusher credentials in `.env`
2. If testing on localhost, add `PUSHER_SSL_VERIFY=false` to `.env`
3. Ensure `VITE_PUSHER_APP_KEY` is set (required for frontend)
4. Check browser console for connection errors

### Database Connection Issues

1. Verify database credentials in `.env`
2. Ensure database exists and server is running
3. Run migrations: `php artisan migrate`

## License

This project is open-sourced software.
