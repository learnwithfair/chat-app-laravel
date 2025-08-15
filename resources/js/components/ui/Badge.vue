<script setup lang="ts">
const props = withDefaults(defineProps<{ variant?: "default" | "secondary" }>(), {
  variant: "default",
});
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium border"
    :class="[
      variant === 'default'
        ? 'bg-emerald-600 text-white border-transparent'
        : 'bg-gray-100 text-gray-700 border-gray-200',
    ]"
  >
    <slot />
  </span>
</template>
