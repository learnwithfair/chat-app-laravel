<template>
  <div
    class="flex items-center gap-1 mt-0"
    :class="alignRight ? 'justify-end flex-row-reverse' : 'justify-end'"
  >
    <!-- Existing Reactions -->
    <button
      v-if="hasReactions"
      @click.stop="showReactionDetails"
      class="flex items-center space-x-1 bg-white border border-gray-200 rounded-full px-2 py-1 text-xs hover:bg-gray-50 shadow-sm"
    >
      <span v-for="emoji in uniqueEmojis.slice(0, 3)" :key="emoji" class="text-sm">
        {{ emoji }}
      </span>
      <span class="font-semibold text-gray-700 ml-1">
        {{ totalReactionCount }}
      </span>
    </button>

    <!-- Add Reaction -->
    <div class="relative">
      <button
        @click.stop="toggleReactionPicker"
        class="flex items-center justify-center w-7 h-7 bg-white border border-gray-200 rounded-full hover:bg-gray-50 shadow-sm cursor-pointer"
        title="Add reaction"
      >
        <svg
          class="w-4 h-4 text-gray-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M14.828 14.828a4 4 0 01-5.656 0M15 11h.01M9 11h.01M12 2a10 10 0 100 20 10 10 0 000-20z"
          />
        </svg>
      </button>    
      
      <!-- Reaction Picker Dropdown -->
      <div
        v-if="showPicker"
        v-click-outside="closeReactionPicker"
        :class="[
          'absolute bottom-full mb-2 bg-white rounded-lg shadow-xl border border-gray-200 p-2 z-50',
          alignRight ? 'right-0' : 'left-0',
        ]"
      >
        <div class="flex items-center space-x-2">
          <button
            v-for="emoji in availableReactions"
            :key="emoji"
            @click.stop="addReaction(emoji)"
            class="text-2xl hover:scale-125 transition-transform p-1 rounded hover:bg-gray-100"
            :title="getEmojiName(emoji)"
          >
            {{ emoji }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reaction Details Modal -->
  <div
    v-if="showDetails"
    @click.stop="closeDetails"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
  >
    <div
      @click.stop
      class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[70vh] overflow-hidden"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Reactions</h3>
        <button @click.stop="closeDetails" class="text-gray-500 hover:text-gray-700">
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

      <!-- Reaction Filter Tabs -->
      <div
        class="flex items-center space-x-1 p-2 border-b border-gray-200 overflow-x-auto"
      >
        <button
          @click="selectedReactionFilter = 'all'"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap',
            selectedReactionFilter === 'all'
              ? 'bg-blue-100 text-blue-600'
              : 'text-gray-600 hover:bg-gray-100',
          ]"
        >
          All {{ totalReactionCount }}
        </button>
        <button
          v-for="reaction in uniqueReactionTypes"
          :key="reaction.emoji"
          @click="selectedReactionFilter = reaction.emoji"
          :class="[
            'px-3 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap flex items-center space-x-1',
            selectedReactionFilter === reaction.emoji
              ? 'bg-blue-100 text-blue-600'
              : 'text-gray-600 hover:bg-gray-100',
          ]"
        >
          <span class="text-lg">{{ reaction.emoji }}</span>
          <span>{{ reaction.count }}</span>
        </button>
      </div>

      <!-- Users List -->
      <div class="overflow-y-auto max-h-96">
        <div
          v-for="user in filteredReactionUsers"
          :key="user.id"
          class="flex items-center justify-between p-4 hover:bg-gray-50"
        >
          <div class="flex items-center">
            <img
              :src="user.avatar"
              :alt="user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <span class="ml-3 font-medium text-gray-900">{{ user.name }}</span>
          </div>
          <span class="text-2xl">{{ user.reaction }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  reactions: {
    type: Array,
    default: () => [],
  },
  alignRight: {
    type: Boolean,
    default: false,
  },
  messageId: {
    type: [String, Number],
    required: true,
  },
});

const emit = defineEmits(["add-reaction", "show-details"]);

// Available reactions (like Messenger)
const availableReactions = ["❤️", "😂", "😮", "😢", "😡", "👍", "👎"];

const emojiNames = {
  "❤️": "Love",
  "😂": "Haha",
  "😮": "Wow",
  "😢": "Sad",
  "😡": "Angry",
  "👍": "Like",
  "👎": "Dislike",
};

const showPicker = ref(false);
const showDetails = ref(false);
const selectedReactionFilter = ref("all");

// Computed
const hasReactions = computed(() => {
  return props.reactions && props.reactions.length > 0;
});

const uniqueEmojis = computed(() => {
  return [...new Set(props.reactions.map((r) => r.emoji))];
});

const totalReactionCount = computed(() => {
  return props.reactions.reduce((sum, r) => sum + r.count, 0);
});

const uniqueReactionTypes = computed(() => {
  const grouped = {};
  props.reactions.forEach((r) => {
    if (grouped[r.emoji]) {
      grouped[r.emoji].count += r.count;
    } else {
      grouped[r.emoji] = { emoji: r.emoji, count: r.count };
    }
  });
  return Object.values(grouped);
});

const filteredReactionUsers = computed(() => {
  // This would come from your backend/store
  // For now, returning mock data based on reactions
  const allUsers = [];

  props.reactions.forEach((reaction) => {

    console.log("react");
    console.log(reaction);
    // Mock: Generate users for each reaction
    for (let i = 0; i < reaction.count; i++) {
      allUsers.push({
        id: `${reaction.emoji}-${i}`,
        name: `User ${i + 1}`,
        avatar: `https://i.pravatar.cc/150?img=${Math.floor(Math.random() * 50)}`,
        reaction: reaction.emoji,
      });
    }
  });

  if (selectedReactionFilter.value === "all") {
    return allUsers;
  }

  return allUsers.filter((user) => user.reaction === selectedReactionFilter.value);
});

// Methods
const toggleReactionPicker = () => {
  showPicker.value = !showPicker.value;
};

const closeReactionPicker = () => {
  showPicker.value = false;
};

const addReaction = (emoji) => {
  emit("add-reaction", {
    messageId: props.messageId,
    emoji: emoji,
  });
  showPicker.value = false;
};

const showReactionDetails = () => {
  showDetails.value = true;
  selectedReactionFilter.value = "all";
};

const closeDetails = () => {
  showDetails.value = false;
};

const getEmojiName = (emoji) => {
  return emojiNames[emoji] || emoji;
};

// Click outside directive functionality
const clickOutside = {
  beforeMount(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener("click", el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener("click", el.clickOutsideEvent);
  },
};

// Register directive
const vClickOutside = clickOutside;
</script>

<style scoped>
/* Smooth animations */
button {
  transition: all 0.2s ease-in-out;
}
</style>
