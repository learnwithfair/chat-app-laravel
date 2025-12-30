<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import EmojiPicker from "./EmojiPicker.vue";

const props = defineProps({
  modelValue: String,
  conversationId: [String, Number],
  isBlocked: Boolean,
  isEditing: Boolean,
});

const emit = defineEmits(["update:modelValue", "send", "send-voice", "typing-change"]);

const showEmoji = ref(false);
const isRecording = ref(false);
const recordingTime = ref(0);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const recordingInterval = ref(null);

function toggleEmoji() {
  showEmoji.value = !showEmoji.value;
}

function addEmoji(emoji) {
  emit("update:modelValue", (props.modelValue || "") + emoji);
}

// Voice Recording Functions
async function startRecording() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder.value = new MediaRecorder(stream);
    audioChunks.value = [];
    recordingTime.value = 0;

    mediaRecorder.value.ondataavailable = (event) => {
      audioChunks.value.push(event.data);
    };

    mediaRecorder.value.onstop = () => {
      const audioBlob = new Blob(audioChunks.value, { type: "audio/webm" });
      emit("send-voice", audioBlob, recordingTime.value);
      stopRecordingTimer();
      // Stop all tracks
      stream.getTracks().forEach((track) => track.stop());
    };

    mediaRecorder.value.start();
    isRecording.value = true;
    startRecordingTimer();
  } catch (error) {
    console.error("Error accessing microphone:", error);
    alert("Unable to access microphone. Please check permissions.");
  }
}

function stopRecording() {
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop();
    isRecording.value = false;
  }
}

function cancelRecording() {
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop();
    isRecording.value = false;
    audioChunks.value = [];
    stopRecordingTimer();

    // Stop all tracks without sending
    if (mediaRecorder.value.stream) {
      mediaRecorder.value.stream.getTracks().forEach((track) => track.stop());
    }
  }
}

function startRecordingTimer() {
  recordingInterval.value = setInterval(() => {
    recordingTime.value++;
  }, 1000);
}

function stopRecordingTimer() {
  if (recordingInterval.value) {
    clearInterval(recordingInterval.value);
    recordingInterval.value = null;
  }
}

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs.toString().padStart(2, "0")}`;
}

/* OUTSIDE CLICK HANDLER */
function handleClickOutside(event) {
  if (!event.target.closest(".emoji-wrapper")) {
    showEmoji.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  stopRecordingTimer();
  if (mediaRecorder.value && isRecording.value) {
    cancelRecording();
  }
});

// Watch for typing
watch(
  () => props.modelValue,
  (newVal, oldVal) => {
    if (newVal && newVal.length > 0) {
      emit("typing-change", true);
    } else if (oldVal && oldVal.length > 0 && (!newVal || newVal.length === 0)) {
      emit("typing-change", false);
    }
  }
);
</script>

<template>
  <div class="bg-white border-t border-gray-200 p-4">
    <div v-if="isBlocked" class="text-center py-4 text-gray-500">
      <svg
        class="w-8 h-8 mx-auto mb-2 text-red-500"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path
          fill-rule="evenodd"
          d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
          clip-rule="evenodd"
        />
      </svg>
      <p class="font-semibold">You can't send messages to this conversation</p>
      <p class="text-sm">This user is blocked</p>
    </div>

    <!-- Recording Mode -->
    <div
      v-else-if="isRecording"
      class="flex items-center space-x-3 bg-red-50 rounded-full px-4 py-2"
    >
      <!-- Cancel Button -->
      <button @click="cancelRecording" class="text-red-600 hover:text-red-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>

      <!-- Recording Animation -->
      <div class="flex items-center space-x-2 flex-1">
        <div class="w-3 h-3 bg-red-600 rounded-full animate-pulse"></div>
        <span class="text-red-600 font-medium">{{ formatTime(recordingTime) }}</span>
        <span class="text-gray-500 text-sm">Recording...</span>
      </div>

      <!-- Send Voice Button -->
      <button
        @click="stopRecording"
        class="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors"
      >
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
          />
        </svg>
      </button>
    </div>

    <!-- Normal Mode -->
    <div v-else class="flex items-end space-x-2">
      <button
        class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
      </button>

      <!--  Emoji wrapper -->
      <div class="relative emoji-wrapper">
        <!-- Emoji button -->
        <button
          @click.stop="toggleEmoji"
          class="p-2 text-gray-500 hover:bg-gray-100 rounded-full cursor-pointer transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </button>

        <!-- Emoji picker -->
        <EmojiPicker
          v-if="showEmoji"
          class="absolute bottom-16 right-0 z-50"
          @pick="addEmoji"
        />
      </div>

      <div class="flex-1 relative">
        <textarea
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          @keydown.enter.prevent="$emit('send')"
          placeholder="Type a message..."
          rows="1"
          class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
        ></textarea>
      </div>

      <!-- Voice/Send Button (Toggle based on input) -->
      <button
        v-if="!modelValue || !modelValue.trim()"
        @click="startRecording"
        class="p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors"
        title="Record voice message"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
          />
        </svg>
      </button>

      <button
        v-else
        @click="$emit('send')"
        :disabled="!modelValue.trim()"
        :class="[
          'p-2 rounded-full transition-colors',
          modelValue.trim()
            ? 'bg-blue-500 text-white hover:bg-blue-600'
            : 'bg-gray-200 text-gray-400 cursor-not-allowed',
        ]"
      >
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
