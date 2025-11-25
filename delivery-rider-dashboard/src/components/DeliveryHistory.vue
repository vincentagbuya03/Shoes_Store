<script setup>
import { ref, computed } from 'vue'

const selectedFilter = ref('all')

const history = ref([
  {
    id: 'ORD-098',
    customer: 'Pedro Reyes',
    address: '321 Ayala Ave, Makati City',
    items: 'Nike Dunk Low (Size 43)',
    status: 'delivered',
    date: '2024-01-15',
    time: '2:30 PM',
    amount: '₱6,200',
    rating: 5
  },
  {
    id: 'ORD-097',
    customer: 'Lisa Tan',
    address: '567 Shaw Blvd, Mandaluyong',
    items: 'New Balance 574 (Size 39)',
    status: 'delivered',
    date: '2024-01-15',
    time: '11:45 AM',
    amount: '₱4,500',
    rating: 4
  },
  {
    id: 'ORD-096',
    customer: 'Mark Lim',
    address: '890 Ortigas Ave, Pasig City',
    items: 'Puma RS-X (Size 41)',
    status: 'delivered',
    date: '2024-01-14',
    time: '5:20 PM',
    amount: '₱5,800',
    rating: 5
  },
  {
    id: 'ORD-095',
    customer: 'Karen Sy',
    address: '123 Katipunan Ave, Quezon City',
    items: 'Vans Old Skool (Size 37)',
    status: 'cancelled',
    date: '2024-01-14',
    time: '3:15 PM',
    amount: '₱3,200',
    rating: null
  },
  {
    id: 'ORD-094',
    customer: 'Michael Go',
    address: '456 Tomas Morato, Quezon City',
    items: 'Reebok Classic (Size 44)',
    status: 'delivered',
    date: '2024-01-14',
    time: '12:00 PM',
    amount: '₱4,200',
    rating: 5
  },
  {
    id: 'ORD-093',
    customer: 'Anna Chua',
    address: '789 McKinley Rd, Taguig',
    items: 'Jordan 1 Low (Size 40)',
    status: 'delivered',
    date: '2024-01-13',
    time: '4:45 PM',
    amount: '₱7,500',
    rating: 4
  }
])

const filteredHistory = computed(() => {
  if (selectedFilter.value === 'all') return history.value
  return history.value.filter(item => item.status === selectedFilter.value)
})

const getStatusClass = (status) => {
  switch(status) {
    case 'delivered': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status) => {
  switch(status) {
    case 'delivered': return 'Delivered'
    case 'cancelled': return 'Cancelled'
    default: return status
  }
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
  <div class="pt-12 lg:pt-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Delivery History</h1>
        <p class="text-gray-600 mt-1">View your past deliveries and ratings</p>
      </div>
      
      <!-- Filter -->
      <div class="flex gap-2">
        <button 
          @click="selectedFilter = 'all'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            selectedFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          All
        </button>
        <button 
          @click="selectedFilter = 'delivered'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            selectedFilter === 'delivered' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Delivered
        </button>
        <button 
          @click="selectedFilter = 'cancelled'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            selectedFilter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Cancelled
        </button>
      </div>
    </div>

    <!-- History List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 lg:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
              <th class="text-left px-4 lg:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Customer</th>
              <th class="text-left px-4 lg:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Date & Time</th>
              <th class="text-left px-4 lg:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
              <th class="text-left px-4 lg:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Rating</th>
              <th class="text-right px-4 lg:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in filteredHistory" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 lg:px-6 py-4">
                <div class="font-medium text-gray-900">{{ item.id }}</div>
                <div class="text-sm text-gray-500 sm:hidden">{{ item.customer }}</div>
              </td>
              <td class="px-4 lg:px-6 py-4 hidden sm:table-cell">
                <div class="text-sm text-gray-900">{{ item.customer }}</div>
                <div class="text-xs text-gray-500 truncate max-w-[200px]">{{ item.items }}</div>
              </td>
              <td class="px-4 lg:px-6 py-4 hidden lg:table-cell">
                <div class="text-sm text-gray-900">{{ formatDate(item.date) }}</div>
                <div class="text-xs text-gray-500">{{ item.time }}</div>
              </td>
              <td class="px-4 lg:px-6 py-4">
                <span :class="[getStatusClass(item.status), 'text-xs font-medium px-2.5 py-0.5 rounded-full']">
                  {{ getStatusLabel(item.status) }}
                </span>
              </td>
              <td class="px-4 lg:px-6 py-4 hidden md:table-cell">
                <div v-if="item.rating" class="flex items-center gap-1">
                  <span v-for="i in 5" :key="i" :class="i <= item.rating ? 'text-yellow-400' : 'text-gray-300'">★</span>
                </div>
                <span v-else class="text-gray-400 text-sm">—</span>
              </td>
              <td class="px-4 lg:px-6 py-4 text-right">
                <span class="font-semibold text-gray-900">{{ item.amount }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="filteredHistory.length === 0" class="p-12 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p class="text-gray-500">No deliveries found</p>
      </div>
    </div>
  </div>
</template>
