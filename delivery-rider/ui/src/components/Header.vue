<template>
  <header class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-40">
    <div class="flex items-center justify-between">
      <!-- Logo and brand -->
      <div class="flex items-center gap-3">
        <!-- 
          Logo: References the main repository logo.
          If running standalone, copy logo to public/logo.png
        -->
        <img 
          :src="logoSrc" 
          alt="ShoeTakels Logo" 
          class="h-10 w-10 object-contain"
          @error="handleLogoError"
        >
        <div>
          <h1 class="text-lg font-bold text-primary">Delivery Rider</h1>
          <p class="text-xs text-text-light">ShoeTakels</p>
        </div>
      </div>
      
      <!-- Rider status and info -->
      <div class="flex items-center gap-4">
        <!-- Status toggle -->
        <div class="hidden sm:flex items-center gap-2">
          <span class="text-sm text-text-light">Status:</span>
          <button 
            @click="toggleStatus" 
            class="flex items-center gap-2 px-3 py-1.5 rounded-full transition-all duration-300"
            :class="isOnline ? 'bg-success/10 text-success' : 'bg-gray-200 text-text-light'"
          >
            <span 
              class="w-2 h-2 rounded-full"
              :class="isOnline ? 'bg-success' : 'bg-gray-400'"
            ></span>
            <span class="text-sm font-medium">{{ isOnline ? 'Online' : 'Offline' }}</span>
          </button>
        </div>
        
        <!-- Rider name -->
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-accent/20 rounded-full flex items-center justify-center">
            <span class="text-accent font-semibold text-sm">{{ riderInitials }}</span>
          </div>
          <span class="hidden sm:block text-sm font-medium text-text-main">{{ riderName }}</span>
        </div>
        
        <!-- Mobile menu button -->
        <button 
          @click="$emit('toggleMenu')" 
          class="md:hidden p-2 rounded-lg hover:bg-light transition-colors"
        >
          <svg class="w-6 h-6 text-text-main" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Mobile status toggle -->
    <div class="sm:hidden mt-3 flex items-center justify-center gap-2">
      <span class="text-sm text-text-light">Status:</span>
      <button 
        @click="toggleStatus" 
        class="flex items-center gap-2 px-3 py-1.5 rounded-full transition-all duration-300"
        :class="isOnline ? 'bg-success/10 text-success' : 'bg-gray-200 text-text-light'"
      >
        <span 
          class="w-2 h-2 rounded-full"
          :class="isOnline ? 'bg-success' : 'bg-gray-400'"
        ></span>
        <span class="text-sm font-medium">{{ isOnline ? 'Online' : 'Offline' }}</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const isOnline = ref(true)
const riderData = ref(null)

// Logo source - try repo logo first, fallback to local
const logoSrc = ref('/logo.png')

const handleLogoError = () => {
  // If local logo fails, try the relative path to repo logo
  logoSrc.value = '../../upload/picture/logo.png'
}

const riderName = computed(() => {
  return riderData.value?.name || 'Rider'
})

const riderInitials = computed(() => {
  const name = riderName.value
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})

const toggleStatus = () => {
  isOnline.value = !isOnline.value
  // In production, this would make an API call to update status
}

onMounted(() => {
  // Load rider data from localStorage
  const stored = localStorage.getItem('rider_data')
  if (stored) {
    try {
      riderData.value = JSON.parse(stored)
    } catch {
      riderData.value = null
    }
  }
})

defineEmits(['toggleMenu'])
</script>
