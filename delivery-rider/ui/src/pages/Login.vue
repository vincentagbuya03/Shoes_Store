<template>
  <div class="min-h-screen bg-gradient-to-br from-light to-gray-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img 
          :src="logoSrc" 
          alt="ShoeTakels Logo" 
          class="h-16 mx-auto mb-4"
          @error="handleLogoError"
        >
        <h1 class="text-3xl font-bold text-primary">Rider Dashboard</h1>
        <p class="text-text-light mt-2">Sign in to manage your deliveries</p>
      </div>

      <!-- Login Form -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <form @submit.prevent="handleLogin">
          <!-- Error Message -->
          <div 
            v-if="error" 
            class="mb-6 p-4 bg-red-50 border border-red-200 text-error rounded-lg text-sm"
          >
            {{ error }}
          </div>

          <!-- Email Field -->
          <div class="mb-6">
            <label for="email" class="block text-sm font-semibold text-primary mb-2">
              Email Address
            </label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              placeholder="rider@shoetakels.com"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition"
            >
          </div>

          <!-- Password Field -->
          <div class="mb-6">
            <label for="password" class="block text-sm font-semibold text-primary mb-2">
              Password
            </label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              placeholder="Enter your password"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition"
            >
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
              <input type="checkbox" v-model="rememberMe" class="rounded border-gray-300 text-accent focus:ring-accent">
              <span class="ml-2 text-sm text-text-light">Remember me</span>
            </label>
            <a href="#" class="text-sm text-accent hover:underline">Forgot password?</a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 bg-primary text-white font-semibold rounded-lg hover:bg-gray-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Signing in...
            </span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-6">
          <div class="flex-1 border-t border-gray-200"></div>
          <span class="px-4 text-sm text-text-light">OR</span>
          <div class="flex-1 border-t border-gray-200"></div>
        </div>

        <!-- Alternative Login Note -->
        <p class="text-center text-sm text-text-light">
          Have an existing ShoeTakels account?
          <a href="/login.php" class="text-accent hover:underline font-semibold">
            Use store login
          </a>
        </p>
      </div>

      <!-- Demo Credentials -->
      <div class="mt-6 p-4 bg-white/50 rounded-lg text-center">
        <p class="text-sm text-text-light">
          <strong>Demo:</strong> rider@demo.com / password123
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { authApi } from '../services/api'

export default {
  name: 'Login',
  data() {
    return {
      email: '',
      password: '',
      rememberMe: false,
      loading: false,
      error: null,
      logoSrc: '../../upload/picture/logo.png'
    }
  },
  methods: {
    handleLogoError() {
      // Fallback to text if logo not found
      this.logoSrc = ''
    },
    async handleLogin() {
      this.loading = true
      this.error = null

      try {
        const response = await authApi.login(this.email, this.password)
        
        if (response.data.token) {
          localStorage.setItem('token', response.data.token)
          localStorage.setItem('rider', JSON.stringify(response.data.rider))
          this.$router.push('/dashboard')
        }
      } catch (err) {
        // Demo mode: allow demo credentials when API is not available
        // NOTE: Remove this block in production - demo credentials are for testing only
        if (this.email === 'rider@demo.com' && this.password === 'password123') {
          localStorage.setItem('token', 'demo-token-12345')
          localStorage.setItem('rider', JSON.stringify({
            id: 1,
            name: 'John Rider',
            email: 'rider@demo.com',
            phone: '+1 234 567 8900',
            status: 'online'
          }))
          this.$router.push('/dashboard')
          return
        }
        
        this.error = err.response?.data?.message || 'Invalid email or password. Try demo credentials.'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
