<script setup>
import { ref, computed } from 'vue'

const stats = ref([
  { label: 'Today\'s Deliveries', value: 12, icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', color: 'blue' },
  { label: 'Active Orders', value: 3, icon: 'M13 10V3L4 14h7v7l9-11h-7z', color: 'orange' },
  { label: 'Today\'s Earnings', value: '₱1,250', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color: 'green' },
  { label: 'Rating', value: '4.8 ★', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', color: 'yellow' }
])

const activeDeliveries = ref([
  {
    id: 'ORD-001',
    customer: 'Maria Santos',
    address: '123 Rizal St, Makati City',
    items: 'Nike Air Max 270 (Size 42)',
    status: 'picked_up',
    eta: '15 mins',
    amount: '₱5,500'
  },
  {
    id: 'ORD-002',
    customer: 'Juan Cruz',
    address: '456 Bonifacio Ave, Taguig',
    items: 'Adidas Ultraboost 22 (Size 40)',
    status: 'in_transit',
    eta: '25 mins',
    amount: '₱8,200'
  },
  {
    id: 'ORD-003',
    customer: 'Ana Garcia',
    address: '789 EDSA, Quezon City',
    items: 'Converse Chuck Taylor (Size 38)',
    status: 'pending',
    eta: '45 mins',
    amount: '₱3,800'
  }
])

const getStatusClass = (status) => {
  switch(status) {
    case 'picked_up': return 'bg-blue-100 text-blue-800'
    case 'in_transit': return 'bg-orange-100 text-orange-800'
    case 'pending': return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status) => {
  switch(status) {
    case 'picked_up': return 'Picked Up'
    case 'in_transit': return 'In Transit'
    case 'pending': return 'Pending'
    default: return status
  }
}

const colorClasses = {
  blue: 'bg-blue-500',
  orange: 'bg-orange-500',
  green: 'bg-green-500',
  yellow: 'bg-yellow-500'
}
</script>

<template>
  <div class="pt-12 lg:pt-0">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Welcome back, John! 👋</h1>
      <p class="text-gray-600 mt-1">Here's what's happening with your deliveries today.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
      <div 
        v-for="stat in stats" 
        :key="stat.label"
        class="bg-white rounded-xl shadow-sm p-4 lg:p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between mb-3">
          <div :class="[colorClasses[stat.color], 'p-2 lg:p-3 rounded-lg']">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
            </svg>
          </div>
        </div>
        <p class="text-xl lg:text-2xl font-bold text-gray-800">{{ stat.value }}</p>
        <p class="text-xs lg:text-sm text-gray-500">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Active Deliveries Section -->
    <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg lg:text-xl font-bold text-gray-800">Active Deliveries</h2>
        <span class="bg-orange-100 text-orange-800 text-xs lg:text-sm font-medium px-3 py-1 rounded-full">
          {{ activeDeliveries.length }} Orders
        </span>
      </div>

      <div class="space-y-4">
        <div 
          v-for="delivery in activeDeliveries" 
          :key="delivery.id"
          class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50/50 transition-all"
        >
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-semibold text-gray-800">{{ delivery.id }}</span>
                <span :class="[getStatusClass(delivery.status), 'text-xs px-2 py-0.5 rounded-full']">
                  {{ getStatusLabel(delivery.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-600 mb-1">
                <span class="font-medium">Customer:</span> {{ delivery.customer }}
              </p>
              <p class="text-sm text-gray-600 mb-1">
                <span class="font-medium">Address:</span> {{ delivery.address }}
              </p>
              <p class="text-sm text-gray-600">
                <span class="font-medium">Items:</span> {{ delivery.items }}
              </p>
            </div>
            <div class="flex flex-row lg:flex-col items-center lg:items-end gap-4 lg:gap-2">
              <div class="text-right">
                <p class="text-lg font-bold text-green-600">{{ delivery.amount }}</p>
                <p class="text-xs text-gray-500">ETA: {{ delivery.eta }}</p>
              </div>
              <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
                View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
