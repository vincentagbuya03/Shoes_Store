<template>
  <div class="pb-20 md:pb-0">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primary mb-2">Active Orders</h1>
      <p class="text-text-light">Manage your delivery assignments</p>
    </div>
    
    <!-- Filter tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
      <button 
        v-for="filter in filters" 
        :key="filter.value"
        @click="activeFilter = filter.value"
        class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
        :class="activeFilter === filter.value 
          ? 'bg-accent text-white' 
          : 'bg-white text-text-main hover:bg-gray-100'"
      >
        {{ filter.label }} ({{ getFilterCount(filter.value) }})
      </button>
    </div>
    
    <!-- Loading state -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 4" :key="i" class="card animate-pulse">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-gray-200 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Orders list -->
    <div v-else-if="filteredOrders.length" class="space-y-4">
      <OrderCard 
        v-for="order in filteredOrders" 
        :key="order.id" 
        :order="order"
        @accept="handleAccept"
        @decline="handleDecline"
        @complete="handleComplete"
      />
    </div>
    
    <!-- Empty state -->
    <div v-else class="card text-center py-16">
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-text-main mb-2">No orders found</h3>
      <p class="text-text-light max-w-sm mx-auto">
        {{ activeFilter === 'all' 
          ? 'There are no active orders at the moment. New orders will appear here.' 
          : `No ${activeFilter} orders found.` 
        }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import OrderCard from '../components/OrderCard.vue'
import api from '../services/api'

const loading = ref(true)
const orders = ref([])
const activeFilter = ref('all')

const filters = [
  { label: 'All', value: 'all' },
  { label: 'Pending', value: 'pending' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Accepted', value: 'accepted' }
]

const filteredOrders = computed(() => {
  if (activeFilter.value === 'all') {
    return orders.value
  }
  return orders.value.filter(o => o.status === activeFilter.value)
})

const getFilterCount = (filter) => {
  if (filter === 'all') return orders.value.length
  return orders.value.filter(o => o.status === filter).length
}

const fetchOrders = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/orders')
    orders.value = response.data.orders || []
  } catch (err) {
    console.error('Failed to fetch orders:', err)
  } finally {
    loading.value = false
  }
}

const handleAccept = async (orderId) => {
  try {
    await api.post(`/api/orders/${orderId}/accept`)
    // Update local state
    const order = orders.value.find(o => o.id === orderId)
    if (order) {
      order.status = 'accepted'
    }
  } catch (err) {
    console.error('Failed to accept order:', err)
  }
}

const handleDecline = async (orderId) => {
  // Remove from local state (in production, would make API call)
  orders.value = orders.value.filter(o => o.id !== orderId)
}

const handleComplete = async (orderId) => {
  try {
    await api.post(`/api/orders/${orderId}/complete`)
    // Remove from active orders
    orders.value = orders.value.filter(o => o.id !== orderId)
  } catch (err) {
    console.error('Failed to complete order:', err)
  }
}

onMounted(() => {
  fetchOrders()
})
</script>
