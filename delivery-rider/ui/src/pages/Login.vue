<template>
  <div class="min-h-screen bg-light flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
      <!-- Logo and header -->
      <div class="text-center mb-8">
        <img 
          :src="logoSrc" 
          alt="ShoeTakels Logo" 
          class="h-16 w-16 mx-auto mb-4 object-contain"
          @error="handleLogoError"
        >
        <h1 class="text-2xl font-bold text-primary">Delivery Rider</h1>
        <p class="text-text-light mt-2">Sign in to your rider account</p>
      </div>
      
      <!-- Error message -->
      <div 
        v-if="error" 
        class="bg-error/10 text-error px-4 py-3 rounded-lg mb-6 text-sm"
      >
        {{ error }}
      </div>
      
      <!-- Login form -->
      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label for="email" class="block text-sm font-medium text-text-main mb-2">
            Email Address
          </label>
          <input 
            id="email"
            v-model="email" 
            type="email" 
            placeholder="your@email.com"
            class="input-field"
            required
          >
        </div>
        
        <div>
          <label for="password" class="block text-sm font-medium text-text-main mb-2">
            Password
          </label>
          <input 
            id="password"
            v-model="password" 
            type="password" 
            placeholder="Enter your password"
            class="input-field"
            required
          >
        </div>
        
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              type="checkbox" 
              v-model="rememberMe"
              class="w-4 h-4 rounded border-gray-300 text-accent focus:ring-accent"
            >
            <span class="text-sm text-text-light">Remember me</span>
          </label>
          
          <a href="#" class="text-sm text-accent hover:underline">
            Forgot password?
          </a>
        </div>
        
        <button 
          type="submit" 
          class="btn-primary w-full py-3"
          :disabled="loading"
        >
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            Signing in...
          </span>
          <span v-else>Sign In</span>
        </button>
      </form>
      
      <!-- Divider -->
      <div class="flex items-center my-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="px-4 text-sm text-text-light">or</span>
        <div class="flex-1 h-px bg-gray-200"></div>
      </div>
      
      <!-- Alternative login note -->
      <div class="text-center">
        <p class="text-sm text-text-light mb-4">
          Already have a ShoeTakels account?
        </p>
        <a 
          href="/login.php" 
          class="btn-secondary inline-block"
        >
          Use Main Login
        </a>
      </div>
      
      <!-- Footer note -->
      <p class="text-center text-xs text-text-light mt-8">
        This is a sample login page. See the README for instructions on 
        integrating with the existing ShoeTakels authentication system.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const loading = ref(false)
const error = ref('')

const logoSrc = ref('/logo.png')

const handleLogoError = () => {
  logoSrc.value = '../../upload/picture/logo.png'
}

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await api.post('/api/auth/login', {
      email: email.value,
      password: password.value
    })
    
    // Store token and rider data
    localStorage.setItem('rider_token', response.data.token)
    localStorage.setItem('rider_data', JSON.stringify(response.data.rider))
    
    // Redirect to dashboard
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
