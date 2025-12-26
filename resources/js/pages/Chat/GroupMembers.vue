<template>
  <div>
    <h1>Group Members - {{ conversation.name }}</h1>
    <ul>
      <li v-for="member in members" :key="member.id">
        {{ member.user.name }} ({{ member.role }})
      </li>
    </ul>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/inertia-vue3'
const { props } = usePage()
const conversation = props.value.conversation
const members = props.value.members ?? []
</script>

<style scoped>
ul { padding-left: 20px; }
</style>
