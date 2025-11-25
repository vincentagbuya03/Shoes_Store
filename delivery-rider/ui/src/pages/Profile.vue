<template>
  <div class="pb-20 md:pb-0">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primary mb-2">My Profile</h1>
      <p class="text-text-light">Manage your account settings</p>
    </div>
    
    <!-- Profile card -->
    <div class="card mb-6">
      <div class="flex flex-col sm:flex-row items-center gap-4">
        <!-- Avatar -->
        <div class="w-24 h-24 bg-accent/20 rounded-full flex items-center justify-center">
          <span class="text-3xl font-bold text-accent">{{ riderInitials }}</span>
        </div>
        
        <!-- Info -->
        <div class="text-center sm:text-left flex-1">
          <h2 class="text-xl font-bold text-primary">{{ profile.name }}</h2>
          <p class="text-text-light">{{ profile.email }}</p>
          <div class="flex items-center justify-center sm:justify-start gap-2 mt-2">
            <span 
              class="px-3 py-1 rounded-full text-sm font-medium"
              :class="profile.is_verified ? 'bg-success/10 text-success' : 'bg-yellow-100 text-yellow-700'"
            >
              {{ profile.is_verified ? 'Verified Rider' : 'Pending Verification' }}
            </span>
          </div>
        </div>
        
        <!-- Edit button -->
        <button class="btn-secondary">
          Edit Profile
        </button>
      </div>
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="card text-center">
        <p class="text-3xl font-bold text-primary mb-1">{{ profile.total_deliveries || 0 }}</p>
        <p class="text-sm text-text-light">Total Deliveries</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-bold text-primary mb-1">{{ profile.rating || '4.9' }}</p>
        <p class="text-sm text-text-light">Average Rating</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-bold text-primary mb-1">₱{{ profile.total_earnings || 0 }}</p>
        <p class="text-sm text-text-light">Total Earnings</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-bold text-primary mb-1">{{ profile.member_since || '2024' }}</p>
        <p class="text-sm text-text-light">Member Since</p>
      </div>
    </div>
    
    <!-- Profile details -->
    <div class="card mb-6">
      <h2 class="text-lg font-bold text-primary mb-4">Personal Information</h2>
      
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Full Name</label>
            <input 
              type="text" 
              :value="profile.name" 
              disabled 
              class="input-field bg-gray-50"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Email Address</label>
            <input 
              type="email" 
              :value="profile.email" 
              disabled 
              class="input-field bg-gray-50"
            >
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Phone Number</label>
            <input 
              type="tel" 
              :value="profile.phone || '+63 912 345 6789'" 
              disabled 
              class="input-field bg-gray-50"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Vehicle Type</label>
            <input 
              type="text" 
              :value="profile.vehicle_type || 'Motorcycle'" 
              disabled 
              class="input-field bg-gray-50"
            >
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-text-light mb-1">Address</label>
          <textarea 
            :value="profile.address || 'Metro Manila, Philippines'" 
            disabled 
            rows="2"
            class="input-field bg-gray-50 resize-none"
          ></textarea>
        </div>
      </div>
    </div>
    
    <!-- Account settings -->
    <div class="card">
      <h2 class="text-lg font-bold text-primary mb-4">Account Settings</h2>
      
      <div class="space-y-3">
        <div class="flex items-center justify-between py-3 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="font-medium text-text-main">Push Notifications</span>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="notifications" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between py-3 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="font-medium text-text-main">Location Sharing</span>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="locationSharing" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
          </label>
        </div>
        
        <button class="flex items-center gap-3 py-3 text-text-main hover:text-accent transition-colors w-full">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
          <span class="font-medium">Change Password</span>
        </button>
        
        <button class="flex items-center gap-3 py-3 text-error hover:opacity-80 transition-opacity w-full">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          <span class="font-medium">Log Out</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const loading = ref(true)
const profile = ref({
  name: 'John Doe',
  email: 'rider@shoetakels.com',
  phone: '+63 912 345 6789',
  address: 'Metro Manila, Philippines',
  vehicle_type: 'Motorcycle',
  is_verified: true,
  total_deliveries: 156,
  rating: '4.9',
  total_earnings: 45680,
  member_since: '2024'
})

const notifications = ref(true)
const locationSharing = ref(true)

const riderInitials = computed(() => {
  const name = profile.value.name
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})

const fetchProfile = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/profile')
    if (response.data.rider) {
      profile.value = { ...profile.value, ...response.data.rider }
    }
  } catch (err) {
    console.error('Failed to fetch profile:', err)
    // Use stored data as fallback
    const stored = localStorage.getItem('rider_data')
    if (stored) {
      try {
        const data = JSON.parse(stored)
        profile.value = { ...profile.value, ...data }
      } catch {
        // Use default profile
      }
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProfile()
})
</script>
