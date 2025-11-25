# Delivery Rider Dashboard

A modern, responsive dashboard interface for delivery riders in the ShoeTakels shoe store application.

## Features

- **Modern UI/UX**: Glassmorphism cards, soft gradients, smooth hover transitions
- **Responsive Design**: Sidebar collapses to hamburger menu on mobile, grid adapts to screen size
- **Interactive Chart**: 7-day delivery statistics using Chart.js with hover tooltips
- **Search & Filter**: Client-side filtering for deliveries table by text search or status
- **SVG Animations**: Subtle icon animations (bike wheel spin, checkmark draw, box bounce)
- **Accessibility**: ARIA labels, keyboard navigation, sufficient color contrast

## Quick Start

1. **View Locally**
   
   Open `dashboard.php` directly in your browser, or serve the repo with PHP:
   ```bash
   # From the repository root
   php -S localhost:8000
   ```
   Then visit: `http://localhost:8000/delivery_rider/dashboard.php`

2. **XAMPP / WAMP / MAMP**
   
   Place the repository in your `htdocs` (or `www`) folder and navigate to:
   ```
   http://localhost/Shoes_Store/delivery_rider/dashboard.php
   ```

## Dependencies

| Dependency | Version | Source |
|------------|---------|--------|
| Chart.js   | 4.4.1   | CDN (`https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js`) |
| Inter Font | Latest  | Google Fonts |

No NPM install required — all dependencies are loaded via CDN.

## File Structure

```
delivery_rider/
├── dashboard.php              # Main dashboard page
├── README.md                  # This file
└── assets/
    ├── css/
    │   └── rider.css          # Stylesheet with CSS variables, animations
    ├── js/
    │   └── rider.js           # Vanilla JS: chart init, search, sidebar toggle
    └── icons/
        ├── bike.svg           # Delivery bike icon
        ├── box.svg            # Package box icon
        └── check.svg          # Checkmark icon
```

## Adding Navigation Link

To add a link to the rider dashboard in the main site navigation, you can add the following to the nav section in existing header files (e.g., `index.php`):

```php
<!-- Add to nav-links ul -->
<li><a href="delivery_rider/dashboard.php">Rider Dashboard</a></li>
```

Or for authenticated riders only:

```php
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'rider'): ?>
    <li><a href="delivery_rider/dashboard.php">My Dashboard</a></li>
<?php endif; ?>
```

## Authentication Placeholder

The `dashboard.php` file includes a placeholder for session/auth checks at the top:

```php
<?php
// session_start();
// if (!isset($_SESSION['rider_id'])) {
//     header('Location: /login.php');
//     exit;
// }
?>
```

Uncomment and modify as needed to integrate with your authentication system.

## Customization

### Color Palette

Edit CSS variables in `assets/css/rider.css`:

```css
:root {
  --primary-indigo: #6366f1;
  --accent-teal: #14b8a6;
  --accent-orange: #f97316;
  /* ... more variables */
}
```

### Sample Data

The dashboard uses hardcoded sample data for demonstration. To connect to your database:

1. Replace the HTML table rows in `dashboard.php` with a PHP loop fetching from your `deliveries` table
2. Update the chart data in `rider.js` or pass data from PHP as JSON

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## License

Part of the ShoeTakels project. See repository license.
