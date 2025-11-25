<template>
  <div class="relative">
    <!-- Map Placeholder -->
    <div class="bg-gray-200 rounded-lg h-64 flex items-center justify-center relative overflow-hidden">
      <!-- Decorative Grid -->
      <div class="absolute inset-0 opacity-10">
        <div 
          v-for="i in 12" 
          :key="i" 
          class="absolute bg-gray-400"
          :style="{ 
            top: `${(i * 20)}px`, 
            left: 0, 
            right: 0, 
            height: '1px' 
          }"
        ></div>
        <div 
          v-for="i in 16" 
          :key="'v-' + i" 
          class="absolute bg-gray-400"
          :style="{ 
            left: `${(i * 20)}px`, 
            top: 0, 
            bottom: 0, 
            width: '1px' 
          }"
        ></div>
      </div>

      <!-- Map Pin Icon -->
      <div class="text-center z-10">
        <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto shadow-lg">
          <span class="text-3xl">📍</span>
        </div>
        <p class="mt-4 font-medium text-gray-600 text-sm max-w-xs px-4">
          {{ address }}
        </p>
      </div>
    </div>

    <!-- Integration Instructions -->
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
      <h4 class="font-semibold text-blue-800 text-sm mb-2">🗺️ Map Integration</h4>
      <p class="text-xs text-blue-700 leading-relaxed">
        This is a placeholder for the delivery location map. To enable real maps:
      </p>
      <ul class="text-xs text-blue-700 mt-2 space-y-1 list-disc list-inside">
        <li>Install <code class="bg-blue-100 px-1 rounded">leaflet</code> and <code class="bg-blue-100 px-1 rounded">vue-leaflet</code> for free OpenStreetMap</li>
        <li>Or use <code class="bg-blue-100 px-1 rounded">@googlemaps/js-api-loader</code> for Google Maps</li>
      </ul>
      <a 
        :href="mapsUrl" 
        target="_blank"
        class="inline-block mt-3 text-xs text-blue-800 font-medium hover:underline"
      >
        🔗 Open in Google Maps →
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MapPlaceholder',
  props: {
    address: {
      type: String,
      default: 'Delivery Location'
    },
    lat: {
      type: Number,
      default: null
    },
    lng: {
      type: Number,
      default: null
    }
  },
  computed: {
    mapsUrl() {
      if (this.lat && this.lng) {
        return `https://www.google.com/maps?q=${this.lat},${this.lng}`
      }
      return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.address)}`
    }
  }
}
</script>

<style scoped>
code {
  font-family: 'Monaco', 'Consolas', monospace;
}
</style>
