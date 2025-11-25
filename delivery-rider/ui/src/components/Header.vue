<template>
  <header class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-50">
    <div class="flex items-center justify-between px-4 py-3 md:ml-64">
      <!-- Logo & Menu Toggle (Mobile) -->
      <div class="flex items-center gap-4">
        <button 
          @click="toggleMobileMenu"
          class="md:hidden p-2 rounded-lg hover:bg-gray-100"
        >
          <span class="text-xl">☰</span>
        </button>
        <img 
          :src="logoSrc" 
          alt="ShoeTakels" 
          class="h-8"
          @error="handleLogoError"
        >
        <span v-if="!logoLoaded" class="font-bold text-primary text-lg">ShoeTakels</span>
      </div>

      <!-- Right Side -->
      <div class="flex items-center gap-4">
        <!-- Status Toggle -->
        <button 
          @click="toggleStatus"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition',
            isOnline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'
          ]"
        >
          <span :class="['w-2 h-2 rounded-full', isOnline ? 'bg-green-500' : 'bg-gray-400']"></span>
          {{ isOnline ? 'Online' : 'Offline' }}
        </button>

        <!-- Notifications -->
        <button class="relative p-2 rounded-lg hover:bg-gray-100">
          <span class="text-xl">🔔</span>
          <span 
            v-if="notifications > 0"
            class="absolute -top-1 -right-1 w-5 h-5 bg-error text-white text-xs rounded-full flex items-center justify-center"
          >
            {{ notifications }}
          </span>
        </button>

        <!-- Profile -->
        <div class="relative">
          <button 
            @click="showProfileMenu = !showProfileMenu"
            class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100"
          >
            <div class="w-8 h-8 bg-accent rounded-full flex items-center justify-center text-sm font-bold text-primary">
              {{ initials }}
            </div>
            <span class="hidden md:block text-sm font-medium text-primary">{{ riderName }}</span>
          </button>

          <!-- Dropdown Menu -->
          <div 
            v-if="showProfileMenu"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2"
          >
            <router-link 
              to="/profile"
              class="block px-4 py-2 text-sm text-text-main hover:bg-gray-50"
              @click="showProfileMenu = false"
            >
              👤 My Profile
            </router-link>
            <router-link 
              to="/history"
              class="block px-4 py-2 text-sm text-text-main hover:bg-gray-50"
              @click="showProfileMenu = false"
            >
              📜 History
            </router-link>
            <hr class="my-2 border-gray-200">
            <button 
              @click="logout"
              class="w-full text-left px-4 py-2 text-sm text-error hover:bg-red-50"
            >
              🚪 Sign Out
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div 
      v-if="showMobileMenu"
      class="md:hidden bg-white border-t border-gray-200 px-4 py-4"
    >
      <nav class="space-y-2">
        <router-link 
          to="/dashboard" 
          class="block px-4 py-3 rounded-lg hover:bg-gray-50"
          @click="showMobileMenu = false"
        >
          📊 Dashboard
        </router-link>
        <router-link 
          to="/orders" 
          class="block px-4 py-3 rounded-lg hover:bg-gray-50"
          @click="showMobileMenu = false"
        >
          📦 Active Orders
        </router-link>
        <router-link 
          to="/history" 
          class="block px-4 py-3 rounded-lg hover:bg-gray-50"
          @click="showMobileMenu = false"
        >
          📜 History
        </router-link>
        <router-link 
          to="/profile" 
          class="block px-4 py-3 rounded-lg hover:bg-gray-50"
          @click="showMobileMenu = false"
        >
          👤 Profile
        </router-link>
      </nav>
    </div>
  </header>
</template>

<script>
export default {
  name: 'Header',
  data() {
    return {
      isOnline: true,
      notifications: 2,
      showProfileMenu: false,
      showMobileMenu: false,
      logoSrc: '../../upload/picture/logo.png',
      logoLoaded: true
    }
  },
  computed: {
    riderName() {
      const rider = JSON.parse(localStorage.getItem('rider') || '{}')
      return rider.name || 'Rider'
    },
    initials() {
      return this.riderName
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    }
  },
  mounted() {
    // Close menus when clicking outside
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  },
  methods: {
    handleLogoError() {
      this.logoLoaded = false
    },
    toggleStatus() {
      this.isOnline = !this.isOnline
    },
    toggleMobileMenu() {
      this.showMobileMenu = !this.showMobileMenu
      this.showProfileMenu = false
    },
    handleClickOutside(event) {
      if (!this.$el.contains(event.target)) {
        this.showProfileMenu = false
        this.showMobileMenu = false
      }
    },
    logout() {
      localStorage.removeItem('token')
      localStorage.removeItem('rider')
      this.$router.push('/login')
    }
  }
}
</script>
