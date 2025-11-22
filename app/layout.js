import './globals.css'

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
              <a href="/" className="text-2xl font-bold">ShoeTakels</a>
              <div className="flex gap-6">
                <a href="/" className="hover:text-gray-600 transition-colors">Home</a>
                <a href="/brand" className="hover:text-gray-600 transition-colors">Brand</a>
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
