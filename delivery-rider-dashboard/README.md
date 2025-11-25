# Delivery Rider Dashboard

A modern, responsive dashboard for delivery riders built with Vue 3 and Tailwind CSS.

## Features

- **Dashboard Overview**: View daily statistics including deliveries, earnings, and ratings
- **Active Deliveries**: Manage current delivery orders with status updates
- **Delivery History**: View past deliveries with filtering options
- **Earnings Tracker**: Monitor income with weekly breakdowns and transaction history
- **Profile Management**: Update personal information and view performance stats

## Tech Stack

- Vue 3 (Composition API with `<script setup>`)
- Vite
- Tailwind CSS

## Getting Started

### Prerequisites

- Node.js 18+
- npm or yarn

### Installation

```bash
cd delivery-rider-dashboard
npm install
```

### Development

```bash
npm run dev
```

The app will be available at `http://localhost:5173`

### Build for Production

```bash
npm run build
```

The built files will be in the `dist` directory.

### Preview Production Build

```bash
npm run preview
```

## Project Structure

```
delivery-rider-dashboard/
├── src/
│   ├── components/
│   │   ├── Sidebar.vue          # Navigation sidebar
│   │   ├── Dashboard.vue        # Main dashboard view
│   │   ├── ActiveDeliveries.vue # Current deliveries management
│   │   ├── DeliveryHistory.vue  # Past deliveries list
│   │   ├── Earnings.vue         # Earnings and statistics
│   │   └── Profile.vue          # User profile settings
│   ├── App.vue                  # Root component
│   ├── main.js                  # Application entry point
│   └── style.css                # Global styles with Tailwind
├── index.html
├── vite.config.js
└── package.json
```

## Screenshots

![Dashboard](https://github.com/user-attachments/assets/981071ad-5140-49f0-99c3-c36faeeb808f)
