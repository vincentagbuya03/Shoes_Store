# Brand UI Feature

This directory contains the React components for the Brand page feature.

## Components

### Brand.jsx
The main Brand landing page component featuring:
- **Hero Section**: Eye-catching gradient hero with call-to-action buttons
- **Brand Story**: Informative section about the company's mission and values
- **Featured Collections**: Grid of brand collection cards showcasing different product categories
- **Brand Partners**: Grid displaying partner brand logos
- **Call to Action**: Conversion-focused section encouraging users to shop

**Accessibility Features:**
- Semantic HTML5 elements (`<main>`, `<section>`, `<header>`)
- ARIA labels and landmarks for screen readers
- Keyboard-focusable interactive elements with visible focus states
- Proper heading hierarchy (h1, h2, h3)
- Alt text for all images and decorative elements marked with `aria-hidden="true"`

**Performance Optimizations:**
- Lazy loading for images
- Optimized SVG usage
- Efficient CSS transitions
- Mobile-first responsive design

### BrandCard.jsx
A reusable card component for displaying brand collections or products.

**Props:**
- `title` (string, required): Card title
- `description` (string, optional): Card description
- `image` (string, optional): Image URL or path
- `link` (string, optional): Link URL (defaults to '#')
- `className` (string, optional): Additional CSS classes

**Features:**
- Hover animations and transitions
- Focus states for accessibility
- Responsive aspect ratio
- Fallback SVG when no image is provided
- Gradient overlays for visual depth

## Styling

The components use Tailwind CSS utility classes for styling, providing:
- Neutral color palette with gradients
- Responsive grid layouts (mobile-first)
- Smooth transitions and hover effects
- Glassmorphism-inspired designs
- Dark mode support (via Tailwind's dark mode)

## Usage

### View the Brand Page

1. Install dependencies:
   ```bash
   npm install
   ```

2. Run the development server:
   ```bash
   npm run dev
   ```

3. Open your browser and navigate to:
   ```
   http://localhost:3000/brand
   ```

### Using BrandCard in Other Components

```jsx
import BrandCard from '@/src/components/Brand/BrandCard'

function MyComponent() {
  return (
    <BrandCard
      title="Summer Collection"
      description="Lightweight designs for warm weather"
      image="/path/to/image.jpg"
      link="/collections/summer"
    />
  )
}
```

## Assets

Brand-related assets are stored in `/src/assets/brand/`:
- `placeholder-logo.svg`: Default brand logo placeholder
- `collection-placeholder.svg`: Default collection image placeholder

## Accessibility Testing

The Brand components have been designed with accessibility in mind:

- ✅ Keyboard navigation support
- ✅ Screen reader friendly
- ✅ ARIA attributes properly used
- ✅ Semantic HTML structure
- ✅ Sufficient color contrast
- ✅ Focus visible states

### Testing Checklist
- [ ] Navigate using Tab key through all interactive elements
- [ ] Test with screen reader (e.g., NVDA, JAWS, VoiceOver)
- [ ] Verify all images have appropriate alt text
- [ ] Check color contrast ratios (WCAG AA compliant)
- [ ] Test responsive layouts on multiple screen sizes

## Performance

### Optimization Techniques Used
- **Lazy Loading**: Non-critical images load on demand
- **SVG Optimization**: Inline SVGs for logos reduce HTTP requests
- **CSS Animations**: Hardware-accelerated transforms for smooth animations
- **Responsive Images**: Using aspect ratios for proper sizing
- **Code Splitting**: Next.js automatically code-splits routes

### Performance Metrics
- Lighthouse Performance Score: Target 90+
- First Contentful Paint: Target <1.5s
- Largest Contentful Paint: Target <2.5s
- Cumulative Layout Shift: Target <0.1

## Future Enhancements

Potential improvements for the Brand feature:
- [ ] Connect to real brand data from database/API
- [ ] Add filtering and search functionality
- [ ] Implement pagination for large brand catalogs
- [ ] Add animations using Framer Motion
- [ ] Create brand detail pages
- [ ] Add wishlist/favorite functionality
- [ ] Implement social sharing features
- [ ] Add user reviews and ratings

## Browser Support

Tested and supported browsers:
- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)

## Contributing

When modifying these components:
1. Maintain accessibility standards
2. Follow the existing code style
3. Test on multiple screen sizes
4. Ensure keyboard navigation works
5. Update this README if adding new features
