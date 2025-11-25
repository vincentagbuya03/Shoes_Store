<script setup>
defineProps({
  currentView: String,
  isOpen: Boolean
})

const emit = defineEmits(['change-view', 'close'])

const menuItems = [
  { id: 'dashboard', label: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { id: 'deliveries', label: 'Active Deliveries', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
  { id: 'history', label: 'Delivery History', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
  { id: 'earnings', label: 'Earnings', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
  { id: 'profile', label: 'Profile', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }
]
</script>

<template>
  <!-- Overlay for mobile -->
  <div 
    v-if="isOpen"
    @click="emit('close')"
    class="lg:hidden fixed inset-0 bg-black/50 z-30"
  ></div>

  <!-- Sidebar -->
  <aside 
    :class="[
      'fixed lg:static inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-blue-800 to-blue-900 text-white transition-transform duration-300 ease-in-out',
      isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <div class="p-6 border-b border-blue-700">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-bold">RiderDash</h1>
          <p class="text-xs text-blue-300">Delivery Partner</p>
        </div>
      </div>
    </div>

    <nav class="p-4">
      <ul class="space-y-2">
        <li v-for="item in menuItems" :key="item.id">
          <button
            @click="emit('change-view', item.id)"
            :class="[
              'w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200',
              currentView === item.id 
                ? 'bg-white/20 text-white shadow-lg' 
                : 'text-blue-200 hover:bg-white/10 hover:text-white'
            ]"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
            </svg>
            {{ item.label }}
          </button>
        </li>
      </ul>
    </nav>

    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-blue-700">
      <div class="flex items-center gap-3 px-4 py-2">
        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-sm font-bold">
          JD
        </div>
        <div class="flex-1">
          <p class="text-sm font-medium">John Doe</p>
          <p class="text-xs text-green-400 flex items-center gap-1">
            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
            Online
          </p>
        </div>
      </div>
    </div>
  </aside>
</template>
