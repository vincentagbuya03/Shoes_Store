import axios from 'axios'

// Create axios instance with base URL
// In development, Vite proxy handles /api requests to the backend
// In production, set VITE_API_BASE_URL to point to the API server
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('rider')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Auth API
export const authApi = {
  login: (email, password) => api.post('/api/auth/login', { email, password })
}

// Orders API
export const ordersApi = {
  getActive: () => api.get('/api/orders'),
  getHistory: () => api.get('/api/orders/history'),
  getById: (id) => api.get(`/api/orders/${id}`),
  accept: (id) => api.post(`/api/orders/${id}/accept`),
  complete: (id) => api.post(`/api/orders/${id}/complete`)
}

// Profile API
export const profileApi = {
  get: () => api.get('/api/profile'),
  update: (data) => api.put('/api/profile', data)
}

export default api
