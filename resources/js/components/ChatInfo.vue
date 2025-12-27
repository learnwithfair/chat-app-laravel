<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  conversation: Object,
});

const emit = defineEmits(["close"]);

const activeSection = ref("media");

const conversationName = computed(() => {
  if (props.conversation.type === "group") {
    return props.conversation.name || "Unnamed Group";
  }
  return props.conversation.receiver?.name || "Unknown User";
});

const leaveGroup = () => {
  if (confirm("Are you sure you want to leave this group?")) {
    router.delete(`/chat/${props.conversation.id}/leave`);
  }
};

const deleteChat = () => {
  if (confirm("Are you sure you want to delete this chat?")) {
    router.delete(`/chat/${props.conversation.id}`);
  }
};

const blockUser = () => {
  const action = props.conversation.is_blocked ? "unblock" : "block";
  if (confirm(`Are you sure you want to ${action} this user?`)) {
    router.post(`/chat/${props.conversation.id}/${action}`);
  }
};

const muteChat = () => {
  router.post(`/chat/${props.conversation.id}/mute`);
};
</script>

<template>
  <div class="chat-info">
    <!-- Header -->
    <div class="info-header">
      <button class="icon-btn" @click="$emit('close')">
        <svg viewBox="0 0 24 24" width="24" height="24">
          <path fill="currentColor" d="M12 4l1.4 1.4L7.8 11H20v2H7.8l5.6 5.6L12 20l-8-8 8-8z"/>
        </svg>
      </button>
      <h3>{{ conversation.type === 'group' ? 'Group Info' : 'Contact Info' }}</h3>
    </div>

    <!-- Profile Section -->
    <div class="info-profile">
      <div class="profile-avatar">
        <img 
          v-if="conversation.type === 'private' && conversation.receiver?.avatar_path" 
          :src="conversation.receiver.avatar_path" 
          :alt="conversationName"
        />
        <img 
          v-else-if="conversation.type === 'group' && conversation.group_setting?.avatar" 
          :src="conversation.group_setting.avatar" 
          :alt="conversationName"
        />
        <span v-else class="avatar-fallback">
          {{ conversationName.charAt(0).toUpperCase() }}
        </span>
      </div>
      <h2>{{ conversationName }}</h2>
      <p v-if="conversation.type === 'group'">
        Group · {{ conversation.participants?.length || 0 }} participants
      </p>
      <p v-else>{{ conversation.receiver?.email }}</p>
    </div>

    <!-- Description (for groups) -->
    <div v-if="conversation.type === 'group' && conversation.group_setting?.description" class="info-section">
      <div class="section-item">
        <div class="item-icon">
          <svg viewBox="0 0 24 24" width="24" height="24">
            <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
          </svg>
        </div>
        <div class="item-content">
          <p class="item-label">Description</p>
          <p class="item-value">{{ conversation.group_setting.description }}</p>
        </div>
      </div>
    </div>

    <!-- Tabs (Media, Links, Docs) -->
    <div class="info-tabs">
      <button 
        :class="['tab', { active: activeSection === 'media' }]"
        @click="activeSection = 'media'"
      >
        Media
      </button>
      <button 
        :class="['tab', { active: activeSection === 'links' }]"
        @click="activeSection = 'links'"
      >
        Links
      </button>
      <button 
        :class="['tab', { active: activeSection === 'docs' }]"
        @click="activeSection = 'docs'"
      >
        Docs
      </button>
    </div>

    <div class="tab-content">
      <div v-if="activeSection === 'media'" class="media-grid">
        <p class="empty-state">No media shared yet</p>
      </div>
      <div v-else-if="activeSection === 'links'" class="links-list">
        <p class="empty-state">No links shared yet</p>
      </div>
      <div v-else class="docs-list">
        <p class="empty-state">No documents shared yet</p>
      </div>
    </div>

    <!-- Group Participants -->
    <div v-if="conversation.type === 'group'" class="info-section">
      <div class="section-header">
        <h4>{{ conversation.participants?.length || 0 }} Participants</h4>
        <button v-if="conversation.is_admin" class="icon-btn">
          <svg viewBox="0 0 24 24" width="24" height="24">
            <path fill="currentColor" d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
        </button>
      </div>
      <div class="participants-list">
        <div 
          v-for="participant in conversation.participants" 
          :key="participant.id"
          class="participant-item"
        >
          <div class="participant-avatar">
            <img v-if="participant.avatar_path" :src="participant.avatar_path" :alt="participant.name" />
            <span v-else>{{ participant.name?.charAt(0).toUpperCase() }}</span>
          </div>
          <div class="participant-info">
            <p class="participant-name">{{ participant.name }}</p>
            <p class="participant-role">{{ participant.role === 'admin' ? 'Group Admin' : 'Member' }}</p>
          </div>
          <button v-if="conversation.is_admin && participant.role !== 'admin'" class="icon-btn">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path fill="currentColor" d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Group Settings -->
    <div v-if="conversation.type === 'group' && conversation.is_admin" class="info-section">
      <h4 class="section-title">Group Settings</h4>
      <div class="settings-list">
        <div class="setting-item">
          <div class="setting-info">
            <p class="setting-label">Send Messages</p>
            <p class="setting-desc">Allow all participants to send messages</p>
          </div>
          <label class="switch">
            <input 
              type="checkbox" 
              :checked="conversation.group_setting?.allow_members_to_send_messages"
            />
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-item">
          <div class="setting-info">
            <p class="setting-label">Edit Group Info</p>
            <p class="setting-desc">Allow all participants to edit group info</p>
          </div>
          <label class="switch">
            <input 
              type="checkbox" 
              :checked="conversation.group_setting?.allow_members_to_change_group_info"
            />
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-item">
          <div class="setting-info">
            <p class="setting-label">Approve New Members</p>
            <p class="setting-desc">Admins must approve new members</p>
          </div>
          <label class="switch">
            <input 
              type="checkbox" 
              :checked="conversation.group_setting?.admins_must_approve_new_members"
            />
            <span class="slider"></span>
          </label>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="info-section">
      <div class="action-list">
        <button class="action-item" @click="muteChat">
          <div class="action-icon">
            <svg viewBox="0 0 24 24" width="24" height="24">
              <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/>
            </svg>
          </div>
          <span>{{ conversation.is_muted ? 'Unmute' : 'Mute' }} notifications</span>
        </button>

        <button v-if="conversation.type === 'private'" class="action-item" @click="blockUser">
          <div class="action-icon">
            <svg viewBox="0 0 24 24" width="24" height="24">
              <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM4 12c0-4.42 3.58-8 8-8 1.85 0 3.55.63 4.9 1.69L5.69 16.9C4.63 15.55 4 13.85 4 12zm8 8c-1.85 0-3.55-.63-4.9-1.69L18.31 7.1C19.37 8.45 20 10.15 20 12c0 4.42-3.58 8-8 8z"/>
            </svg>
          </div>
          <span>{{ conversation.is_blocked ? 'Unblock' : 'Block' }} user</span>
        </button>

        <button v-if="conversation.type === 'group'" class="action-item danger" @click="leaveGroup">
          <div class="action-icon">
            <svg viewBox="0 0 24 24" width="24" height="24">
              <path fill="currentColor" d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
            </svg>
          </div>
          <span>Exit group</span>
        </button>

        <button class="action-item danger" @click="deleteChat">
          <div class="action-icon">
            <svg viewBox="0 0 24 24" width="24" height="24">
              <path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
            </svg>
          </div>
          <span>Delete chat</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.chat-info {
  width: 400px;
  background: #111b21;
  border-left: 1px solid #2a3942;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.info-header {
  background: #202c33;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 20px;
  position: sticky;
  top: 0;
  z-index: 10;
}

.info-header h3 {
  color: #e9edef;
  font-size: 19px;
  font-weight: 500;
}

.info-profile {
  text-align: center;
  padding: 30px 20px;
  background: #111b21;
}

.profile-avatar {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  margin: 0 auto 20px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #6b7c85;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-fallback {
  color: #fff;
  font-size: 80px;
  font-weight: 500;
}

.info-profile h2 {
  color: #e9edef;
  font-size: 24px;
  font-weight: 400;
  margin-bottom: 8px;
}

.info-profile p {
  color: #8696a0;
  font-size: 14px;
}

.info-section {
  background: #111b21;
  margin-top: 10px;
  padding: 10px 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 20px;
}

.section-title {
  color: #008069;
  font-size: 14px;
  font-weight: 500;
  padding: 10px 20px;
}

.section-header h4 {
  color: #e9edef;
  font-size: 16px;
  font-weight: 400;
}

.section-item {
  display: flex;
  gap: 15px;
  padding: 12px 20px;
}

.item-icon {
  color: #8696a0;
  flex-shrink: 0;
}

.item-content {
  flex: 1;
}

.item-label {
  color: #8696a0;
  font-size: 13px;
  margin-bottom: 4px;
}

.item-value {
  color: #e9edef;
  font-size: 14px;
  line-height: 20px;
}

.info-tabs {
  display: flex;
  background: #111b21;
  border-bottom: 1px solid #2a3942;
  margin-top: 10px;
}

.tab {
  flex: 1;
  background: none;
  border: none;
  color: #8696a0;
  padding: 12px;
  font-size: 14px;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}

.tab:hover {
  background: rgba(255, 255, 255, 0.05);
}

.tab.active {
  color: #00a884;
  border-bottom-color: #00a884;
}

.tab-content {
  background: #111b21;
  padding: 20px;
  min-height: 200px;
}

.media-grid,
.links-list,
.docs-list {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  color: #8696a0;
  padding: 40px;
  font-size: 14px;
}

.participants-list {
  background: #111b21;
}

.participant-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  cursor: pointer;
  transition: background 0.2s;
}

.participant-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.participant-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #6b7c85;
  flex-shrink: 0;
}

.participant-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.participant-avatar span {
  color: #fff;
  font-size: 18px;
  font-weight: 500;
}

.participant-info {
  flex: 1;
}

.participant-name {
  color: #e9edef;
  font-size: 16px;
  margin-bottom: 2px;
}

.participant-role {
  color: #8696a0;
  font-size: 13px;
}

.settings-list {
  background: #111b21;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  gap: 15px;
}

.setting-info {
  flex: 1;
}

.setting-label {
  color: #e9edef;
  font-size: 15px;
  margin-bottom: 4px;
}

.setting-desc {
  color: #8696a0;
  font-size: 13px;
}

.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
  flex-shrink: 0;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #667781;
  transition: 0.3s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background: #00a884;
}

input:checked + .slider:before {
  transform: translateX(24px);
}

.action-list {
  background: #111b21;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 14px 20px;
  width: 100%;
  background: none;
  border: none;
  color: #e9edef;
  font-size: 15px;
  cursor: pointer;
  transition: background 0.2s;
  text-align: left;
}

.action-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.action-item.danger {
  color: #f15c6d;
}

.action-icon {
  color: #8696a0;
  display: flex;
}

.action-item.danger .action-icon {
  color: #f15c6d;
}
</style>