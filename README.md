# ShoeTakels - Premium Shoe Store

A modern e-commerce platform for premium shoe brands built with Next.js 16, React, and Tailwind CSS.

## Features

- 🎨 Modern, responsive UI with Tailwind CSS
- ♿ Accessible components with ARIA labels and semantic HTML
- 🚀 Fast performance with Next.js App Router
- 📱 Mobile-first responsive design
- 🔍 SEO optimized with metadata

## Getting Started

### Prerequisites

- Node.js 18+ 
- npm or yarn

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/vincentagbuya03/Shoes_Store.git
   cd Shoes_Store
   ```

2. Install dependencies:
   ```bash
   npm install --legacy-peer-deps
   ```

3. Run the development server:
   ```bash
   npm run dev
   ```

4. Open [http://localhost:3000](http://localhost:3000) in your browser

### Build for Production

```bash
npm run build
npm start
```

## Project Structure

```
Shoes_Store/
├── app/                    # Next.js App Router pages
│   ├── brand/             # Brand page route
│   ├── layout.js          # Root layout with navigation
│   ├── page.js            # Home page
│   └── globals.css        # Global styles
├── src/
│   ├── components/        # React components
│   │   └── Brand/         # Brand page components
│   └── assets/            # Static assets (SVGs, images)
├── next.config.js         # Next.js configuration
├── tailwind.config.js     # Tailwind CSS configuration
└── package.json           # Dependencies and scripts
```

## Pages

### Home (`/`)
Landing page with introduction and navigation to brand collections.

### Brand (`/brand`)
Comprehensive brand showcase page featuring:
- Hero section with gradient background
- Brand story and mission
- Featured collections grid
- Brand partners showcase
- Call-to-action sections

## Components

### Brand Components

- **Brand.jsx**: Main brand landing page component
- **BrandCard.jsx**: Reusable card component for collections

See `src/components/Brand/README.md` for detailed component documentation.

## Technologies

- **Framework**: Next.js 16 (App Router)
- **UI Library**: React 19
- **Styling**: Tailwind CSS v4
- **Components**: Radix UI primitives
- **Icons**: Lucide React
- **Fonts**: System fonts (optimized for performance)

## Accessibility

This project follows WCAG 2.1 Level AA guidelines:
- Semantic HTML5 elements
- ARIA labels and roles
- Keyboard navigation support
- Focus visible states
- Screen reader friendly

## Browser Support

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is private and proprietary.

## Contact

Vincent Agbuya - [@vincentagbuya03](https://github.com/vincentagbuya03)

Project Link: [https://github.com/vincentagbuya03/Shoes_Store](https://github.com/vincentagbuya03/Shoes_Store)
