<script setup>
import { ref, computed } from 'vue'

const selectedPeriod = ref('week')

const earningsData = ref({
  today: {
    total: 1250,
    deliveries: 12,
    tips: 150,
    bonuses: 100
  },
  week: {
    total: 8750,
    deliveries: 78,
    tips: 980,
    bonuses: 500
  },
  month: {
    total: 35200,
    deliveries: 312,
    tips: 4200,
    bonuses: 2000
  }
})

const weeklyBreakdown = ref([
  { day: 'Mon', earnings: 1200, deliveries: 10 },
  { day: 'Tue', earnings: 1450, deliveries: 12 },
  { day: 'Wed', earnings: 980, deliveries: 8 },
  { day: 'Thu', earnings: 1680, deliveries: 14 },
  { day: 'Fri', earnings: 1890, deliveries: 16 },
  { day: 'Sat', earnings: 1100, deliveries: 9 },
  { day: 'Sun', earnings: 450, deliveries: 4 }
])

const transactions = ref([
  { id: 'TXN-001', type: 'delivery', description: 'Order ORD-098 completed', amount: 120, date: 'Today, 2:30 PM' },
  { id: 'TXN-002', type: 'tip', description: 'Tip from Pedro Reyes', amount: 50, date: 'Today, 2:30 PM' },
  { id: 'TXN-003', type: 'delivery', description: 'Order ORD-097 completed', amount: 120, date: 'Today, 11:45 AM' },
  { id: 'TXN-004', type: 'bonus', description: 'Peak hour bonus', amount: 100, date: 'Today, 12:00 PM' },
  { id: 'TXN-005', type: 'delivery', description: 'Order ORD-096 completed', amount: 150, date: 'Yesterday, 5:20 PM' }
])

const currentEarnings = computed(() => earningsData.value[selectedPeriod.value])

const maxEarning = computed(() => Math.max(...weeklyBreakdown.value.map(d => d.earnings)))

const getBarHeight = (earnings) => {
  return (earnings / maxEarning.value) * 100
}

const getTransactionIcon = (type) => {
  switch(type) {
    case 'delivery': return 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
    case 'tip': return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'
    case 'bonus': return 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    default: return ''
  }
}

const getTransactionColor = (type) => {
  switch(type) {
    case 'delivery': return 'bg-blue-100 text-blue-600'
    case 'tip': return 'bg-pink-100 text-pink-600'
    case 'bonus': return 'bg-green-100 text-green-600'
    default: return 'bg-gray-100 text-gray-600'
  }
}
</script>

<template>
  <div class="pt-12 lg:pt-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Earnings</h1>
        <p class="text-gray-600 mt-1">Track your income and performance</p>
      </div>
      
      <!-- Period Filter -->
      <div class="flex bg-gray-100 rounded-lg p-1">
        <button 
          @click="selectedPeriod = 'today'"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
            selectedPeriod === 'today' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Today
        </button>
        <button 
          @click="selectedPeriod = 'week'"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
            selectedPeriod === 'week' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          This Week
        </button>
        <button 
          @click="selectedPeriod = 'month'"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
            selectedPeriod === 'month' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          This Month
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 lg:p-6 text-white">
        <p class="text-green-100 text-sm mb-1">Total Earnings</p>
        <p class="text-2xl lg:text-3xl font-bold">₱{{ currentEarnings.total.toLocaleString() }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6">
        <p class="text-gray-500 text-sm mb-1">Deliveries</p>
        <p class="text-2xl lg:text-3xl font-bold text-gray-800">{{ currentEarnings.deliveries }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6">
        <p class="text-gray-500 text-sm mb-1">Tips</p>
        <p class="text-2xl lg:text-3xl font-bold text-pink-600">₱{{ currentEarnings.tips.toLocaleString() }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6">
        <p class="text-gray-500 text-sm mb-1">Bonuses</p>
        <p class="text-2xl lg:text-3xl font-bold text-orange-500">₱{{ currentEarnings.bonuses.toLocaleString() }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Weekly Chart -->
      <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-4 lg:p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-6">Weekly Overview</h2>
        <div class="flex items-end justify-between h-48 gap-2">
          <div 
            v-for="day in weeklyBreakdown" 
            :key="day.day"
            class="flex-1 flex flex-col items-center"
          >
            <div class="w-full flex justify-center mb-2">
              <div 
                :style="{ height: getBarHeight(day.earnings) + '%' }"
                class="w-8 lg:w-12 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg transition-all duration-300 hover:from-blue-700 hover:to-blue-500 relative group"
              >
                <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                  ₱{{ day.earnings.toLocaleString() }}
                </div>
              </div>
            </div>
            <p class="text-xs text-gray-500 font-medium">{{ day.day }}</p>
          </div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Recent Transactions</h2>
        <div class="space-y-3">
          <div 
            v-for="txn in transactions" 
            :key="txn.id"
            class="flex items-center gap-3"
          >
            <div :class="[getTransactionColor(txn.type), 'p-2 rounded-lg']">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTransactionIcon(txn.type)" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ txn.description }}</p>
              <p class="text-xs text-gray-500">{{ txn.date }}</p>
            </div>
            <p class="text-sm font-semibold text-green-600">+₱{{ txn.amount }}</p>
          </div>
        </div>
        <button class="w-full mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">
          View All Transactions
        </button>
      </div>
    </div>
  </div>
</template>
