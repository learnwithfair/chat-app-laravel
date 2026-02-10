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

const emit = defineEmits(["add-reaction", "show-reaction-modal"]);

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
  // Emit to parent (MessageItem) to open the modal
  emit("show-reaction-modal", {
    messageId: props.messageId,
    reactions: props.reactions,
  });
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
</template>

<style scoped>
/* Smooth animations */
button {
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}
</style>
