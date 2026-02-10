<script setup>
defineProps({
  members: { type: Array, default: () => [] },
});

defineEmits(["approve", "reject"]);

const formatTime = (datetime) => {
  const date = new Date(datetime);
  const now = new Date();
  const diff = now - date;

  const minute = 60 * 1000;
  const hour = 60 * minute;
  const day = 24 * hour;

  if (diff < minute) return "Just now";
  if (diff < hour) return `${Math.floor(diff / minute)}m ago`;
  if (diff < day) return `${Math.floor(diff / hour)}h ago`;

  return date.toLocaleDateString();
};
</script>

<template>
  <div v-if="members.length > 0" class="mb-6 border-t border-gray-200 pt-4">
    <h4 class="text-sm font-semibold text-gray-700 mb-3">
      Pending Approval ({{ members.length }})
    </h4>

    <div class="space-y-2">
      <div
        v-for="member in members"
        :key="member.id"
        class="flex items-center justify-between p-2 bg-yellow-50 rounded-lg"
      >
        <div class="flex items-center flex-1 min-w-0">
          <img
            :src="member.avatar"
            :alt="member.name"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
          />
          <div class="ml-2 min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 truncate">{{ member.name }}</p>
            <p class="text-xs text-gray-500">{{ formatTime(member.requestedAt) }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-1 flex-shrink-0 ml-2">
          <button
            @click="$emit('approve', member.id)"
            class="p-1.5 text-green-600 hover:bg-green-100 rounded transition-colors"
            title="Approve"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </button>

          <button
            @click="$emit('reject', member.id)"
            class="p-1.5 text-red-600 hover:bg-red-100 rounded transition-colors"
            title="Reject"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
