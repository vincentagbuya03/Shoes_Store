<template>
  <div class="p-4 md:p-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl md:text-3xl font-bold text-primary">Delivery History</h1>
      <p class="text-text-light mt-1">View your completed deliveries</p>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-success">{{ stats.total }}</p>
        <p class="text-sm text-text-light">Total Completed</p>
      </div>
      <div class="bg-white rounded-xl p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-accent">₱{{ stats.earnings }}</p>
        <p class="text-sm text-text-light">Total Earnings</p>
      </div>
      <div class="bg-white rounded-xl p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-primary">{{ stats.avgTime }}</p>
        <p class="text-sm text-text-light">Avg. Delivery Time</p>
      </div>
      <div class="bg-white rounded-xl p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-primary">{{ stats.rating }}⭐</p>
        <p class="text-sm text-text-light">Average Rating</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-4 mb-6">
      <select 
        v-model="dateFilter"
        class="px-4 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-accent"
      >
        <option value="all">All Time</option>
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
      </select>
      <div class="flex-1"></div>
      <div class="text-sm text-text-light">
        Showing {{ filteredHistory.length }} orders
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin w-12 h-12 border-4 border-accent border-t-transparent rounded-full mx-auto"></div>
      <p class="text-text-light mt-4">Loading history...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredHistory.length === 0" class="text-center py-12 bg-white rounded-xl">
      <span class="text-6xl">📜</span>
      <h3 class="text-xl font-semibold text-primary mt-4">No delivery history</h3>
      <p class="text-text-light mt-2">Complete some deliveries to see them here</p>
    </div>

    <!-- History List -->
    <div v-else class="space-y-4">
      <div 
        v-for="order in filteredHistory" 
        :key="order.id"
        class="bg-white rounded-xl p-6 shadow-sm"
      >
        <div class="flex flex-col md:flex-row md:items-center gap-4">
          <!-- Order Info -->
          <div class="flex-1">
            <div class="flex items-center gap-3">
              <span class="text-2xl">📦</span>
              <div>
                <p class="font-semibold text-primary">#{{ order.id }}</p>
                <p class="text-sm text-text-light">{{ order.customer_name }}</p>
              </div>
            </div>
            <p class="text-sm text-text-light mt-2 ml-10">{{ order.address }}</p>
          </div>

          <!-- Delivery Info -->
          <div class="flex flex-wrap items-center gap-4 md:gap-6">
            <div class="text-center">
              <p class="font-semibold text-primary">₱{{ order.total }}</p>
              <p class="text-xs text-text-light">Amount</p>
            </div>
            <div class="text-center">
              <p class="font-semibold text-primary">{{ order.delivery_time || '25 min' }}</p>
              <p class="text-xs text-text-light">Delivery Time</p>
            </div>
            <div class="text-center">
              <p class="font-semibold text-accent">{{ order.rating || 5 }}⭐</p>
              <p class="text-xs text-text-light">Rating</p>
            </div>
            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
              Completed
            </span>
          </div>
        </div>

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
          <p class="text-sm text-text-light">
            {{ formatDate(order.completed_at || order.created_at) }}
          </p>
          <router-link 
            :to="`/orders/${order.id}`"
            class="text-accent hover:underline text-sm font-medium"
          >
            View Details →
          </router-link>
        </div>
      </div>
    </div>

    <!-- Load More -->
    <div v-if="hasMore" class="text-center mt-6">
      <button 
        @click="loadMore"
        class="px-6 py-3 bg-white text-primary font-medium rounded-lg hover:bg-gray-50 transition border border-gray-200"
      >
        Load More
      </button>
    </div>
  </div>
</template>

<script>
import { ordersApi } from '../services/api'

export default {
  name: 'History',
  data() {
    return {
      loading: true,
      dateFilter: 'all',
      history: [],
      hasMore: true,
      page: 1,
      stats: {
        total: 156,
        earnings: '12,450',
        avgTime: '28 min',
        rating: 4.8
      }
    }
  },
  computed: {
    filteredHistory() {
      if (this.dateFilter === 'all') {
        return this.history
      }

      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

      return this.history.filter(order => {
        const orderDate = new Date(order.completed_at || order.created_at)
        
        switch (this.dateFilter) {
          case 'today':
            return orderDate >= today
          case 'week':
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
            return orderDate >= weekAgo
          case 'month':
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
            return orderDate >= monthAgo
          default:
            return true
        }
      })
    }
  },
  async mounted() {
    await this.fetchHistory()
  },
  methods: {
    async fetchHistory() {
      this.loading = true
      try {
        const response = await ordersApi.getHistory()
        this.history = response.data.orders || response.data || []
      } catch (error) {
        // Use sample data if API fails
        this.history = [
          {
            id: 'ORD-150',
            customer_name: 'Carlos Mendoza',
            address: '45 Aurora Blvd, Quezon City',
            total: 3200,
            delivery_time: '22 min',
            rating: 5,
            completed_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
          },
          {
            id: 'ORD-149',
            customer_name: 'Isabella Cruz',
            address: '123 Makati Ave, Makati City',
            total: 1850,
            delivery_time: '35 min',
            rating: 4,
            completed_at: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString()
          },
          {
            id: 'ORD-148',
            customer_name: 'Miguel Santos',
            address: '78 Ortigas Center, Pasig',
            total: 4500,
            delivery_time: '28 min',
            rating: 5,
            completed_at: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()
          },
          {
            id: 'ORD-147',
            customer_name: 'Sofia Garcia',
            address: '256 BGC, Taguig City',
            total: 2100,
            delivery_time: '18 min',
            rating: 5,
            completed_at: new Date(Date.now() - 48 * 60 * 60 * 1000).toISOString()
          },
          {
            id: 'ORD-146',
            customer_name: 'Antonio Reyes',
            address: '89 Alabang, Muntinlupa',
            total: 5600,
            delivery_time: '42 min',
            rating: 4,
            completed_at: new Date(Date.now() - 72 * 60 * 60 * 1000).toISOString()
          }
        ]
      } finally {
        this.loading = false
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'Unknown'
      const date = new Date(dateString)
      return date.toLocaleString('en-PH', { 
        dateStyle: 'medium', 
        timeStyle: 'short' 
      })
    },
    loadMore() {
      this.page++
      // In real app, fetch more data
      this.hasMore = false
    }
  }
}
</script>
