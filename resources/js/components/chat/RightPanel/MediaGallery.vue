<template>
  <div>
    <div v-if="media && media.length > 0" class="grid grid-cols-3 gap-2 mb-6">
      <div
        v-for="item in media"
        :key="item.id"
        class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
        @click="openMedia(item)"
      >
        <img
          v-if="item.type === 'image'"
          :src="item.url"
          :alt="item.name"
          class="w-full h-full object-cover"
        />
        <div v-else-if="item.type === 'video'" class="relative w-full h-full">
          <video :src="item.url" class="w-full h-full object-cover" />
          <div
            class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30"
          >
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <svg
        class="w-16 h-16 mx-auto text-gray-300 mb-2"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
        />
      </svg>
      <p class="text-sm text-gray-500">No media yet</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  media: { type: Array, default: () => [] },
  conversationId: { type: [Number, String], required: true },
});

const openMedia = (item) => {
  // Open media in modal or new tab
  window.open(item.url, "_blank");
};
</script>
