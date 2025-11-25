<script setup>
import Sidebar from './components/Sidebar.vue'
import Dashboard from './components/Dashboard.vue'
import ActiveDeliveries from './components/ActiveDeliveries.vue'
import DeliveryHistory from './components/DeliveryHistory.vue'
import Earnings from './components/Earnings.vue'
import Profile from './components/Profile.vue'
import { ref } from 'vue'

const currentView = ref('dashboard')
const sidebarOpen = ref(false)

const changeView = (view) => {
  currentView.value = view
  sidebarOpen.value = false
}

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Mobile menu button -->
    <button 
      @click="toggleSidebar"
      class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-md bg-blue-600 text-white shadow-lg"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Sidebar -->
    <Sidebar 
      :currentView="currentView" 
      :isOpen="sidebarOpen"
      @change-view="changeView" 
      @close="sidebarOpen = false"
    />

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-4 lg:p-8">
      <Dashboard v-if="currentView === 'dashboard'" />
      <ActiveDeliveries v-else-if="currentView === 'deliveries'" />
      <DeliveryHistory v-else-if="currentView === 'history'" />
      <Earnings v-else-if="currentView === 'earnings'" />
      <Profile v-else-if="currentView === 'profile'" />
    </main>
  </div>
</template>

<style scoped>
</style>
