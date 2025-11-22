'use client'

import React from 'react'
import BrandCard from './BrandCard'

/**
 * Brand - Main landing page component showcasing brand information, collections, and partners
 * Features: Hero section, brand story, featured collections, brand grid
 * Accessibility: Semantic HTML, ARIA labels, keyboard navigation support
 */
export default function Brand() {
  // Sample brand data - in production, this would come from an API or database
  const featuredCollections = [
    {
      id: 1,
      title: 'Summer Collection 2024',
      description: 'Lightweight and breathable designs perfect for warm weather adventures',
      image: null,
      link: '#summer-collection'
    },
    {
      id: 2,
      title: 'Athletic Performance',
      description: 'High-performance footwear engineered for athletes and active lifestyles',
      image: null,
      link: '#athletic'
    },
    {
      id: 3,
      title: 'Classic Heritage',
      description: 'Timeless designs that blend tradition with modern comfort',
      image: null,
      link: '#heritage'
    },
    {
      id: 4,
      title: 'Urban Style',
      description: 'Contemporary streetwear-inspired shoes for the modern city dweller',
      image: null,
      link: '#urban'
    }
  ]

  const brandLogos = [
    { id: 1, name: 'Nike', svg: true },
    { id: 2, name: 'Adidas', svg: true },
    { id: 3, name: 'Puma', svg: true },
    { id: 4, name: 'Reebok', svg: true },
    { id: 5, name: 'New Balance', svg: true },
    { id: 6, name: 'Converse', svg: true },
  ]

  return (
    <main className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      {/* Hero Section */}
      <section 
        className="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white"
        aria-labelledby="hero-heading"
      >
        <div className="absolute inset-0 opacity-10">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white via-transparent to-transparent" />
        </div>
        
        <div className="container relative mx-auto px-4 py-24 md:py-32">
          <div className="max-w-4xl mx-auto text-center">
            <h1 
              id="hero-heading"
              className="mb-6 text-5xl md:text-7xl font-bold tracking-tight"
            >
              Discover Premium
              <span className="block bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                Shoe Brands
              </span>
            </h1>
            <p className="mb-8 text-xl md:text-2xl text-gray-300 leading-relaxed">
              Curated collections from the world's most iconic footwear brands
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="#collections"
                className="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white text-gray-900 font-semibold hover:bg-gray-100 transition-all duration-300 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-900"
                aria-label="Explore our collections"
              >
                Explore Collections
                <svg 
                  className="ml-2 h-5 w-5" 
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
              </a>
              <a
                href="#brands"
                className="inline-flex items-center justify-center px-8 py-4 rounded-full border-2 border-white text-white font-semibold hover:bg-white hover:text-gray-900 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-900"
                aria-label="View all brands"
              >
                View All Brands
              </a>
            </div>
          </div>
        </div>

        {/* Decorative elements */}
        <div className="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-gray-50 to-transparent" aria-hidden="true" />
      </section>

      {/* Brand Story Section */}
      <section 
        className="py-20 md:py-28"
        aria-labelledby="story-heading"
      >
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto text-center">
            <h2 
              id="story-heading"
              className="mb-6 text-4xl md:text-5xl font-bold text-gray-900"
            >
              Our Brand Story
            </h2>
            <div className="space-y-6 text-lg text-gray-600 leading-relaxed">
              <p>
                At ShoeTakels, we believe that the right pair of shoes can transform not just your outfit, 
                but your entire day. For over a decade, we've been partnering with the world's most 
                innovative footwear brands to bring you collections that combine style, comfort, and performance.
              </p>
              <p>
                From classic heritage designs to cutting-edge athletic technology, our curated selection 
                represents the pinnacle of craftsmanship and design. Each brand we feature shares our 
                commitment to quality, sustainability, and pushing the boundaries of what's possible in footwear.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Featured Collections Section */}
      <section 
        id="collections"
        className="py-20 bg-white"
        aria-labelledby="collections-heading"
      >
        <div className="container mx-auto px-4">
          <header className="mb-12 text-center">
            <h2 
              id="collections-heading"
              className="mb-4 text-4xl md:text-5xl font-bold text-gray-900"
            >
              Featured Collections
            </h2>
            <p className="text-xl text-gray-600">
              Explore our handpicked selection of premium footwear
            </p>
          </header>

          <div 
            className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8"
            role="list"
            aria-label="Featured collections"
          >
            {featuredCollections.map((collection) => (
              <BrandCard
                key={collection.id}
                title={collection.title}
                description={collection.description}
                image={collection.image}
                link={collection.link}
              />
            ))}
          </div>
        </div>
      </section>

      {/* Brand Partners Grid Section */}
      <section 
        id="brands"
        className="py-20 bg-gradient-to-b from-gray-50 to-white"
        aria-labelledby="brands-heading"
      >
        <div className="container mx-auto px-4">
          <header className="mb-12 text-center">
            <h2 
              id="brands-heading"
              className="mb-4 text-4xl md:text-5xl font-bold text-gray-900"
            >
              Our Brand Partners
            </h2>
            <p className="text-xl text-gray-600">
              Trusted by millions, worn with pride
            </p>
          </header>

          <div 
            className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 md:gap-8"
            role="list"
            aria-label="Brand partners"
          >
            {brandLogos.map((brand) => (
              <div
                key={brand.id}
                className="group flex items-center justify-center p-8 bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
                role="listitem"
              >
                <div className="flex items-center justify-center h-20 w-full">
                  {/* Placeholder brand logo */}
                  <div className="text-center">
                    <svg 
                      className="h-12 w-12 mx-auto text-gray-400 group-hover:text-gray-600 transition-colors"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <path 
                        strokeLinecap="round" 
                        strokeLinejoin="round" 
                        strokeWidth={1.5} 
                        d="M13 10V3L4 14h7v7l9-11h-7z" 
                      />
                    </svg>
                    <p className="mt-2 text-sm font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">
                      {brand.name}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Call to Action Section */}
      <section 
        className="py-20 bg-gradient-to-br from-blue-600 to-purple-600 text-white"
        aria-labelledby="cta-heading"
      >
        <div className="container mx-auto px-4 text-center">
          <h2 
            id="cta-heading"
            className="mb-6 text-4xl md:text-5xl font-bold"
          >
            Ready to Find Your Perfect Pair?
          </h2>
          <p className="mb-8 text-xl md:text-2xl text-blue-100">
            Join thousands of satisfied customers who found their ideal shoes with us
          </p>
          <a
            href="/index.php"
            className="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white text-blue-600 font-semibold hover:bg-gray-100 transition-all duration-300 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600"
            aria-label="Start shopping now"
          >
            Start Shopping Now
            <svg 
              className="ml-2 h-5 w-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
