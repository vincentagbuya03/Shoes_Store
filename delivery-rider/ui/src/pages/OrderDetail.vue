<template>
  <div class="pb-20 md:pb-0">
    <!-- Back button -->
    <button 
      @click="router.back()" 
      class="flex items-center gap-2 text-text-light hover:text-accent mb-6 transition-colors"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      Back to Orders
    </button>
    
    <!-- Loading state -->
    <div v-if="loading" class="space-y-4">
      <div class="card animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      </div>
      <div class="card animate-pulse h-64"></div>
    </div>
    
    <template v-else-if="order">
      <!-- Order header -->
      <div class="card mb-4">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 class="text-xl font-bold text-primary">Order #{{ order.id }}</h1>
              <span 
                class="px-3 py-1 rounded-full text-sm font-medium"
                :class="statusClass"
              >
                {{ order.status }}
              </span>
            </div>
            <p class="text-text-light text-sm">Placed {{ formatDate(order.created_at) }}</p>
          </div>
        </div>
        
        <!-- Customer info -->
        <div class="border-t border-gray-100 pt-4">
          <h2 class="font-semibold text-text-main mb-3">Customer Information</h2>
          <div class="space-y-2">
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span>{{ order.customer_name }}</span>
            </div>
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
              <span>{{ order.customer_phone || '+63 912 345 6789' }}</span>
            </div>
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-text-light mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span>{{ order.address }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Map placeholder -->
      <div class="mb-4">
        <MapPlaceholder 
          height="250px"
          :pickup-location="{ lat: 14.5995, lng: 120.9842 }"
          :delivery-location="{ lat: 14.6037, lng: 120.9822 }"
        />
      </div>
      
      <!-- Order items -->
      <div class="card mb-4">
        <h2 class="font-semibold text-text-main mb-4">Order Items</h2>
        <div class="space-y-3">
          <div 
            v-for="item in order.items" 
            :key="item.id"
            class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0"
          >
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
              </div>
              <div>
                <p class="font-medium text-text-main">{{ item.name }}</p>
                <p class="text-sm text-text-light">Qty: {{ item.quantity }}</p>
              </div>
            </div>
            <p class="font-semibold text-text-main">₱{{ item.price }}</p>
          </div>
        </div>
        
        <!-- Order totals -->
        <div class="border-t border-gray-200 mt-4 pt-4 space-y-2">
          <div class="flex justify-between text-text-light">
            <span>Subtotal</span>
            <span>₱{{ order.subtotal || calculateSubtotal() }}</span>
          </div>
          <div class="flex justify-between text-text-light">
            <span>Delivery Fee</span>
            <span>₱{{ order.delivery_fee || 50 }}</span>
          </div>
          <div class="flex justify-between font-bold text-primary text-lg">
            <span>Total</span>
            <span>₱{{ order.total || calculateTotal() }}</span>
          </div>
        </div>
      </div>
      
      <!-- Payment info -->
      <div class="card mb-4">
        <h2 class="font-semibold text-text-main mb-3">Payment Method</h2>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
          </div>
          <span class="font-medium">{{ order.payment_method }}</span>
        </div>
      </div>
      
      <!-- Action buttons -->
      <div class="flex gap-3">
        <button 
          v-if="order.status === 'pending'"
          @click="handleAccept"
          class="btn-success flex-1 py-3"
          :disabled="actionLoading"
        >
          Accept Order
        </button>
        <button 
          v-if="order.status === 'pending'"
          @click="handleDecline"
          class="btn-error flex-1 py-3"
          :disabled="actionLoading"
        >
          Decline
        </button>
        <button 
          v-if="order.status === 'accepted' || order.status === 'in_progress'"
          @click="handleComplete"
          class="btn-success flex-1 py-3"
          :disabled="actionLoading"
        >
          Mark as Completed
        </button>
        <a 
          :href="`tel:${order.customer_phone || '+639123456789'}`"
          class="btn-secondary flex items-center justify-center gap-2 flex-1 py-3"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          Call Customer
        </a>
      </div>
    </template>
    
    <!-- Error state -->
    <div v-else class="card text-center py-16">
      <div class="w-20 h-20 bg-error/10 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-text-main mb-2">Order not found</h3>
      <p class="text-text-light mb-4">This order may have been removed or doesn't exist.</p>
      <router-link to="/orders" class="btn-primary">
        Back to Orders
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MapPlaceholder from '../components/MapPlaceholder.vue'
import api from '../services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const actionLoading = ref(false)
const order = ref(null)

const statusClass = computed(() => {
  if (!order.value) return ''
  const statusClasses = {
    pending: 'bg-yellow-100 text-yellow-700',
    accepted: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-purple-100 text-purple-700',
    completed: 'bg-success/10 text-success',
    cancelled: 'bg-error/10 text-error'
  }
  return statusClasses[order.value.status] || 'bg-gray-100 text-gray-700'
})

const formatDate = (dateString) => {
  if (!dateString) return 'Just now'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const calculateSubtotal = () => {
  if (!order.value?.items) return 0
  return order.value.items.reduce((sum, item) => sum + (item.price * item.quantity), 0)
}

const calculateTotal = () => {
  return calculateSubtotal() + (order.value?.delivery_fee || 50)
}

const fetchOrder = async () => {
  loading.value = true
  try {
    const response = await api.get(`/api/orders/${route.params.id}`)
    order.value = response.data.order || response.data
  } catch (err) {
    console.error('Failed to fetch order:', err)
    order.value = null
  } finally {
    loading.value = false
  }
}

const handleAccept = async () => {
  actionLoading.value = true
  try {
    await api.post(`/api/orders/${order.value.id}/accept`)
    order.value.status = 'accepted'
  } catch (err) {
    console.error('Failed to accept order:', err)
  } finally {
    actionLoading.value = false
  }
}

const handleDecline = async () => {
  actionLoading.value = true
  try {
    // In production, would make API call
    router.push('/orders')
  } catch (err) {
    console.error('Failed to decline order:', err)
  } finally {
    actionLoading.value = false
  }
}

const handleComplete = async () => {
  actionLoading.value = true
  try {
    await api.post(`/api/orders/${order.value.id}/complete`)
    order.value.status = 'completed'
    router.push('/orders')
  } catch (err) {
    console.error('Failed to complete order:', err)
  } finally {
    actionLoading.value = false
  }
}

onMounted(() => {
  fetchOrder()
})
</script>
