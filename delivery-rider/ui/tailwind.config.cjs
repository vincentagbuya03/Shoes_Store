/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}"
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1a1a1a',
        accent: '#d4a574',
        light: '#f5f5f5',
        'text-main': '#333333',
        'text-light': '#666666',
        success: '#10b981',
        error: '#ef4444'
      },
      fontFamily: {
        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'sans-serif']
      }
    }
  },
  plugins: []
}
