<template>
  <div class="bg-gray-200 rounded-xl overflow-hidden" :style="{ height: height }">
    <!-- Map placeholder content -->
    <div class="h-full flex flex-col items-center justify-center p-6 text-center">
      <div class="w-16 h-16 bg-accent/20 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
      </div>
      
      <h3 class="text-lg font-semibold text-text-main mb-2">Map View</h3>
      <p class="text-sm text-text-light mb-4 max-w-xs">
        Interactive map showing pickup and delivery locations
      </p>
      
      <!-- Location markers -->
      <div class="flex items-center gap-4 mb-4">
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 bg-success rounded-full"></div>
          <span class="text-xs text-text-light">Pickup</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 bg-error rounded-full"></div>
          <span class="text-xs text-text-light">Delivery</span>
        </div>
      </div>
      
      <!-- Integration note -->
      <div class="bg-white/50 rounded-lg p-3 text-xs text-text-light">
        <p class="font-medium mb-1">To enable real maps:</p>
        <p>Install <code class="bg-gray-300 px-1 rounded">leaflet</code> or Google Maps API</p>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * MapPlaceholder Component
 * 
 * This is a placeholder component for displaying maps.
 * To integrate a real map, follow these instructions:
 * 
 * OPTION 1: Leaflet (Free, Open Source)
 * =====================================
 * 1. Install dependencies:
 *    npm install leaflet @vue-leaflet/vue-leaflet
 * 
 * 2. Import and use:
 *    import { LMap, LTileLayer, LMarker } from '@vue-leaflet/vue-leaflet'
 *    import 'leaflet/dist/leaflet.css'
 * 
 * 3. Replace this component's template with:
 *    <LMap :zoom="13" :center="[lat, lng]">
 *      <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
 *      <LMarker :lat-lng="[pickupLat, pickupLng]" />
 *      <LMarker :lat-lng="[deliveryLat, deliveryLng]" />
 *    </LMap>
 * 
 * OPTION 2: Google Maps
 * =====================
 * 1. Install dependencies:
 *    npm install @googlemaps/js-api-loader
 * 
 * 2. Get an API key from Google Cloud Console
 * 
 * 3. Initialize the loader:
 *    import { Loader } from '@googlemaps/js-api-loader'
 *    const loader = new Loader({ apiKey: 'YOUR_API_KEY' })
 * 
 * 4. Create the map in onMounted:
 *    const google = await loader.load()
 *    new google.maps.Map(mapElement, { center: { lat, lng }, zoom: 13 })
 * 
 * Props for real implementation:
 * - pickupLocation: { lat: number, lng: number }
 * - deliveryLocation: { lat: number, lng: number }
 * - showRoute: boolean
 */

defineProps({
  height: {
    type: String,
    default: '300px'
  },
  pickupLocation: {
    type: Object,
    default: () => ({ lat: 0, lng: 0 })
  },
  deliveryLocation: {
    type: Object,
    default: () => ({ lat: 0, lng: 0 })
  }
})
</script>
