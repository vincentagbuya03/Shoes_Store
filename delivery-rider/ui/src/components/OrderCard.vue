<template>
  <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div>
        <router-link :to="`/orders/${order.id}`" class="text-lg font-bold text-primary hover:text-accent">
          #{{ order.id }}
        </router-link>
        <p class="text-sm text-text-light">{{ formatTime(order.created_at) }}</p>
      </div>
      <span 
        :class="['px-3 py-1 rounded-full text-xs font-medium', statusClass]"
      >
        {{ statusText }}
      </span>
    </div>

    <!-- Customer Info -->
    <div class="flex items-center gap-3 mb-4">
      <div class="w-10 h-10 bg-light rounded-full flex items-center justify-center">
        <span class="text-xl">👤</span>
      </div>
      <div>
        <p class="font-semibold text-primary">{{ order.customer_name }}</p>
        <p class="text-sm text-text-light truncate max-w-xs">{{ order.address }}</p>
      </div>
    </div>

    <!-- Order Details -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-4 border-t border-b border-gray-100">
      <div>
        <p class="text-xs text-text-light">Distance</p>
        <p class="font-semibold text-primary">{{ order.distance || 'N/A' }}</p>
      </div>
      <div>
        <p class="text-xs text-text-light">Items</p>
        <p class="font-semibold text-primary">{{ order.items_count || 1 }} item(s)</p>
      </div>
      <div>
        <p class="text-xs text-text-light">Payment</p>
        <p class="font-semibold text-primary">{{ order.payment_method }}</p>
      </div>
      <div>
        <p class="text-xs text-text-light">Total</p>
        <p class="font-semibold text-accent">₱{{ order.total }}</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3 mt-4">
      <router-link 
        :to="`/orders/${order.id}`"
        class="flex-1 py-2 px-4 bg-light text-primary font-medium rounded-lg hover:bg-gray-200 transition text-center"
      >
        View Details
      </router-link>
      
      <template v-if="order.status === 'pending'">
        <button 
          @click="$emit('accept', order.id)"
          class="flex-1 py-2 px-4 bg-success text-white font-medium rounded-lg hover:bg-green-600 transition"
        >
          Accept
        </button>
        <button 
          @click="$emit('decline', order.id)"
          class="py-2 px-4 border-2 border-error text-error font-medium rounded-lg hover:bg-red-50 transition"
        >
          Decline
        </button>
      </template>

      <template v-else-if="order.status === 'accepted' || order.status === 'in_transit'">
        <button 
          @click="$emit('complete', order.id)"
          class="flex-1 py-2 px-4 bg-accent text-primary font-medium rounded-lg hover:bg-yellow-500 transition"
        >
          Complete Delivery
        </button>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OrderCard',
  props: {
    order: {
      type: Object,
      required: true
    }
  },
  emits: ['accept', 'decline', 'complete'],
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
    },
    statusText() {
      const labels = {
        pending: 'Pending',
        accepted: 'Accepted',
        in_transit: 'In Transit',
        delivered: 'Delivered',
        completed: 'Completed'
      }
      return labels[this.order.status] || this.order.status
    }
  },
  methods: {
    formatTime(dateString) {
      if (!dateString) return 'Just now'
      const date = new Date(dateString)
      const now = new Date()
      const diff = now - date
      
      if (diff < 60000) return 'Just now'
      if (diff < 3600000) return `${Math.floor(diff / 60000)} min ago`
      if (diff < 86400000) return `${Math.floor(diff / 3600000)} hours ago`
      
      return date.toLocaleDateString('en-PH', { dateStyle: 'short' })
    }
  }
}
</script>
