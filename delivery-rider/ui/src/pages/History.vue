<template>
  <div class="pb-20 md:pb-0">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primary mb-2">Order History</h1>
      <p class="text-text-light">View your completed deliveries</p>
    </div>
    
    <!-- Stats summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-success/20 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div>
            <p class="text-xl font-bold text-primary">{{ stats.completed }}</p>
            <p class="text-xs text-text-light">Completed</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-error/20 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
          <div>
            <p class="text-xl font-bold text-primary">{{ stats.cancelled }}</p>
            <p class="text-xs text-text-light">Cancelled</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-xl font-bold text-primary">₱{{ stats.totalEarnings }}</p>
            <p class="text-xs text-text-light">Total Earnings</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
          <div>
            <p class="text-xl font-bold text-primary">{{ stats.avgDistance }}</p>
            <p class="text-xs text-text-light">Avg Distance</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Date filter -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
      <button 
        v-for="period in periods" 
        :key="period.value"
        @click="activePeriod = period.value"
        class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
        :class="activePeriod === period.value 
          ? 'bg-accent text-white' 
          : 'bg-white text-text-main hover:bg-gray-100'"
      >
        {{ period.label }}
      </button>
    </div>
    
    <!-- Loading state -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="card animate-pulse">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-gray-200 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Orders list -->
    <div v-else-if="historyOrders.length" class="space-y-4">
      <div 
        v-for="order in historyOrders" 
        :key="order.id"
        class="card cursor-pointer hover:border-accent border-2 border-transparent"
        @click="viewOrder(order.id)"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm font-bold text-primary">Order #{{ order.id }}</span>
              <span 
                class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="order.status === 'completed' ? 'bg-success/10 text-success' : 'bg-error/10 text-error'"
              >
                {{ order.status }}
              </span>
            </div>
            
            <p class="font-medium text-text-main mb-1">{{ order.customer_name }}</p>
            
            <div class="flex items-center gap-4 text-sm text-text-light">
              <span>{{ formatDate(order.completed_at || order.created_at) }}</span>
              <span>{{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}</span>
              <span>{{ order.distance || '~2.5 km' }}</span>
            </div>
          </div>
          
          <div class="text-right">
            <p class="font-bold text-primary">₱{{ order.total || order.delivery_fee || 50 }}</p>
            <p class="text-xs text-text-light">Earnings</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="card text-center py-16">
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-text-main mb-2">No delivery history</h3>
      <p class="text-text-light max-w-sm mx-auto">
        Your completed deliveries will appear here. Start accepting orders to build your history.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const loading = ref(true)
const historyOrders = ref([])
const activePeriod = ref('all')

const stats = ref({
  completed: 0,
  cancelled: 0,
  totalEarnings: 0,
  avgDistance: '0 km'
})

const periods = [
  { label: 'All Time', value: 'all' },
  { label: 'Today', value: 'today' },
  { label: 'This Week', value: 'week' },
  { label: 'This Month', value: 'month' }
]

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric'
  })
}

const viewOrder = (orderId) => {
  router.push(`/orders/${orderId}`)
}

const fetchHistory = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/orders/history')
    historyOrders.value = response.data.orders || []
    
    if (response.data.stats) {
      stats.value = response.data.stats
    } else {
      // Calculate stats from orders
      const completed = historyOrders.value.filter(o => o.status === 'completed')
      const cancelled = historyOrders.value.filter(o => o.status === 'cancelled')
      stats.value = {
        completed: completed.length,
        cancelled: cancelled.length,
        totalEarnings: completed.reduce((sum, o) => sum + (o.delivery_fee || 50), 0),
        avgDistance: '2.8 km'
      }
    }
  } catch (err) {
    console.error('Failed to fetch history:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHistory()
})
</script>
