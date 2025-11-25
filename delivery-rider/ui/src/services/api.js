import axios from 'axios'

// Create axios instance with base configuration
const api = axios.create({
  // Default to local development API
  // In production, set VITE_API_BASE_URL environment variable
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('rider_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle 401 Unauthorized - redirect to login
    if (error.response?.status === 401) {
      localStorage.removeItem('rider_token')
      localStorage.removeItem('rider_data')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
