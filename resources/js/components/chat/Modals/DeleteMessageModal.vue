<template>
  <div @click="$emit('close')" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div @click.stop class="bg-white rounded-lg shadow-xl max-w-sm w-full">
      <div class="p-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold">Delete Message</h3>
      </div>
      
      <div class="p-4">
        <button
          @click="$emit('delete-for-me')"
          class="w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 rounded mb-2"
        >
          Delete for me
        </button>
        
        <button
          v-if="message?.isMine"
          @click="$emit('delete-for-everyone')"
          class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 rounded"
        >
          Delete for everyone
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps(['message']);
defineEmits(['close', 'delete-for-me', 'delete-for-everyone']);
</script>
<style scoped>
button{
  cursor: pointer;
}
</style>