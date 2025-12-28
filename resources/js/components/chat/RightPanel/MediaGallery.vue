<template>
  <div class="grid grid-cols-3 gap-2 mb-6">
    <div v-for="i in 9" :key="i" class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-80">
      <img :src="`https://picsum.photos/200/200?random=${i}`" class="w-full h-full object-cover" />
    </div>
  </div>
</template>