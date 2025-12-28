<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import EmojiPicker from "./EmojiPicker.vue";

const props = defineProps({
    modelValue: String,
    isBlocked: Boolean,
    isEditing: Boolean,
});

const emit = defineEmits(["update:modelValue", "send"]);

const showEmoji = ref(false);

function toggleEmoji() {
    showEmoji.value = !showEmoji.value;
}

function addEmoji(emoji) {
    emit("update:modelValue", (props.modelValue || "") + emoji);
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
});
</script>



<template> 

    <div class="bg-white border-t border-gray-200 p-4">
        <div v-if="isBlocked" class="text-center py-4 text-gray-500">
            <svg class="w-8 h-8 mx-auto mb-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                    clip-rule="evenodd" />
            </svg>
            <p class="font-semibold">You can't send messages to this conversation</p>
            <p class="text-sm">This user is blocked</p>
        </div>

        <div v-else class="flex items-end space-x-2">
            <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>

            <!--  Emoji wrapper -->
            <div class="relative emoji-wrapper">
                <!-- Emoji button -->
                <button @click.stop="toggleEmoji" class="p-2 text-gray-500 hover:bg-gray-100 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                </button>

                <!-- Emoji picker -->
                <EmojiPicker v-if="showEmoji" class="absolute bottom-16 right-0 z-50" @pick="addEmoji" />
            </div>


            <div class="flex-1 relative">
                <textarea :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
                    @keydown.enter.prevent="$emit('send')" placeholder="Type a message..." rows="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>


            <button @click="$emit('send')" :disabled="!modelValue.trim()" :class="[
                'p-2 rounded-full transition-colors',
                modelValue.trim()
                    ? 'bg-blue-500 text-white hover:bg-blue-600'
                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'
            ]">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                </svg>
            </button>
        </div>
    </div>
</template>
