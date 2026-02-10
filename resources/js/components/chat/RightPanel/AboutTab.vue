<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  description: { type: String, default: "" },
  canEdit: { type: Boolean, default: false },
  createdBy: { type: String, default: "" },
  createdAt: { type: String, default: "" },
});

const emit = defineEmits(["update-description"]);

const isEditing = ref(false);
const editedDescription = ref(props.description);

watch(
  () => props.description,
  (newVal) => {
    editedDescription.value = newVal;
  }
);

const startEditing = () => {
  isEditing.value = true;
  editedDescription.value = props.description;
};

const cancelEditing = () => {
  isEditing.value = false;
  editedDescription.value = props.description;
};

const saveDescription = () => {
  emit("update-description", editedDescription.value);
  isEditing.value = false;
};
</script>

<template>
  <div class="space-y-4">
    <div class="bg-gray-50 rounded-lg p-4">
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-semibold text-gray-700">Group Description</h4>
        <button
          v-if="canEdit && !isEditing"
          @click="startEditing"
          class="text-blue-600 hover:text-blue-700 text-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
            />
          </svg>
        </button>
      </div>

      <div v-if="!isEditing">
        <p v-if="description" class="text-sm text-gray-600 whitespace-pre-wrap">
          {{ description }}
        </p>
        <p v-else class="text-sm text-gray-400 italic">No description set</p>
        <p class="text-sm mt-4">Created By {{ createdBy }} at {{ createdAt }}</p>
      </div>

      <div v-else class="space-y-2">
        <textarea
          v-model="editedDescription"
          rows="4"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm"
          placeholder="Enter group description..."
          maxlength="500"
        />
        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-500"> {{ editedDescription.length }}/500 </span>
          <div class="flex space-x-2">
            <button
              @click="cancelEditing"
              class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-200 rounded transition-colors"
            >
              Cancel
            </button>
            <button
              @click="saveDescription"
              class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
button {
  cursor: pointer;
}
</style>
