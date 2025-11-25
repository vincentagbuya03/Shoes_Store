<template>
  <div class="card cursor-pointer hover:border-accent border-2 border-transparent" @click="handleClick">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
      <!-- Order info -->
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-sm font-bold text-primary">Order #{{ order.id }}</span>
          <span 
            class="px-2 py-0.5 rounded-full text-xs font-medium"
            :class="statusClass"
          >
            {{ order.status }}
          </span>
        </div>
        
        <h3 class="font-semibold text-text-main mb-1">{{ order.customer_name }}</h3>
        
        <div class="space-y-1 text-sm text-text-light">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="line-clamp-1">{{ order.address }}</span>
          </div>
          
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
              </svg>
              <span>{{ order.distance || '~2.5 km' }}</span>
            </div>
            
            <div class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
              <span>{{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}</span>
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span>{{ order.payment_method }}</span>
          </div>
        </div>
      </div>
      
      <!-- Action buttons -->
      <div v-if="showActions" class="flex sm:flex-col gap-2">
        <button 
          v-if="order.status === 'pending'"
          @click.stop="$emit('accept', order.id)"
          class="btn-success flex-1 sm:flex-none text-sm"
        >
          Accept
        </button>
        <button 
          v-if="order.status === 'pending'"
          @click.stop="$emit('decline', order.id)"
          class="btn-error flex-1 sm:flex-none text-sm"
        >
          Decline
        </button>
        <button 
          v-if="order.status === 'accepted' || order.status === 'in_progress'"
          @click.stop="$emit('complete', order.id)"
          class="btn-success flex-1 sm:flex-none text-sm"
        >
          Complete
        </button>
      </div>
      
      <!-- Arrow indicator for clickable card -->
      <div v-if="!showActions" class="hidden sm:block">
        <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

defineEmits(['accept', 'decline', 'complete'])

const router = useRouter()

const statusClass = computed(() => {
  const statusClasses = {
    pending: 'bg-yellow-100 text-yellow-700',
    accepted: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-purple-100 text-purple-700',
    completed: 'bg-success/10 text-success',
    cancelled: 'bg-error/10 text-error'
  }
  return statusClasses[props.order.status] || 'bg-gray-100 text-gray-700'
})

const handleClick = () => {
  router.push(`/orders/${props.order.id}`)
}
</script>
