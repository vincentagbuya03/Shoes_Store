<template>
  <div class="p-4 md:p-8">
    <!-- Welcome Section -->
    <div class="mb-8">
      <h1 class="text-2xl md:text-3xl font-bold text-primary">
        Welcome back, {{ riderName }}! 👋
      </h1>
      <p class="text-text-light mt-1">Here's your delivery summary for today</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-text-light text-sm">Active Orders</p>
            <p class="text-3xl font-bold text-primary mt-1">{{ stats.activeOrders }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">📦</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-text-light text-sm">Completed Today</p>
            <p class="text-3xl font-bold text-success mt-1">{{ stats.completedToday }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">✅</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-text-light text-sm">Earnings Today</p>
            <p class="text-3xl font-bold text-accent mt-1">₱{{ stats.earningsToday }}</p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">💰</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-text-light text-sm">Rating</p>
            <p class="text-3xl font-bold text-primary mt-1">{{ stats.rating }}⭐</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">🏆</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions & Recent Orders -->
    <div class="grid lg:grid-cols-2 gap-6">
      <!-- Quick Actions -->
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-primary mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-4">
          <router-link 
            to="/orders" 
            class="p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition text-center"
          >
            <span class="text-3xl">📋</span>
            <p class="mt-2 font-medium text-primary">View Orders</p>
          </router-link>
          <router-link 
            to="/history" 
            class="p-4 bg-green-50 rounded-xl hover:bg-green-100 transition text-center"
          >
            <span class="text-3xl">📜</span>
            <p class="mt-2 font-medium text-primary">Order History</p>
          </router-link>
          <router-link 
            to="/profile" 
            class="p-4 bg-yellow-50 rounded-xl hover:bg-yellow-100 transition text-center"
          >
            <span class="text-3xl">👤</span>
            <p class="mt-2 font-medium text-primary">My Profile</p>
          </router-link>
          <button 
            @click="toggleStatus"
            :class="[
              'p-4 rounded-xl transition text-center',
              isOnline ? 'bg-red-50 hover:bg-red-100' : 'bg-green-50 hover:bg-green-100'
            ]"
          >
            <span class="text-3xl">{{ isOnline ? '🔴' : '🟢' }}</span>
            <p class="mt-2 font-medium text-primary">
              {{ isOnline ? 'Go Offline' : 'Go Online' }}
            </p>
          </button>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-primary">Recent Orders</h2>
          <router-link to="/orders" class="text-accent hover:underline text-sm">
            View all →
          </router-link>
        </div>
        
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin w-8 h-8 border-4 border-accent border-t-transparent rounded-full mx-auto"></div>
          <p class="text-text-light mt-2">Loading orders...</p>
        </div>

        <div v-else-if="recentOrders.length === 0" class="text-center py-8">
          <span class="text-4xl">📭</span>
          <p class="text-text-light mt-2">No active orders</p>
        </div>

        <div v-else class="space-y-3">
          <router-link 
            v-for="order in recentOrders" 
            :key="order.id"
            :to="`/orders/${order.id}`"
            class="block p-4 bg-light rounded-lg hover:bg-gray-100 transition"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="font-semibold text-primary">#{{ order.id }}</p>
                <p class="text-sm text-text-light">{{ order.customer_name }}</p>
              </div>
              <span 
                :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  order.status === 'accepted' ? 'bg-blue-100 text-blue-800' :
                  'bg-green-100 text-green-800'
                ]"
              >
                {{ order.status }}
              </span>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ordersApi } from '../services/api'

export default {
  name: 'Dashboard',
  data() {
    return {
      loading: true,
      isOnline: true,
      stats: {
        activeOrders: 0,
        completedToday: 5,
        earningsToday: 450,
        rating: 4.8
      },
      recentOrders: []
    }
  },
  computed: {
    riderName() {
      const rider = JSON.parse(localStorage.getItem('rider') || '{}')
      return rider.name || 'Rider'
    }
  },
  async mounted() {
    await this.fetchOrders()
  },
  methods: {
    async fetchOrders() {
      this.loading = true
      try {
        const response = await ordersApi.getActive()
        this.recentOrders = response.data.orders?.slice(0, 3) || response.data.slice?.(0, 3) || []
        this.stats.activeOrders = this.recentOrders.length
      } catch (error) {
        // Use sample data if API fails
        this.recentOrders = [
          { id: 'ORD-001', customer_name: 'Maria Santos', status: 'pending' },
          { id: 'ORD-002', customer_name: 'Juan Dela Cruz', status: 'accepted' },
          { id: 'ORD-003', customer_name: 'Ana Garcia', status: 'pending' }
        ]
        this.stats.activeOrders = 3
      } finally {
        this.loading = false
      }
    },
    toggleStatus() {
      this.isOnline = !this.isOnline
    }
  }
}
</script>
