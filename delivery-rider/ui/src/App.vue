<template>
  <div class="min-h-screen bg-light">
    <Header v-if="showHeader" />
    <div class="flex">
      <!-- Sidebar Navigation (desktop) -->
      <aside 
        v-if="showSidebar" 
        class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 md:pt-16 bg-primary"
      >
        <nav class="flex-1 px-4 py-6 space-y-2">
          <router-link 
            to="/dashboard" 
            class="nav-link"
            :class="{ 'active': $route.path === '/dashboard' }"
          >
            <span class="mr-3">📊</span>
            Dashboard
          </router-link>
          <router-link 
            to="/orders" 
            class="nav-link"
            :class="{ 'active': $route.path === '/orders' || $route.path.startsWith('/orders/') }"
          >
            <span class="mr-3">📦</span>
            Active Orders
          </router-link>
          <router-link 
            to="/history" 
            class="nav-link"
            :class="{ 'active': $route.path === '/history' }"
          >
            <span class="mr-3">📜</span>
            History
          </router-link>
          <router-link 
            to="/profile" 
            class="nav-link"
            :class="{ 'active': $route.path === '/profile' }"
          >
            <span class="mr-3">👤</span>
            Profile
          </router-link>
        </nav>
        <div class="px-4 py-4 border-t border-gray-700">
          <button 
            @click="logout"
            class="w-full px-4 py-2 text-sm text-white bg-error rounded-lg hover:bg-red-600 transition"
          >
            Sign Out
          </button>
        </div>
      </aside>

      <!-- Mobile bottom navigation -->
      <nav 
        v-if="showSidebar"
        class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 z-50"
      >
        <div class="flex justify-around">
          <router-link to="/dashboard" class="mobile-nav-link">
            <span class="text-xl">📊</span>
            <span class="text-xs">Dashboard</span>
          </router-link>
          <router-link to="/orders" class="mobile-nav-link">
            <span class="text-xl">📦</span>
            <span class="text-xs">Orders</span>
          </router-link>
          <router-link to="/history" class="mobile-nav-link">
            <span class="text-xl">📜</span>
            <span class="text-xs">History</span>
          </router-link>
          <router-link to="/profile" class="mobile-nav-link">
            <span class="text-xl">👤</span>
            <span class="text-xs">Profile</span>
          </router-link>
        </div>
      </nav>

      <!-- Main content -->
      <main 
        :class="[
          'flex-1 min-h-screen',
          showSidebar ? 'md:ml-64 pt-16 pb-20 md:pb-0' : ''
        ]"
      >
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import Header from './components/Header.vue'

export default {
  name: 'App',
  components: {
    Header
  },
  computed: {
    showHeader() {
      return this.$route.path !== '/login'
    },
    showSidebar() {
      return this.$route.path !== '/login'
    }
  },
  methods: {
    logout() {
      localStorage.removeItem('token')
      localStorage.removeItem('rider')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.nav-link {
  @apply flex items-center px-4 py-3 text-gray-300 rounded-lg transition-colors;
}

.nav-link:hover {
  @apply bg-gray-800 text-white;
}

.nav-link.active {
  @apply bg-accent text-primary font-semibold;
}

.mobile-nav-link {
  @apply flex flex-col items-center py-1 text-gray-600;
}

.mobile-nav-link.router-link-active {
  @apply text-accent;
}
</style>
