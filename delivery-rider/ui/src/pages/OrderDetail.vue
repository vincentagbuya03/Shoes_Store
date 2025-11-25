<template>
  <div class="p-4 md:p-8">
    <!-- Back Button -->
    <button 
      @click="$router.back()" 
      class="flex items-center text-text-light hover:text-primary mb-6 transition"
    >
      <span class="mr-2">←</span>
      Back to Orders
    </button>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin w-12 h-12 border-4 border-accent border-t-transparent rounded-full mx-auto"></div>
      <p class="text-text-light mt-4">Loading order details...</p>
    </div>

    <template v-else>
      <!-- Order Header -->
      <div class="bg-white rounded-xl p-6 shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-primary">Order #{{ order.id }}</h1>
            <p class="text-text-light mt-1">
              Placed {{ formatDate(order.created_at) }}
            </p>
          </div>
          <span 
            :class="[
              'mt-4 md:mt-0 px-4 py-2 rounded-full text-sm font-semibold inline-block',
              statusClass
            ]"
          >
            {{ order.status?.toUpperCase() }}
          </span>
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Customer & Delivery Info -->
        <div class="space-y-6">
          <!-- Customer Info -->
          <div class="bg-white rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-primary mb-4">Customer Information</h2>
            <div class="space-y-3">
              <div class="flex items-center">
                <span class="text-2xl mr-3">👤</span>
                <div>
                  <p class="font-medium text-primary">{{ order.customer_name }}</p>
                  <p class="text-sm text-text-light">Customer</p>
                </div>
              </div>
              <div class="flex items-center">
                <span class="text-2xl mr-3">📞</span>
                <div>
                  <p class="font-medium text-primary">{{ order.customer_phone || '+63 912 345 6789' }}</p>
                  <p class="text-sm text-text-light">Phone</p>
                </div>
              </div>
              <div class="flex items-start">
                <span class="text-2xl mr-3">📍</span>
                <div>
                  <p class="font-medium text-primary">{{ order.address }}</p>
                  <p class="text-sm text-text-light">Delivery Address</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Map Placeholder -->
          <div class="bg-white rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-primary mb-4">Delivery Location</h2>
            <MapPlaceholder :address="order.address" />
          </div>
        </div>

        <!-- Order Details & Actions -->
        <div class="space-y-6">
          <!-- Order Items -->
          <div class="bg-white rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-primary mb-4">Order Items</h2>
            <div class="space-y-4">
              <div 
                v-for="item in order.items" 
                :key="item.id"
                class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0"
              >
                <div class="flex items-center">
                  <div class="w-12 h-12 bg-light rounded-lg flex items-center justify-center mr-4">
                    <span class="text-xl">👟</span>
                  </div>
                  <div>
                    <p class="font-medium text-primary">{{ item.name }}</p>
                    <p class="text-sm text-text-light">Size: {{ item.size }} | Qty: {{ item.quantity }}</p>
                  </div>
                </div>
                <p class="font-semibold text-primary">₱{{ item.price }}</p>
              </div>
            </div>

            <div class="border-t border-gray-200 mt-4 pt-4">
              <div class="flex justify-between text-text-light">
                <span>Subtotal</span>
                <span>₱{{ order.subtotal || order.total }}</span>
              </div>
              <div class="flex justify-between text-text-light mt-2">
                <span>Delivery Fee</span>
                <span>₱{{ order.delivery_fee || 50 }}</span>
              </div>
              <div class="flex justify-between font-bold text-primary text-lg mt-4">
                <span>Total</span>
                <span>₱{{ order.total }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="bg-white rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-primary mb-4">Payment</h2>
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <span class="text-2xl mr-3">
                  {{ order.payment_method === 'COD' ? '💵' : '💳' }}
                </span>
                <div>
                  <p class="font-medium text-primary">{{ order.payment_method }}</p>
                  <p class="text-sm text-text-light">
                    {{ order.payment_method === 'COD' ? 'Collect on delivery' : 'Already paid' }}
                  </p>
                </div>
              </div>
              <span 
                :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  order.payment_method === 'COD' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
                ]"
              >
                {{ order.payment_method === 'COD' ? 'Pending' : 'Paid' }}
              </span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="bg-white rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-primary mb-4">Actions</h2>
            <div class="space-y-3">
              <button 
                v-if="order.status === 'pending'"
                @click="acceptOrder"
                class="w-full py-3 bg-success text-white font-semibold rounded-lg hover:bg-green-600 transition"
              >
                Accept Order
              </button>
              <button 
                v-if="order.status === 'pending'"
                @click="declineOrder"
                class="w-full py-3 bg-white border-2 border-error text-error font-semibold rounded-lg hover:bg-red-50 transition"
              >
                Decline Order
              </button>
              <button 
                v-if="order.status === 'accepted' || order.status === 'in_transit'"
                @click="completeOrder"
                class="w-full py-3 bg-accent text-primary font-semibold rounded-lg hover:bg-yellow-500 transition"
              >
                Mark as Delivered
              </button>
              <a 
                :href="`tel:${order.customer_phone || '+639123456789'}`"
                class="block w-full py-3 bg-primary text-white font-semibold rounded-lg hover:bg-gray-800 transition text-center"
              >
                📞 Call Customer
              </a>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import MapPlaceholder from '../components/MapPlaceholder.vue'
import { ordersApi } from '../services/api'

export default {
  name: 'OrderDetail',
  components: {
    MapPlaceholder
  },
  data() {
    return {
      loading: true,
      order: {}
    }
  },
  computed: {
    statusClass() {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-blue-100 text-blue-800',
        in_transit: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        completed: 'bg-green-100 text-green-800'
      }
      return classes[this.order.status] || 'bg-gray-100 text-gray-800'
    }
  },
  async mounted() {
    await this.fetchOrder()
  },
  methods: {
    async fetchOrder() {
      this.loading = true
      const orderId = this.$route.params.id
      
      try {
        const response = await ordersApi.getById(orderId)
        this.order = response.data.order || response.data
      } catch (error) {
        // Use sample data if API fails
        this.order = {
          id: orderId,
          customer_name: 'Maria Santos',
          customer_phone: '+63 912 345 6789',
          address: '123 Rizal Street, Makati City, Metro Manila',
          distance: '2.5 km',
          items_count: 3,
          payment_method: 'COD',
          subtotal: 2400,
          delivery_fee: 50,
          total: 2450,
          status: 'pending',
          created_at: new Date().toISOString(),
          items: [
            { id: 1, name: 'Nike Air Max 90', size: '42', quantity: 1, price: 1200 },
            { id: 2, name: 'Adidas Ultraboost', size: '41', quantity: 1, price: 800 },
            { id: 3, name: 'Shoe Care Kit', size: 'N/A', quantity: 1, price: 400 }
          ]
        }
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
    async acceptOrder() {
      try {
        await ordersApi.accept(this.order.id)
        this.order.status = 'accepted'
      } catch (error) {
        this.order.status = 'accepted'
      }
    },
    declineOrder() {
      if (confirm('Are you sure you want to decline this order?')) {
        this.$router.push('/orders')
      }
    },
    async completeOrder() {
      try {
        await ordersApi.complete(this.order.id)
        this.order.status = 'delivered'
        setTimeout(() => this.$router.push('/orders'), 1500)
      } catch (error) {
        this.order.status = 'delivered'
        setTimeout(() => this.$router.push('/orders'), 1500)
      }
    }
  }
}
</script>
