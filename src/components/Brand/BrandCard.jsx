'use client'

import React from 'react'

/**
 * BrandCard - A reusable card component for displaying brand collections or products
 * @param {Object} props - Component props
 * @param {string} props.title - Card title
 * @param {string} props.description - Card description
 * @param {string} props.image - Image URL or SVG
 * @param {string} props.link - Link URL
 * @param {string} props.className - Additional CSS classes
 */
export default function BrandCard({ 
  title, 
  description, 
  image, 
  link = '#',
  className = '' 
}) {
  return (
    <article 
      className={`group relative overflow-hidden rounded-2xl bg-white shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 ${className}`}
      role="article"
      aria-label={`${title} collection card`}
    >
      <a 
        href={link}
        className="block focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900"
        aria-label={`View ${title} collection`}
      >
        <div className="aspect-[4/3] overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
          {image ? (
            <img 
              src={image}
              alt={title}
              loading="lazy"
              className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
          ) : (
            <div className="flex h-full items-center justify-center">
              <svg 
                className="h-24 w-24 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path 
                  strokeLinecap="round" 
                  strokeLinejoin="round" 
                  strokeWidth={1.5} 
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" 
                />
              </svg>
            </div>
          )}
        </div>
        
        <div className="p-6">
          <h3 className="mb-2 text-xl font-semibold text-gray-900 group-hover:text-gray-700 transition-colors">
            {title}
          </h3>
          {description && (
            <p className="text-sm text-gray-600 line-clamp-2">
              {description}
            </p>
          )}
        </div>

        {/* Decorative gradient overlay on hover */}
        <div 
          className="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
          aria-hidden="true"
        />
      </a>
    </article>
  )
}
