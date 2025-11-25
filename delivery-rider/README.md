# Delivery Rider Dashboard

A modern delivery rider dashboard application built with Vue 3, Vite, and Tailwind CSS on the frontend, with a lightweight Laravel Lumen API scaffold on the backend.

## Overview

This module provides a dedicated interface for delivery riders to:
- View and manage delivery orders
- Accept/decline incoming orders
- Track order status and completion
- View delivery history
- Manage their profile and availability status

## Tech Stack

### Frontend (UI)
- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next-generation frontend build tool
- **Tailwind CSS** - Utility-first CSS framework
- **Vue Router** - Official router for Vue.js
- **Axios** - Promise-based HTTP client

### Backend (API)
- **Laravel Lumen** - Lightweight PHP micro-framework
- **SQLite** - Lightweight database (easily switchable to MySQL)

## Prerequisites

### For UI Development
- Node.js >= 16.x
- npm >= 8.x

### For API Development
- PHP >= 8.0
- Composer >= 2.x
- PHP SQLite extension (or MySQL)

## Quick Start

### UI Setup

```bash
cd delivery-rider/ui

# Install dependencies
npm install

# Start development server
npm run dev
```

The UI will be available at `http://localhost:5173`

### API Setup

```bash
cd delivery-rider/api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key (add to .env manually)
# APP_KEY=base64:YOUR_RANDOM_32_CHAR_STRING

# Create SQLite database
touch database/database.sqlite

# Start the development server
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`

## Configuration

### Connecting UI to API

The UI is pre-configured to connect to the API at `http://localhost:8000`. To change this:

1. Create a `.env` file in the `ui/` directory:
```env
VITE_API_BASE_URL=http://localhost:8000
```

2. Or modify `src/services/api.js` directly.

### Using Existing Repository Login

This project includes a sample login page, but you can integrate with the existing ShoeTakels authentication system:

1. **Option A: Redirect to existing login**
   - Modify `src/router/index.js` to redirect `/login` to the main app's login page
   - After successful login, redirect back to `/delivery-rider/ui/`

2. **Option B: Share session/token**
   - If the main app uses PHP sessions, ensure the API reads from the same session
   - If using JWT, share the token between apps via localStorage

3. **Option C: Use sample login for development**
   - The included login page calls `POST /api/auth/login`
   - For testing, the API returns mock data without real authentication

## Project Structure

```
delivery-rider/
├── README.md                 # This file
├── ui/                       # Vue 3 + Vite frontend
│   ├── package.json
│   ├── vite.config.js
│   ├── tailwind.config.cjs
│   ├── postcss.config.cjs
│   ├── index.html
│   ├── public/
│   └── src/
│       ├── main.js
│       ├── App.vue
│       ├── router/
│       │   └── index.js
│       ├── pages/
│       │   ├── Login.vue
│       │   ├── Dashboard.vue
│       │   ├── Orders.vue
│       │   ├── OrderDetail.vue
│       │   ├── Profile.vue
│       │   └── History.vue
│       ├── components/
│       │   ├── Header.vue
│       │   ├── OrderCard.vue
│       │   └── MapPlaceholder.vue
│       ├── services/
│       │   └── api.js
│       └── assets/
└── api/                      # Lumen micro-app
    ├── composer.json
    ├── .env.example
    ├── bootstrap/
    │   └── app.php
    ├── public/
    │   └── index.php
    ├── routes/
    │   └── web.php
    ├── app/
    │   └── Http/
    │       └── Controllers/
    │           ├── AuthController.php
    │           ├── OrdersController.php
    │           └── ProfileController.php
    └── database/
        └── database.sqlite
```

## API Endpoints

| Method | Endpoint                    | Description              |
|--------|----------------------------|--------------------------|
| POST   | `/api/auth/login`          | Authenticate rider       |
| GET    | `/api/orders`              | Get active orders        |
| GET    | `/api/orders/history`      | Get completed orders     |
| POST   | `/api/orders/{id}/accept`  | Accept an order          |
| POST   | `/api/orders/{id}/complete`| Complete an order        |
| GET    | `/api/profile`             | Get rider profile        |

## Color Scheme

The UI uses colors from the main ShoeTakels repository:

| Variable     | Color     | Usage                    |
|-------------|-----------|--------------------------|
| Primary     | `#1a1a1a` | Headers, buttons         |
| Accent      | `#d4a574` | Highlights, active states|
| Light       | `#f5f5f5` | Backgrounds              |
| White       | `#ffffff` | Cards, content areas     |
| Text        | `#333333` | Primary text             |
| Text Light  | `#666666` | Secondary text           |
| Success     | `#10b981` | Success states           |
| Error       | `#ef4444` | Error states             |

## Logo

The UI references the repository logo at `../../upload/picture/logo.png`. If the logo is not found:
1. Copy your logo to `ui/public/logo.png`
2. Update the logo path in `src/components/Header.vue`

## Development Notes

### Environment Variables

**UI (.env)**
```env
VITE_API_BASE_URL=http://localhost:8000
```

**API (.env)**
```env
APP_NAME=DeliveryRiderAPI
APP_ENV=local
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### Database Setup

The API uses SQLite by default. To switch to MySQL:

1. Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery_rider
DB_USERNAME=root
DB_PASSWORD=
```

2. Create the database and run migrations (if available).

### Map Integration

The `MapPlaceholder` component includes instructions for integrating Leaflet or Google Maps:

**Leaflet (Free)**
```bash
npm install leaflet vue-leaflet
```

**Google Maps**
```bash
npm install @googlemaps/js-api-loader
```

See the component comments for implementation details.

## Assumptions & Notes

1. **Authentication**: The sample login provides mock authentication. For production, integrate with your existing auth system or implement proper JWT authentication in the Lumen API.

2. **Logo Path**: The logo is expected at `../../upload/picture/logo.png` relative to the UI. Adjust if your deployment structure differs.

3. **CORS**: The API includes basic CORS headers. For production, configure allowed origins properly.

4. **Database**: Sample JSON responses are included in controllers for testing without a database. For production, implement proper database models and migrations.

## License

This module is part of the ShoeTakels repository.
