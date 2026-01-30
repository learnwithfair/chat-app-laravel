<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import EmojiPicker from "./EmojiPicker.vue";

const props = defineProps({
  modelValue: String,
  conversationId: [String, Number],
  isBlocked: Boolean,
  canSendMessage: Boolean,
  isEditing: Boolean,
});

const emit = defineEmits([
  "update:modelValue",
  "send",
  "send-voice",
  "typing-change",
  "send-files",
]);

const showEmoji = ref(false);
const isRecording = ref(false);
const recordingTime = ref(0);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const recordingInterval = ref(null);

// File upload state
const showAttachMenu = ref(false);
const selectedFiles = ref([]);
const isDragging = ref(false);

// File input refs
const fileInputRef = ref(null);
const imageInputRef = ref(null);
const videoInputRef = ref(null);
const audioInputRef = ref(null);
const documentInputRef = ref(null);

// Attachment options
const attachmentOptions = [
  {
    id: "camera",
    label: "Camera",
    icon: "camera",
    color: "bg-pink-500",
    accept: "image/*",
    capture: true,
    ref: imageInputRef,
  },
  {
    id: "photos",
    label: "Photos & Videos",
    icon: "image",
    color: "bg-purple-500",
    accept: "image/*,video/*",
    multiple: true,
    ref: imageInputRef,
  },
  {
    id: "document",
    label: "Document",
    icon: "file-text",
    color: "bg-blue-500",
    accept: ".pdf,.doc,.docx,.txt,.xls,.xlsx,.ppt,.pptx",
    multiple: true,
    ref: documentInputRef,
  },
  {
    id: "video",
    label: "Video",
    icon: "video",
    color: "bg-red-500",
    accept: "video/*",
    multiple: true,
    ref: videoInputRef,
  },
  {
    id: "audio",
    label: "Audio",
    icon: "music",
    color: "bg-green-500",
    accept: "audio/*",
    multiple: true,
    ref: audioInputRef,
  },
  {
    id: "file",
    label: "File",
    icon: "file",
    color: "bg-orange-500",
    accept: "*",
    multiple: true,
    ref: fileInputRef,
  },
];

function toggleEmoji() {
  showEmoji.value = !showEmoji.value;
}

function addEmoji(emoji) {
  emit("update:modelValue", (props.modelValue || "") + emoji);
}

// File handling functions
function toggleAttachMenu() {
  showAttachMenu.value = !showAttachMenu.value;
}

function handleFileSelect(event, option) {
  const files = Array.from(event.target.files);
  if (files.length > 0) {
    const newFiles = files.map((file) => ({
      id: Date.now() + Math.random(),
      file,
      name: file.name,
      size: file.size,
      type: file.type,
      preview: file.type.startsWith("image/") ? URL.createObjectURL(file) : null,
      category: option.label,
    }));
    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    showAttachMenu.value = false;
  }
  // Reset input
  event.target.value = "";
}

function handleDragOver(event) {
  event.preventDefault();
  isDragging.value = true;
}

function handleDragLeave() {
  isDragging.value = false;
}

function handleDrop(event) {
  event.preventDefault();
  isDragging.value = false;

  const files = Array.from(event.dataTransfer.files);
  const newFiles = files.map((file) => ({
    id: Date.now() + Math.random(),
    file,
    name: file.name,
    size: file.size,
    type: file.type,
    preview: file.type.startsWith("image/") ? URL.createObjectURL(file) : null,
    category: "Dropped File",
  }));
  selectedFiles.value = [...selectedFiles.value, ...newFiles];
}

function removeFile(fileId) {
  const removed = selectedFiles.value.find((f) => f.id === fileId);
  if (removed?.preview) {
    URL.revokeObjectURL(removed.preview);
  }
  selectedFiles.value = selectedFiles.value.filter((f) => f.id !== fileId);
}

function clearAllFiles() {
  selectedFiles.value.forEach((file) => {
    if (file.preview) {
      URL.revokeObjectURL(file.preview);
    }
  });
  selectedFiles.value = [];
}

function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

function getFileIconClass(type) {
  if (type.startsWith("image/")) return "image";
  if (type.startsWith("video/")) return "video";
  if (type.startsWith("audio/")) return "music";
  if (type.includes("pdf") || type.includes("document") || type.includes("text"))
    return "file-text";
  return "file";
}

// function handleSendWithFiles() {
//   if (selectedFiles.value.length > 0) {
//     emit("send-files", selectedFiles.value);
//     clearAllFiles();
//   } else {
//     emit("send");
//   }
// }
function handleSendWithFiles() {
  if (selectedFiles.value.length > 0) {
    emit("send-files", selectedFiles.value); // ← This emits files
    clearAllFiles();
  } else {
    emit("send"); // ← This doesn't include files
  }
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
  if (!event.target.closest(".attach-wrapper")) {
    showAttachMenu.value = false;
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
  clearAllFiles();
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
  <div class="bg-white border-t border-gray-200">
    <!-- File Preview Section -->
    <div v-if="selectedFiles.length > 0" class="border-b border-gray-200 p-4 bg-gray-50">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
          {{ selectedFiles.length }} file{{ selectedFiles.length > 1 ? "s" : "" }}
          selected
        </h4>
        <button
          @click="clearAllFiles"
          class="text-xs text-red-600 hover:text-red-700 font-medium"
        >
          Clear all
        </button>
      </div>

      <div
        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto"
      >
        <div
          v-for="file in selectedFiles"
          :key="file.id"
          class="relative group bg-white rounded-lg border border-gray-200 p-3 hover:shadow-md transition-shadow"
        >
          <button
            @click="removeFile(file.id)"
            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg hover:bg-red-600 z-10"
          >
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <!-- Image Preview -->
          <img
            v-if="file.preview"
            :src="file.preview"
            :alt="file.name"
            class="w-full h-24 object-cover rounded mb-2"
          />
          <!-- File Icon -->
          <div
            v-else
            class="w-full h-24 bg-gray-100 rounded mb-2 flex items-center justify-center"
          >
            <svg
              class="w-10 h-10 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <!-- Generic file icon -->
              <path
                v-if="getFileIconClass(file.type) === 'file'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
              />
              <!-- Image icon -->
              <path
                v-else-if="getFileIconClass(file.type) === 'image'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
              <!-- Video icon -->
              <path
                v-else-if="getFileIconClass(file.type) === 'video'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
              />
              <!-- Music icon -->
              <path
                v-else-if="getFileIconClass(file.type) === 'music'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"
              />
              <!-- Document icon -->
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </div>

          <p class="text-xs font-medium text-gray-700 truncate" :title="file.name">
            {{ file.name }}
          </p>
          <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
        </div>
      </div>
    </div>

    <!-- Main Input Area -->
    <div
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @drop="handleDrop"
      :class="[
        'p-4 transition-colors relative',
        isDragging ? 'bg-blue-50 border-2 border-blue-400 border-dashed' : '',
      ]"
    >
      <!-- Drag Drop Overlay -->
      <div
        v-if="isDragging"
        class="absolute inset-0 flex items-center justify-center bg-blue-50 bg-opacity-90 z-10 rounded-lg"
      >
        <div class="text-center">
          <svg
            class="w-12 h-12 text-blue-500 mx-auto mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
            />
          </svg>
          <p class="text-lg font-semibold text-blue-600">Drop files here</p>
        </div>
      </div>

      <!-- Blocked / Cannot Send Message Indicator -->
      <div v-if="isBlocked || !canSendMessage" class="py-1 text-center text-gray-500">
        <p class="flex items-center justify-center gap-2 font-semibold">
          <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
              clip-rule="evenodd"
            />
          </svg>

          <span>You can't send messages to this conversation</span>
        </p>

        <p v-if="isBlocked" class="text-sm mt-1">This user is blocked</p>
      </div>

      <!-- Recording Mode -->
      <div
        v-else-if="isRecording"
        class="flex items-center space-x-3 bg-red-50 rounded-full px-4 py-2"
      >
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

        <div class="flex items-center space-x-2 flex-1">
          <div class="w-3 h-3 bg-red-600 rounded-full animate-pulse"></div>
          <span class="text-red-600 font-medium">{{ formatTime(recordingTime) }}</span>
          <span class="text-gray-500 text-sm">Recording...</span>
        </div>

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
        <!-- Attach Button with Menu -->
        <div class="relative attach-wrapper">
          <button
            @click.stop="toggleAttachMenu"
            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
            title="Attach files"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
              />
            </svg>
          </button>

          <!-- Attachment Menu -->
          <div
            v-if="showAttachMenu"
            class="absolute bottom-14 left-0 bg-white rounded-2xl shadow-2xl border border-gray-200 p-3 z-20 min-w-[280px]"
          >
            <div class="grid grid-cols-3 gap-3">
              <button
                v-for="option in attachmentOptions"
                :key="option.id"
                @click="option.ref.value?.click()"
                class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-50 transition-colors group"
              >
                <div
                  :class="[
                    option.color,
                    'p-3 rounded-full mb-2 group-hover:scale-110 transition-transform',
                  ]"
                >
                  <svg
                    class="w-6 h-6 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <!-- Camera -->
                    <path
                      v-if="option.icon === 'camera'"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <!-- Image -->
                    <path
                      v-else-if="option.icon === 'image'"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                    <!-- File Text -->
                    <path
                      v-else-if="option.icon === 'file-text'"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                    <!-- Video -->
                    <path
                      v-else-if="option.icon === 'video'"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                    <!-- Music -->
                    <path
                      v-else-if="option.icon === 'music'"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"
                    />
                    <!-- Generic File -->
                    <path
                      v-else
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                    />
                  </svg>
                </div>
                <span class="text-xs font-medium text-gray-700 text-center">
                  {{ option.label }}
                </span>
              </button>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200">
              <p class="text-xs text-gray-500 text-center">
                or drag and drop files anywhere
              </p>
            </div>
          </div>

          <!-- Hidden File Inputs -->
          <input
            v-for="option in attachmentOptions"
            :key="option.id"
            :ref="(el) => (option.ref.value = el)"
            type="file"
            :accept="option.accept"
            :multiple="option.multiple"
            :capture="option.capture ? 'environment' : undefined"
            @change="(e) => handleFileSelect(e, option)"
            class="hidden"
          />
        </div>

        <!-- Emoji wrapper -->
        <div class="relative emoji-wrapper">
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
            @keydown.enter.prevent="handleSendWithFiles"
            placeholder="Type a message..."
            rows="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
          ></textarea>
        </div>

        <!-- Voice/Send Button -->
        <button
          v-if="!modelValue?.trim() && selectedFiles.length === 0"
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
          @click="handleSendWithFiles"
          :disabled="!modelValue?.trim() && selectedFiles.length === 0"
          :class="[
            'p-2 rounded-full transition-colors',
            modelValue?.trim() || selectedFiles.length > 0
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
  </div>
</template>
