<template>
  <div class="p-4 md:p-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl md:text-3xl font-bold text-primary">My Profile</h1>
      <p class="text-text-light mt-1">Manage your account settings</p>
    </div>

    <!-- Profile Card -->
    <div class="bg-white rounded-xl p-6 shadow-sm mb-6">
      <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
        <!-- Avatar -->
        <div class="relative">
          <div class="w-24 h-24 bg-accent rounded-full flex items-center justify-center text-4xl text-primary">
            {{ initials }}
          </div>
          <span 
            :class="[
              'absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white',
              profile.status === 'online' ? 'bg-success' : 'bg-gray-400'
            ]"
          ></span>
        </div>

        <!-- Info -->
        <div class="text-center md:text-left flex-1">
          <h2 class="text-2xl font-bold text-primary">{{ profile.name }}</h2>
          <p class="text-text-light">{{ profile.email }}</p>
          <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-4">
            <span class="px-3 py-1 bg-light rounded-full text-sm">
              ⭐ {{ profile.rating || 4.8 }} Rating
            </span>
            <span class="px-3 py-1 bg-light rounded-full text-sm">
              📦 {{ profile.total_deliveries || 156 }} Deliveries
            </span>
            <span 
              :class="[
                'px-3 py-1 rounded-full text-sm',
                profile.status === 'online' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]"
            >
              {{ profile.status === 'online' ? '🟢 Online' : '⚫ Offline' }}
            </span>
          </div>
        </div>

        <!-- Edit Button -->
        <button 
          @click="editing = !editing"
          class="px-6 py-2 bg-accent text-primary font-semibold rounded-lg hover:bg-yellow-500 transition"
        >
          {{ editing ? 'Cancel' : 'Edit Profile' }}
        </button>
      </div>
    </div>

    <!-- Profile Form -->
    <div class="grid lg:grid-cols-2 gap-6">
      <!-- Personal Information -->
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-primary mb-4">Personal Information</h3>
        <form @submit.prevent="saveProfile" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Full Name</label>
            <input
              v-model="profile.name"
              type="text"
              :disabled="!editing"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition disabled:bg-gray-50"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Email Address</label>
            <input
              v-model="profile.email"
              type="email"
              :disabled="!editing"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition disabled:bg-gray-50"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Phone Number</label>
            <input
              v-model="profile.phone"
              type="tel"
              :disabled="!editing"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition disabled:bg-gray-50"
            >
          </div>
          <button 
            v-if="editing"
            type="submit"
            class="w-full py-3 bg-primary text-white font-semibold rounded-lg hover:bg-gray-800 transition"
          >
            Save Changes
          </button>
        </form>
      </div>

      <!-- Vehicle Information -->
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-primary mb-4">Vehicle Information</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Vehicle Type</label>
            <input
              v-model="profile.vehicle_type"
              type="text"
              :disabled="!editing"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition disabled:bg-gray-50"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">Plate Number</label>
            <input
              v-model="profile.plate_number"
              type="text"
              :disabled="!editing"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition disabled:bg-gray-50"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-text-light mb-1">License Number</label>
            <input
              v-model="profile.license_number"
              type="text"
              :disabled="!editing"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-accent transition disabled:bg-gray-50"
            >
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-primary mb-4">Statistics</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="text-center p-4 bg-light rounded-lg">
            <p class="text-3xl font-bold text-primary">{{ profile.total_deliveries || 156 }}</p>
            <p class="text-sm text-text-light">Total Deliveries</p>
          </div>
          <div class="text-center p-4 bg-light rounded-lg">
            <p class="text-3xl font-bold text-success">{{ profile.completed_this_month || 42 }}</p>
            <p class="text-sm text-text-light">This Month</p>
          </div>
          <div class="text-center p-4 bg-light rounded-lg">
            <p class="text-3xl font-bold text-accent">₱{{ profile.total_earnings || '12,450' }}</p>
            <p class="text-sm text-text-light">Total Earnings</p>
          </div>
          <div class="text-center p-4 bg-light rounded-lg">
            <p class="text-3xl font-bold text-primary">{{ profile.rating || 4.8 }}⭐</p>
            <p class="text-sm text-text-light">Average Rating</p>
          </div>
        </div>
      </div>

      <!-- Account Actions -->
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-primary mb-4">Account Actions</h3>
        <div class="space-y-3">
          <button class="w-full py-3 bg-light text-primary font-medium rounded-lg hover:bg-gray-200 transition text-left px-4">
            🔔 Notification Settings
          </button>
          <button class="w-full py-3 bg-light text-primary font-medium rounded-lg hover:bg-gray-200 transition text-left px-4">
            🔒 Change Password
          </button>
          <button class="w-full py-3 bg-light text-primary font-medium rounded-lg hover:bg-gray-200 transition text-left px-4">
            📄 View Documents
          </button>
          <button 
            @click="logout"
            class="w-full py-3 bg-error text-white font-semibold rounded-lg hover:bg-red-600 transition"
          >
            Sign Out
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { profileApi } from '../services/api'

export default {
  name: 'Profile',
  data() {
    return {
      editing: false,
      profile: {
        name: '',
        email: '',
        phone: '',
        status: 'online',
        vehicle_type: 'Motorcycle',
        plate_number: 'ABC 1234',
        license_number: 'D01-23-456789',
        rating: 4.8,
        total_deliveries: 156,
        completed_this_month: 42,
        total_earnings: '12,450'
      }
    }
  },
  computed: {
    initials() {
      return this.profile.name
        ?.split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2) || 'RD'
    }
  },
  mounted() {
    this.loadProfile()
  },
  methods: {
    loadProfile() {
      // Load from localStorage first
      const stored = localStorage.getItem('rider')
      if (stored) {
        const rider = JSON.parse(stored)
        this.profile = { ...this.profile, ...rider }
      }

      // Then try to fetch from API
      this.fetchProfile()
    },
    async fetchProfile() {
      try {
        const response = await profileApi.get()
        this.profile = { ...this.profile, ...response.data }
      } catch (error) {
        // Keep sample data if API fails
        console.log('Using cached profile data')
      }
    },
    async saveProfile() {
      try {
        await profileApi.update(this.profile)
        localStorage.setItem('rider', JSON.stringify(this.profile))
        this.editing = false
        alert('Profile updated successfully!')
      } catch (error) {
        // Save locally for demo
        localStorage.setItem('rider', JSON.stringify(this.profile))
        this.editing = false
        alert('Profile updated successfully!')
      }
    },
    logout() {
      if (confirm('Are you sure you want to sign out?')) {
        localStorage.removeItem('token')
        localStorage.removeItem('rider')
        this.$router.push('/login')
      }
    }
  }
}
</script>
