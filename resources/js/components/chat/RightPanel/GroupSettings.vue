<template>
  <div class="p-4 border-t border-gray-200 mt-6">
    <h3 class="text-lg font-semibold mb-4">Group Settings</h3>
    
    <div class="space-y-4">
      <SettingSwitch
        v-model="localSettings.allow_members_to_send_messages"
        label="Allow members to send messages"
        description="Members can send messages in this group"
        @change="updateSettings"
      />
      
      <SettingSwitch
        v-model="localSettings.allow_members_to_add_remove_participants"
        label="Allow members to add/remove participants"
        description="Members can manage group participants"
        @change="updateSettings"
      />
      
      <SettingSwitch
        v-model="localSettings.allow_members_to_change_group_info"
        label="Allow members to change group info"
        description="Members can edit group name, avatar, and description"
        @change="updateSettings"
      />
      
      <SettingSwitch
        v-model="localSettings.admins_must_approve_new_members"
        label="Admins must approve new members"
        description="New members must be approved by admins"
        @change="updateSettings"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import SettingSwitch from './SettingSwitch.vue';

const props = defineProps(['settings']);
const emit = defineEmits(['update']);

const localSettings = ref({
  allow_members_to_send_messages: props.settings?.allow_members_to_send_messages ?? true,
  allow_members_to_add_remove_participants: props.settings?.allow_members_to_add_remove_participants ?? true,
  allow_members_to_change_group_info: props.settings?.allow_members_to_change_group_info ?? true,
  admins_must_approve_new_members: props.settings?.admins_must_approve_new_members ?? true
});

const updateSettings = () => {
  emit('update', localSettings.value);
};
</script>