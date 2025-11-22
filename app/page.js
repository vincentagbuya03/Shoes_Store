import Link from 'next/link'

export default function Home() {
  return (
    <main className="min-h-screen">
      <section className="container mx-auto px-4 py-16">
        <div className="text-center">
          <h1 className="text-5xl font-bold mb-6">Welcome to ShoeTakels</h1>
          <p className="text-xl text-gray-600 mb-8">
            Discover premium shoe brands and exclusive collections
          </p>
          <Link 
            href="/brand"
            className="inline-block bg-black text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition-colors"
          >
            Explore Brands
          </Link>
        </div>
      </section>
    </main>
  )
}
