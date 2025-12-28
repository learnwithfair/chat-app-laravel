<template>
  <div class="flex items-center justify-between">
    <div class="flex-1">
      <p class="text-sm font-medium text-gray-900">{{ label }}</p>
      <p class="text-xs text-gray-500">{{ description }}</p>
    </div>
    
    <button
      @click="toggle"
      :class="[
        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
        modelValue ? 'bg-blue-600' : 'bg-gray-200'
      ]"
    >
      <span
        :class="[
          'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
          modelValue ? 'translate-x-5' : 'translate-x-0'
        ]"
      />
    </button>
  </div>
</template>

<script setup>
const props = defineProps(['modelValue', 'label', 'description']);
const emit = defineEmits(['update:modelValue', 'change']);

const toggle = () => {
  emit('update:modelValue', !props.modelValue);
  emit('change', !props.modelValue);
};
</script>