<template>
  <div class="min-h-screen bg-light">
    <!-- Header - shown on all pages except login -->
    <Header v-if="showHeader" />
    
    <!-- Main content area -->
    <div class="flex">
      <!-- Sidebar navigation - shown on all pages except login -->
      <aside 
        v-if="showSidebar" 
        class="hidden md:flex flex-col w-64 bg-white border-r border-gray-200 min-h-[calc(100vh-64px)]"
      >
        <nav class="flex-1 p-4 space-y-2">
          <router-link 
            to="/dashboard" 
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-text-main hover:bg-light transition-colors"
            :class="{ 'bg-accent/10 text-accent': $route.path === '/dashboard' }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-medium">Dashboard</span>
          </router-link>
          
          <router-link 
            to="/orders" 
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-text-main hover:bg-light transition-colors"
            :class="{ 'bg-accent/10 text-accent': $route.path === '/orders' || $route.path.startsWith('/orders/') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="font-medium">Active Orders</span>
          </router-link>
          
          <router-link 
            to="/history" 
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-text-main hover:bg-light transition-colors"
            :class="{ 'bg-accent/10 text-accent': $route.path === '/history' }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-medium">Order History</span>
          </router-link>
          
          <router-link 
            to="/profile" 
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-text-main hover:bg-light transition-colors"
            :class="{ 'bg-accent/10 text-accent': $route.path === '/profile' }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="font-medium">Profile</span>
          </router-link>
        </nav>
        
        <!-- Logout button at bottom -->
        <div class="p-4 border-t border-gray-200">
          <button 
            @click="logout" 
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-error hover:bg-error/10 transition-colors w-full"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="font-medium">Logout</span>
          </button>
        </div>
      </aside>
      
      <!-- Main content -->
      <main class="flex-1 p-4 md:p-6">
        <router-view />
      </main>
    </div>
    
    <!-- Mobile bottom navigation -->
    <nav 
      v-if="showSidebar" 
      class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 z-50"
    >
      <div class="flex justify-around items-center">
        <router-link 
          to="/dashboard" 
          class="flex flex-col items-center gap-1 py-2 px-3 rounded-lg"
          :class="{ 'text-accent': $route.path === '/dashboard', 'text-text-light': $route.path !== '/dashboard' }"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          <span class="text-xs font-medium">Home</span>
        </router-link>
        
        <router-link 
          to="/orders" 
          class="flex flex-col items-center gap-1 py-2 px-3 rounded-lg"
          :class="{ 'text-accent': $route.path === '/orders' || $route.path.startsWith('/orders/'), 'text-text-light': !($route.path === '/orders' || $route.path.startsWith('/orders/')) }"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <span class="text-xs font-medium">Orders</span>
        </router-link>
        
        <router-link 
          to="/history" 
          class="flex flex-col items-center gap-1 py-2 px-3 rounded-lg"
          :class="{ 'text-accent': $route.path === '/history', 'text-text-light': $route.path !== '/history' }"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-xs font-medium">History</span>
        </router-link>
        
        <router-link 
          to="/profile" 
          class="flex flex-col items-center gap-1 py-2 px-3 rounded-lg"
          :class="{ 'text-accent': $route.path === '/profile', 'text-text-light': $route.path !== '/profile' }"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <span class="text-xs font-medium">Profile</span>
        </router-link>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Header from './components/Header.vue'

const route = useRoute()
const router = useRouter()

const showHeader = computed(() => route.path !== '/login')
const showSidebar = computed(() => route.path !== '/login')

const logout = () => {
  localStorage.removeItem('rider_token')
  localStorage.removeItem('rider_data')
  router.push('/login')
}
</script>
