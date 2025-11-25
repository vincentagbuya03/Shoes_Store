# Delivery Rider Dashboard

A delivery rider dashboard for the ShoeTakels Shoes Store, featuring a Vue 3 + Vite + Tailwind CSS frontend and a Laravel Lumen API backend.

## Overview

This module provides delivery riders with:
- Order management dashboard
- Real-time order status updates
- Order history tracking
- Profile management
- Map integration for delivery locations (placeholder included)

## Tech Stack

### Frontend (UI)
- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next-generation frontend build tool
- **Tailwind CSS** - Utility-first CSS framework
- **Vue Router** - Official router for Vue.js
- **Axios** - HTTP client for API requests

### Backend (API)
- **Laravel Lumen 8** - Lightweight PHP micro-framework
- **SQLite** - Lightweight database (configurable to MySQL)

## Prerequisites

- **Node.js** >= 16.x
- **npm** >= 8.x
- **PHP** >= 7.3
- **Composer** >= 2.x

## Project Structure

```
delivery-rider/
├── README.md
├── ui/                         # Vue 3 Frontend
│   ├── package.json
│   ├── vite.config.js
│   ├── tailwind.config.cjs
│   ├── postcss.config.cjs
│   ├── index.html
│   ├── public/
│   └── src/
│       ├── main.js
│       ├── App.vue
│       ├── router/index.js
│       ├── services/api.js
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
│       └── assets/
└── api/                        # Lumen Backend
    ├── composer.json
    ├── .env.example
    ├── bootstrap/app.php
    ├── public/index.php
    ├── routes/web.php
    ├── app/Http/Controllers/
    │   ├── AuthController.php
    │   ├── OrdersController.php
    │   └── ProfileController.php
    └── database/
```

## Quick Start

### 1. Start the API Server

```bash
cd delivery-rider/api

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key (update APP_KEY in .env manually if needed)
# For Lumen, set APP_KEY to a random 32-character string

# Start the development server
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`

### 2. Start the UI Development Server

```bash
cd delivery-rider/ui

# Install Node.js dependencies
npm install

# Start the development server
npm run dev
```

The UI will be available at `http://localhost:5173`

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Authenticate rider |
| GET | `/api/orders` | Get active orders |
| GET | `/api/orders/history` | Get completed orders |
| POST | `/api/orders/{id}/accept` | Accept an order |
| POST | `/api/orders/{id}/complete` | Complete an order |
| GET | `/api/profile` | Get rider profile |

## Environment Configuration

### UI Environment Variables

Create a `.env` file in the `ui/` directory:

```env
VITE_API_BASE_URL=http://localhost:8000
```

Or set the environment variable when running:

```bash
VITE_API_BASE_URL=http://localhost:8000 npm run dev
```

### API Environment Variables

Copy `.env.example` to `.env` and configure:

```env
APP_NAME=DeliveryRiderAPI
APP_ENV=local
APP_KEY=your-32-character-random-string
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## Using Existing Repository Login

This dashboard includes a sample login page, but you can integrate with the existing ShoeTakels authentication:

### Option 1: Redirect to Existing Login
Modify `src/router/index.js` to redirect `/login` to the main store's `login.php`:

```javascript
{
  path: '/login',
  beforeEnter: () => {
    window.location.href = '/login.php';
    return false;
  }
}
```

### Option 2: Share Authentication Token
If the existing login system uses session-based authentication:
1. Ensure the API verifies the PHP session
2. Update the API controllers to check `$_SESSION`
3. Configure CORS to allow credentials

### Option 3: API Token Authentication
1. Generate API tokens from the existing user table
2. Store the token in localStorage after login
3. Include the token in API requests via the Authorization header

## Color Scheme

The UI uses color tokens that match the repository's existing design:

| Token | Value | Usage |
|-------|-------|-------|
| `--primary` | `#1a1a1a` | Primary dark color |
| `--accent` | `#d4a574` | Accent/highlight color |
| `--light` | `#f5f5f5` | Light backgrounds |
| `--white` | `#ffffff` | White backgrounds |
| `--text` | `#333333` | Main text color |
| `--text-light` | `#666666` | Secondary text |
| `--success` | `#10b981` | Success states |
| `--error` | `#ef4444` | Error states |

## Logo

The UI references the repository logo at `../../upload/picture/logo.png`. If the logo is not loading:

1. Ensure you're running the UI from within the repository structure
2. Or copy the logo to `ui/public/logo.png` and update the reference in `index.html`:

```html
<link rel="icon" type="image/png" href="/logo.png">
```

## Map Integration

The OrderDetail page includes a `MapPlaceholder` component. To enable actual map functionality:

### Using Leaflet (Free)

```bash
npm install leaflet vue-leaflet
```

Then replace the MapPlaceholder with:

```vue
<template>
  <l-map :zoom="13" :center="[lat, lng]">
    <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
    <l-marker :lat-lng="[lat, lng]" />
  </l-map>
</template>
```

### Using Google Maps

1. Get an API key from Google Cloud Console
2. Install `@googlemaps/js-api-loader`
3. Follow Google Maps JavaScript API documentation

## Development Notes

### Building for Production

```bash
# Build UI
cd ui
npm run build
# Output will be in ui/dist/

# For API, configure your web server (Apache/Nginx) to serve public/index.php
```

### Running Tests (API)

```bash
cd api
composer test  # If PHPUnit is configured
```

### Database Seeding

The API includes sample data in the controllers for testing. To use a real database:

1. Create the SQLite database:
```bash
touch database/database.sqlite
```

2. Run migrations (create tables manually or add migration files)

3. Seed with sample data

## Troubleshooting

### CORS Issues
If you encounter CORS errors, ensure the API has proper CORS headers. The Lumen app includes CORS middleware configuration.

### API Connection Failed
1. Verify the API is running on the expected port
2. Check the `VITE_API_BASE_URL` environment variable
3. Ensure no firewall is blocking the connection

### Logo Not Loading
1. Check the relative path from the UI to the repository root
2. Copy the logo to `ui/public/` as a fallback

## License

This module is part of the ShoeTakels Shoes Store project.
