<template>
  <div class="pb-20 md:pb-0">
    <!-- Welcome section -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primary mb-2">Welcome back, {{ riderName }}!</h1>
      <p class="text-text-light">Here's your delivery overview for today</p>
    </div>
    
    <!-- Stats cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-primary">{{ stats.pending }}</p>
            <p class="text-sm text-text-light">Pending</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-primary">{{ stats.inProgress }}</p>
            <p class="text-sm text-text-light">In Progress</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-primary">{{ stats.completed }}</p>
            <p class="text-sm text-text-light">Completed</p>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-accent/20 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-primary">₱{{ stats.earnings }}</p>
            <p class="text-sm text-text-light">Today's Earnings</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Recent orders section -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-primary">Recent Orders</h2>
        <router-link to="/orders" class="text-accent hover:underline text-sm font-medium">
          View all →
        </router-link>
      </div>
      
      <!-- Loading state -->
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="card animate-pulse">
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
      <div v-else-if="recentOrders.length" class="space-y-4">
        <OrderCard 
          v-for="order in recentOrders" 
          :key="order.id" 
          :order="order"
          @accept="handleAccept"
          @decline="handleDecline"
          @complete="handleComplete"
        />
      </div>
      
      <!-- Empty state -->
      <div v-else class="card text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-text-main mb-2">No active orders</h3>
        <p class="text-text-light">New orders will appear here when available</p>
      </div>
    </div>
    
    <!-- Quick actions -->
    <div>
      <h2 class="text-xl font-bold text-primary mb-4">Quick Actions</h2>
      <div class="grid grid-cols-2 gap-4">
        <router-link to="/orders" class="card text-center hover:border-accent border-2 border-transparent">
          <div class="w-12 h-12 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <p class="font-medium text-text-main">View Orders</p>
        </router-link>
        
        <router-link to="/history" class="card text-center hover:border-accent border-2 border-transparent">
          <div class="w-12 h-12 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <p class="font-medium text-text-main">Order History</p>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import OrderCard from '../components/OrderCard.vue'
import api from '../services/api'

const loading = ref(true)
const orders = ref([])
const stats = ref({
  pending: 0,
  inProgress: 0,
  completed: 0,
  earnings: 0
})

const riderData = computed(() => {
  const stored = localStorage.getItem('rider_data')
  if (stored) {
    try {
      return JSON.parse(stored)
    } catch {
      return null
    }
  }
  return null
})

const riderName = computed(() => {
  return riderData.value?.name?.split(' ')[0] || 'Rider'
})

const recentOrders = computed(() => {
  return orders.value.slice(0, 5)
})

const fetchOrders = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/orders')
    orders.value = response.data.orders || []
    
    // Calculate stats
    stats.value = {
      pending: orders.value.filter(o => o.status === 'pending').length,
      inProgress: orders.value.filter(o => o.status === 'accepted' || o.status === 'in_progress').length,
      completed: response.data.stats?.completed || 0,
      earnings: response.data.stats?.earnings || 0
    }
  } catch (err) {
    console.error('Failed to fetch orders:', err)
  } finally {
    loading.value = false
  }
}

const handleAccept = async (orderId) => {
  try {
    await api.post(`/api/orders/${orderId}/accept`)
    fetchOrders()
  } catch (err) {
    console.error('Failed to accept order:', err)
  }
}

const handleDecline = async (orderId) => {
  orders.value = orders.value.filter(o => o.id !== orderId)
}

const handleComplete = async (orderId) => {
  try {
    await api.post(`/api/orders/${orderId}/complete`)
    fetchOrders()
  } catch (err) {
    console.error('Failed to complete order:', err)
  }
}

onMounted(() => {
  fetchOrders()
})
</script>
