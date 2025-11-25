<script setup>
import { ref } from 'vue'

const profile = ref({
  name: 'John Doe',
  email: 'john.doe@example.com',
  phone: '+63 917 123 4567',
  vehicleType: 'Motorcycle',
  vehiclePlate: 'ABC 1234',
  address: '123 Sample Street, Makati City',
  joinDate: 'January 2023'
})

const stats = ref({
  totalDeliveries: 1248,
  averageRating: 4.8,
  completionRate: 98,
  onTimeRate: 95
})

const isEditing = ref(false)
const editedProfile = ref({ ...profile.value })

const startEditing = () => {
  editedProfile.value = { ...profile.value }
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
}

const saveProfile = () => {
  profile.value = { ...editedProfile.value }
  isEditing.value = false
}
</script>

<template>
  <div class="pt-12 lg:pt-0">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Profile Settings</h1>
      <p class="text-gray-600 mt-1">Manage your account information</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Profile Card -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <!-- Profile Header -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
            <div class="flex items-center gap-4">
              <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center text-3xl font-bold">
                JD
              </div>
              <div>
                <h2 class="text-2xl font-bold">{{ profile.name }}</h2>
                <p class="text-blue-200">Delivery Partner since {{ profile.joinDate }}</p>
              </div>
            </div>
          </div>

          <!-- Profile Details -->
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-800">Personal Information</h3>
              <button 
                v-if="!isEditing"
                @click="startEditing"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center gap-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit
              </button>
            </div>

            <form v-if="isEditing" @submit.prevent="saveProfile" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                  <input 
                    v-model="editedProfile.name"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input 
                    v-model="editedProfile.email"
                    type="email"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                  <input 
                    v-model="editedProfile.phone"
                    type="tel"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                  <input 
                    v-model="editedProfile.address"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                  <select 
                    v-model="editedProfile.vehicleType"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option>Motorcycle</option>
                    <option>Bicycle</option>
                    <option>Car</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Plate</label>
                  <input 
                    v-model="editedProfile.vehiclePlate"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>
              <div class="flex gap-3 pt-4">
                <button 
                  type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                >
                  Save Changes
                </button>
                <button 
                  type="button"
                  @click="cancelEditing"
                  class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p class="text-sm text-gray-500 mb-1">Email</p>
                <p class="text-gray-800">{{ profile.email }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 mb-1">Phone</p>
                <p class="text-gray-800">{{ profile.phone }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 mb-1">Address</p>
                <p class="text-gray-800">{{ profile.address }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 mb-1">Vehicle</p>
                <p class="text-gray-800">{{ profile.vehicleType }} - {{ profile.vehiclePlate }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Card -->
      <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Performance Stats</h3>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Total Deliveries</span>
                <span class="text-sm font-semibold text-gray-800">{{ stats.totalDeliveries }}</span>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Average Rating</span>
                <span class="text-sm font-semibold text-yellow-500">{{ stats.averageRating }} ★</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-yellow-500 h-2 rounded-full" :style="{ width: (stats.averageRating / 5) * 100 + '%' }"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Completion Rate</span>
                <span class="text-sm font-semibold text-green-600">{{ stats.completionRate }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full" :style="{ width: stats.completionRate + '%' }"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">On-Time Rate</span>
                <span class="text-sm font-semibold text-blue-600">{{ stats.onTimeRate }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full" :style="{ width: stats.onTimeRate + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
          <div class="space-y-2">
            <button class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
              </svg>
              <span class="text-gray-700">Change Password</span>
            </button>
            <button class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span class="text-gray-700">Notification Settings</span>
            </button>
            <button class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left hover:bg-gray-50 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-gray-700">Help & Support</span>
            </button>
            <button class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left hover:bg-red-50 transition-colors text-red-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span>Sign Out</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
