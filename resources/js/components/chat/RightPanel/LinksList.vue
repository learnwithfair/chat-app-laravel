<template>
  <div>
    <div v-if="links && links.length > 0" class="space-y-2">
      <a
        v-for="link in links"
        :key="link.id"
        :href="link.url"
        target="_blank"
        class="block p-3 hover:bg-gray-50 rounded-lg transition-colors border border-gray-200"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div
              class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
            >
              <svg
                class="w-5 h-5 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                />
              </svg>
            </div>
          </div>
          <div class="ml-3 flex-1 min-w-0">
            <p class="text-sm font-medium text-blue-600 hover:text-blue-700 truncate">
              {{ extractDomain(link.url) }}
            </p>
            <p class="text-xs text-gray-500 truncate mt-1">{{ link.url }}</p>
            <p v-if="link.sharedAt" class="text-xs text-gray-400 mt-1">
              Shared {{ formatDate(link.sharedAt) }}
            </p>
          </div>
        </div>
      </a>
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
          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
        />
      </svg>
      <p class="text-sm text-gray-500">No links yet</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  links: { type: Array, default: () => [] },
  conversationId: { type: [Number, String], required: true },
});

const extractDomain = (url) => {
  try {
    const domain = new URL(url).hostname;
    return domain.replace("www.", "");
  } catch {
    return url;
  }
};

const formatDate = (date) => {
  const d = new Date(date);
  const now = new Date();
  const diff = now - d;

  const minute = 60 * 1000;
  const hour = 60 * minute;
  const day = 24 * hour;

  if (diff < hour) return `${Math.floor(diff / minute)}m ago`;
  if (diff < day) return `${Math.floor(diff / hour)}h ago`;
  if (diff < 7 * day) return `${Math.floor(diff / day)}d ago`;

  return d.toLocaleDateString();
};
</script>
