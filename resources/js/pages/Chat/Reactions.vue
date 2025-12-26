<template>
  <div>
    <h1>Reactions for Message #{{ message.id }}</h1>
    <ul>
      <li v-for="(group, type) in reactions.grouped" :key="type">
        {{ type }}: {{ group.count }}
        <ul>
          <li v-for="user in group.users" :key="user.user_id">
            {{ user.name }} ({{ user.created_at }})
          </li>
        </ul>
      </li>
    </ul>
    <p>Total Reactions: {{ reactions.total_reactions }}</p>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/inertia-vue3'
const { props } = usePage()
const message = props.value.message
const reactions = props.value.reactions
</script>

<style scoped>
ul { padding-left: 20px; }
</style>
