<script setup>
import { ref } from 'vue'

const deliveries = ref([
  {
    id: 'ORD-001',
    customer: 'Maria Santos',
    phone: '+63 917 123 4567',
    address: '123 Rizal St, Makati City',
    items: [
      { name: 'Nike Air Max 270', size: '42', qty: 1, price: '₱5,500' }
    ],
    status: 'picked_up',
    pickupTime: '10:30 AM',
    eta: '15 mins',
    distance: '3.2 km',
    payment: 'COD',
    amount: '₱5,500',
    notes: 'Please call upon arrival'
  },
  {
    id: 'ORD-002',
    customer: 'Juan Cruz',
    phone: '+63 918 234 5678',
    address: '456 Bonifacio Ave, Taguig',
    items: [
      { name: 'Adidas Ultraboost 22', size: '40', qty: 1, price: '₱8,200' }
    ],
    status: 'in_transit',
    pickupTime: '11:00 AM',
    eta: '25 mins',
    distance: '5.8 km',
    payment: 'Paid Online',
    amount: '₱8,200',
    notes: ''
  },
  {
    id: 'ORD-003',
    customer: 'Ana Garcia',
    phone: '+63 919 345 6789',
    address: '789 EDSA, Quezon City',
    items: [
      { name: 'Converse Chuck Taylor', size: '38', qty: 2, price: '₱3,800' }
    ],
    status: 'pending',
    pickupTime: '11:30 AM',
    eta: '45 mins',
    distance: '8.5 km',
    payment: 'COD',
    amount: '₱3,800',
    notes: 'Gate code: 1234'
  }
])

const selectedDelivery = ref(null)

const getStatusClass = (status) => {
  switch(status) {
    case 'picked_up': return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'in_transit': return 'bg-orange-100 text-orange-800 border-orange-200'
    case 'pending': return 'bg-gray-100 text-gray-800 border-gray-200'
    default: return 'bg-gray-100 text-gray-800 border-gray-200'
  }
}

const getStatusLabel = (status) => {
  switch(status) {
    case 'picked_up': return 'Picked Up'
    case 'in_transit': return 'In Transit'
    case 'pending': return 'Pending Pickup'
    default: return status
  }
}

const updateStatus = (delivery, newStatus) => {
  const index = deliveries.value.findIndex(d => d.id === delivery.id)
  if (index !== -1) {
    deliveries.value[index] = { ...deliveries.value[index], status: newStatus }
  }
}

const selectDelivery = (delivery) => {
  selectedDelivery.value = delivery
}

const closeDetails = () => {
  selectedDelivery.value = null
}
</script>

<template>
  <div class="pt-12 lg:pt-0">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Active Deliveries</h1>
      <p class="text-gray-600 mt-1">Manage your current delivery orders</p>
    </div>

    <!-- Delivery List -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <div 
        v-for="delivery in deliveries" 
        :key="delivery.id"
        :class="[
          'bg-white rounded-xl shadow-sm overflow-hidden border-2 transition-all cursor-pointer',
          selectedDelivery?.id === delivery.id ? 'border-blue-500 ring-2 ring-blue-200' : 'border-transparent hover:border-blue-200'
        ]"
        @click="selectDelivery(delivery)"
      >
        <!-- Header -->
        <div class="p-4 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-800">{{ delivery.customer }}</p>
                <p class="text-xs text-gray-500">{{ delivery.phone }}</p>
              </div>
            </div>
            <span :class="[getStatusClass(delivery.status), 'text-xs font-medium px-3 py-1 rounded-full border']">
              {{ getStatusLabel(delivery.status) }}
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-4">
          <div class="flex items-start gap-2 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-sm text-gray-600">{{ delivery.address }}</p>
          </div>

          <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
            <span class="flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ delivery.eta }}
            </span>
            <span class="flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              </svg>
              {{ delivery.distance }}
            </span>
          </div>

          <!-- Items -->
          <div class="bg-gray-50 rounded-lg p-3 mb-4">
            <p class="text-xs text-gray-500 mb-2 font-medium">ORDER ITEMS</p>
            <div v-for="item in delivery.items" :key="item.name" class="flex justify-between text-sm">
              <span class="text-gray-700">{{ item.name }} (Size {{ item.size }}) x{{ item.qty }}</span>
              <span class="font-medium text-gray-800">{{ item.price }}</span>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500">{{ delivery.payment }}</p>
              <p class="text-lg font-bold text-green-600">{{ delivery.amount }}</p>
            </div>
            <div class="flex gap-2">
              <button 
                v-if="delivery.status === 'pending'"
                @click.stop="updateStatus(delivery, 'picked_up')"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition-colors"
              >
                Pick Up
              </button>
              <button 
                v-if="delivery.status === 'picked_up'"
                @click.stop="updateStatus(delivery, 'in_transit')"
                class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg transition-colors"
              >
                Start Delivery
              </button>
              <button 
                v-if="delivery.status === 'in_transit'"
                @click.stop="updateStatus(delivery, 'delivered')"
                class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg transition-colors"
              >
                Complete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delivery Details Modal -->
    <div 
      v-if="selectedDelivery"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeDetails"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-800">Order {{ selectedDelivery.id }}</h3>
          <button @click="closeDetails" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <p class="text-sm text-gray-500 mb-1">Customer</p>
            <p class="font-medium text-gray-800">{{ selectedDelivery.customer }}</p>
            <p class="text-sm text-gray-600">{{ selectedDelivery.phone }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 mb-1">Delivery Address</p>
            <p class="text-gray-800">{{ selectedDelivery.address }}</p>
          </div>
          <div v-if="selectedDelivery.notes">
            <p class="text-sm text-gray-500 mb-1">Notes</p>
            <p class="text-gray-800 bg-yellow-50 p-2 rounded border border-yellow-200">{{ selectedDelivery.notes }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500 mb-1">Payment Method</p>
              <p class="font-medium text-gray-800">{{ selectedDelivery.payment }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 mb-1">Total Amount</p>
              <p class="font-bold text-green-600 text-lg">{{ selectedDelivery.amount }}</p>
            </div>
          </div>
          <div class="flex gap-2 pt-4">
            <a 
              :href="'tel:' + selectedDelivery.phone"
              class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              Call
            </a>
            <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
              </svg>
              Navigate
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
