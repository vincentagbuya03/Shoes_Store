<template>
  <div class="p-4 md:p-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl md:text-3xl font-bold text-primary">Active Orders</h1>
      <p class="text-text-light mt-1">Manage your delivery assignments</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button 
        v-for="filter in filters" 
        :key="filter.value"
        @click="activeFilter = filter.value"
        :class="[
          'px-4 py-2 rounded-full text-sm font-medium transition',
          activeFilter === filter.value 
            ? 'bg-accent text-primary' 
            : 'bg-white text-text-light hover:bg-gray-100'
        ]"
      >
        {{ filter.label }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin w-12 h-12 border-4 border-accent border-t-transparent rounded-full mx-auto"></div>
      <p class="text-text-light mt-4">Loading orders...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredOrders.length === 0" class="text-center py-12 bg-white rounded-xl">
      <span class="text-6xl">📭</span>
      <h3 class="text-xl font-semibold text-primary mt-4">No orders found</h3>
      <p class="text-text-light mt-2">Check back later for new delivery assignments</p>
    </div>

    <!-- Orders List -->
    <div v-else class="space-y-4">
      <OrderCard 
        v-for="order in filteredOrders" 
        :key="order.id" 
        :order="order"
        @accept="handleAccept"
        @decline="handleDecline"
        @complete="handleComplete"
      />
    </div>
  </div>
</template>

<script>
import OrderCard from '../components/OrderCard.vue'
import { ordersApi } from '../services/api'

export default {
  name: 'Orders',
  components: {
    OrderCard
  },
  data() {
    return {
      loading: true,
      activeFilter: 'all',
      filters: [
        { label: 'All', value: 'all' },
        { label: 'Pending', value: 'pending' },
        { label: 'Accepted', value: 'accepted' },
        { label: 'In Transit', value: 'in_transit' }
      ],
      orders: []
    }
  },
  computed: {
    filteredOrders() {
      if (this.activeFilter === 'all') {
        return this.orders
      }
      return this.orders.filter(order => order.status === this.activeFilter)
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
        this.orders = response.data.orders || response.data || []
      } catch (error) {
        // Use sample data if API fails
        this.orders = [
          {
            id: 'ORD-001',
            customer_name: 'Maria Santos',
            address: '123 Rizal Street, Makati City',
            distance: '2.5 km',
            items_count: 3,
            payment_method: 'COD',
            total: 2450,
            status: 'pending',
            created_at: new Date().toISOString()
          },
          {
            id: 'ORD-002',
            customer_name: 'Juan Dela Cruz',
            address: '456 EDSA, Quezon City',
            distance: '4.2 km',
            items_count: 1,
            payment_method: 'Paid Online',
            total: 3200,
            status: 'accepted',
            created_at: new Date().toISOString()
          },
          {
            id: 'ORD-003',
            customer_name: 'Ana Garcia',
            address: '789 Ayala Avenue, BGC',
            distance: '1.8 km',
            items_count: 2,
            payment_method: 'COD',
            total: 1850,
            status: 'pending',
            created_at: new Date().toISOString()
          },
          {
            id: 'ORD-004',
            customer_name: 'Pedro Reyes',
            address: '321 Ortigas Center, Pasig',
            distance: '3.5 km',
            items_count: 4,
            payment_method: 'GCash',
            total: 5600,
            status: 'in_transit',
            created_at: new Date().toISOString()
          }
        ]
      } finally {
        this.loading = false
      }
    },
    async handleAccept(orderId) {
      try {
        await ordersApi.accept(orderId)
        const order = this.orders.find(o => o.id === orderId)
        if (order) order.status = 'accepted'
      } catch (error) {
        // Update locally for demo
        const order = this.orders.find(o => o.id === orderId)
        if (order) order.status = 'accepted'
      }
    },
    handleDecline(orderId) {
      this.orders = this.orders.filter(o => o.id !== orderId)
    },
    async handleComplete(orderId) {
      try {
        await ordersApi.complete(orderId)
        this.orders = this.orders.filter(o => o.id !== orderId)
      } catch (error) {
        // Remove locally for demo
        this.orders = this.orders.filter(o => o.id !== orderId)
      }
    }
  }
}
</script>
