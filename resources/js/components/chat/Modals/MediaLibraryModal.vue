<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  media: { type: Array, default: () => [] },
  audio: { type: Array, default: () => [] },
  files: { type: Array, default: () => [] },
  links: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(["close"]);

const activeTab = ref("all");

const tabs = [
  { label: "All", value: "all" },
  { label: "Images", value: "images" },
  { label: "Videos", value: "videos" },
  { label: "Audio", value: "audio" },
  { label: "Files", value: "files" },
  { label: "Links", value: "links" },
];

const mediaImages = computed(() => props.media.filter((item) => item.type === "image"));
const mediaVideos = computed(() => props.media.filter((item) => item.type === "video"));

const getTabCount = (tabValue) => {
  switch (tabValue) {
    case "all":
      return (
        props.media.length + props.audio.length + props.files.length + props.links.length
      );
    case "images":
      return mediaImages.value.length;
    case "videos":
      return mediaVideos.value.length;
    case "audio":
      return props.audio.length;
    case "files":
      return props.files.length;
    case "links":
      return props.links.length;
    default:
      return 0;
  }
};

const formatDate = (date) => {
  if (!date) return "";
  const d = new Date(date);
  const now = new Date();
  const diffInMs = now - d;
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

  if (diffInDays === 0) return "Today";
  if (diffInDays === 1) return "Yesterday";
  if (diffInDays < 7) return `${diffInDays} days ago`;

  return d.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

const formatFileSize = (bytes) => {
  if (!bytes) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
};

const openMediaPreview = (item) => {
  window.open(item.url, "_blank");
};
</script>

<template>
  <div
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
  >
    <div
      class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col"
    >
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-900">Media & Links</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-200 px-6">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          :class="[
            'px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer',
            activeTab === tab.value
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700',
          ]"
        >
          {{ tab.label }}
          <span
            v-if="getTabCount(tab.value) > 0"
            class="ml-2 px-2 py-0.5 text-xs rounded-full"
            :class="
              activeTab === tab.value
                ? 'bg-blue-100 text-blue-600'
                : 'bg-gray-100 text-gray-600'
            "
          >
            {{ getTabCount(tab.value) }}
          </span>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6 scrollbar-custom">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <div
            class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"
          ></div>
        </div>

        <!-- All Tab -->
        <div v-else-if="activeTab === 'all'">
          <!-- Images Section -->
          <div v-if="media.length > 0" class="mb-8">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Images & Videos</h4>
            <div class="grid grid-cols-6 gap-3">
              <div
                v-for="item in media"
                :key="item.id"
                class="relative aspect-square group cursor-pointer"
                @click="openMediaPreview(item)"
              >
                <img
                  v-if="item.type === 'image'"
                  :src="item.url"
                  :alt="item.name"
                  class="w-full h-full object-cover rounded-lg"
                />
                <div
                  v-else-if="item.type === 'video'"
                  class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center"
                >
                  <video
                    :src="item.url"
                    class="w-full h-full object-cover rounded-lg"
                    muted
                    playsinline
                  ></video>
                </div>
                <!-- Hover Overlay -->
                <div
                  class="absolute inset-0 transition-all rounded-lg flex items-center justify-center pointer-events-none"
                >
                  <svg
                    class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Audio Section -->
          <div v-if="audio.length > 0" class="mb-8">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Audio Files</h4>
            <div class="space-y-2">
              <div
                v-for="item in audio"
                :key="item.id"
                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div
                  class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-blue-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"
                    />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ item.name }}
                  </p>
                  <p class="text-xs text-gray-500">{{ formatDate(item.createdAt) }}</p>
                </div>
                <a
                  :href="item.url"
                  download
                  class="text-blue-600 hover:text-blue-700 transition-colors"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                    />
                  </svg>
                </a>
              </div>
            </div>
          </div>

          <!-- Files Section -->
          <div v-if="files.length > 0" class="mb-8">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Documents</h4>
            <div class="space-y-2">
              <div
                v-for="item in files"
                :key="item.id"
                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div
                  class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-gray-600"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ item.name }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ formatFileSize(item.size) }} • {{ formatDate(item.createdAt) }}
                  </p>
                </div>
                <a
                  :href="item.url"
                  download
                  class="text-blue-600 hover:text-blue-700 transition-colors"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                    />
                  </svg>
                </a>
              </div>
            </div>
          </div>

          <!-- Links Section -->
          <div v-if="links.length > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Links</h4>
            <div class="space-y-2">
              <a
                v-for="(link, index) in links"
                :key="index"
                :href="link.url"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group"
              >
                <div
                  class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"
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
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-medium text-blue-600 truncate group-hover:text-blue-700"
                  >
                    {{ link.url }}
                  </p>
                  <p class="text-xs text-gray-500">{{ formatDate(link.created_at) }}</p>
                </div>
                <svg
                  class="w-5 h-5 text-gray-400 group-hover:text-gray-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
              </a>
            </div>
          </div>

          <!-- Empty State for All -->
          <div
            v-if="
              media.length === 0 &&
              audio.length === 0 &&
              files.length === 0 &&
              links.length === 0
            "
            class="text-center py-12"
          >
            <svg
              class="w-16 h-16 mx-auto text-gray-300 mb-4"
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
            <p class="text-gray-500">No media or links shared yet</p>
          </div>
        </div>

        <!-- Images Tab -->
        <div v-else-if="activeTab === 'images'">
          <div v-if="mediaImages.length > 0" class="grid grid-cols-6 gap-3">
            <div
              v-for="item in mediaImages"
              :key="item.id"
              class="relative aspect-square group cursor-pointer"
              @click="openMediaPreview(item)"
            >
              <img
                :src="item.url"
                :alt="item.name"
                class="w-full h-full object-cover rounded-lg"
              />
              <div
                class="absolute inset-0 transition-all rounded-lg flex items-center justify-center pointer-events-none"
              >
                <svg
                  class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                  />
                </svg>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <svg
              class="w-16 h-16 mx-auto text-gray-300 mb-4"
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
            <p class="text-gray-500">No images shared yet</p>
          </div>
        </div>

        <!-- Videos Tab -->
        <div v-else-if="activeTab === 'videos'">
          <div v-if="mediaVideos.length > 0" class="grid grid-cols-6 gap-3">
            <div
              v-for="item in mediaVideos"
              :key="item.id"
              class="relative aspect-square group cursor-pointer"
              @click="openMediaPreview(item)"
            >
              <div
                class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center"
              >
                <video
                  :src="item.url"
                  class="w-full h-full object-cover rounded-lg"
                  muted
                  playsinline
                ></video>
              </div>
              <div
                class="absolute inset-0 transition-all rounded-lg flex items-center justify-center pointer-events-none"
              >
                <svg
                  class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                  />
                </svg>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <svg
              class="w-16 h-16 mx-auto text-gray-300 mb-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"
              />
            </svg>
            <p class="text-gray-500">No videos shared yet</p>
          </div>
        </div>

        <!-- Audio Tab -->
        <div v-else-if="activeTab === 'audio'">
          <div v-if="audio.length > 0" class="space-y-2">
            <div
              v-for="item in audio"
              :key="item.id"
              class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div
                class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"
              >
                <svg
                  class="w-5 h-5 text-blue-600"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"
                  />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(item.createdAt) }}</p>
              </div>
              <a
                :href="item.url"
                download
                class="text-blue-600 hover:text-blue-700 transition-colors"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  />
                </svg>
              </a>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <svg
              class="w-16 h-16 mx-auto text-gray-300 mb-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"
              />
            </svg>
            <p class="text-gray-500">No audio files shared yet</p>
          </div>
        </div>

        <!-- Files Tab -->
        <div v-else-if="activeTab === 'files'">
          <div v-if="files.length > 0" class="space-y-2">
            <div
              v-for="item in files"
              :key="item.id"
              class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div
                class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center"
              >
                <svg
                  class="w-5 h-5 text-gray-600"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</p>
                <p class="text-xs text-gray-500">
                  {{ formatFileSize(item.size) }} • {{ formatDate(item.createdAt) }}
                </p>
              </div>
              <a
                :href="item.url"
                download
                class="text-blue-600 hover:text-blue-700 transition-colors"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  />
                </svg>
              </a>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <svg
              class="w-16 h-16 mx-auto text-gray-300 mb-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                clip-rule="evenodd"
              />
            </svg>
            <p class="text-gray-500">No documents shared yet</p>
          </div>
        </div>

        <!-- Links Tab -->
        <div v-else-if="activeTab === 'links'">
          <div v-if="links.length > 0" class="space-y-2">
            <a
              v-for="(link, index) in links"
              :key="index"
              :href="link.url"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group"
            >
              <div
                class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"
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
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm font-medium text-blue-600 truncate group-hover:text-blue-700"
                >
                  {{ link.url }}
                </p>
                <p class="text-xs text-gray-500">{{ formatDate(link.created_at) }}</p>
              </div>
              <svg
                class="w-5 h-5 text-gray-400 group-hover:text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                />
              </svg>
            </a>
          </div>
          <div v-else class="text-center py-12">
            <svg
              class="w-16 h-16 mx-auto text-gray-300 mb-4"
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
            <p class="text-gray-500">No links shared yet</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-custom {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 10px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
  background-color: rgba(107, 114, 128, 0.7);
}
</style>
