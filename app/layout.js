import './globals.css'
import Link from 'next/link'

export const metadata = {
  title: 'ShoeTakels - Premium Shoe Brands',
  description: 'Discover premium shoe brands and collections',
}

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body className="font-sans antialiased">
        <nav className="border-b">
          <div className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <Link href="/" className="text-2xl font-bold">ShoeTakels</Link>
              <div className="flex gap-6">
                <Link href="/" className="hover:text-gray-600 transition-colors">Home</Link>
                <Link href="/brand" className="hover:text-gray-600 transition-colors">Brand</Link>
                <a href="/index.php" className="hover:text-gray-600 transition-colors">Shop</a>
              </div>
            </div>
          </div>
        </nav>
        {children}
      </body>
    </html>
  )
}
