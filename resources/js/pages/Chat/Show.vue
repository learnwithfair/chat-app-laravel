<script lang="ts">
import { defineComponent, onMounted, onUnmounted, ref, type PropType } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import ChatLayout from '@/components/chat/ChatLayout.vue'
import MessageList from '@/components/chat/MessageList.vue'
import MessageInput from '@/components/chat/MessageInput.vue'
import axios from 'axios'

type Conversation = {
  id: number
  name?: string | null
  is_group: boolean
}

type Sender = { id: number; name: string }
type Message = {
  id: number
  body: string
  sender: Sender
  conversation_id: number
  created_at?: string
}

type Paginator<T> = { data: T[] }

// Minimal presence channel surface we use
type PresenceChannel = {
  here(cb: (users: any[]) => void): PresenceChannel
  joining(cb: (user: any) => void): PresenceChannel
  leaving(cb: (user: any) => void): PresenceChannel
  listen(event: string, cb: (payload: any) => void): PresenceChannel
  listenForWhisper(event: string, cb: (payload: any) => void): PresenceChannel
  whisper(event: string, payload: any): void
  leave(): void
}

export default defineComponent({
  name: 'ChatShow',
  components: { Head, ChatLayout, MessageList, MessageInput },
  props: {
    conversation: { type: Object as PropType<Conversation>, required: true },
    initialMessages: { type: Object as PropType<Paginator<Message>>, required: true },
  },
  setup(props) {
    const page = usePage()
    const authUser = (page.props as any).auth.user as { id: number; name: string }

    const messages = ref<Message[]>([...props.initialMessages.data].reverse())
    const typingUsers = ref<Map<number, number>>(new Map())

    let presence: PresenceChannel | null = null

    function joinChannel(): void {
      const channelName = `presence.chat.${props.conversation.id}`
      presence = (window as any).Echo.join(channelName) as PresenceChannel

      presence
        .here(() => {})
        .joining(() => {})
        .leaving((user: { id: number }) => {
          typingUsers.value.delete(user.id)
        })
        .listen('MessageCreated', (payload: Message) => {
          messages.value.push(payload)
          scrollToBottom()
        })
        .listenForWhisper('typing', ({ userId }: { userId: number }) => {
          if (userId !== authUser.id) {
            typingUsers.value.set(userId, Date.now())
            setTimeout(() => typingUsers.value.delete(userId), 3000)
          }
        })
    }

    function whisperTyping(): void {
      if (!presence) return
      presence.whisper('typing', { userId: authUser.id })
    }

    function scrollToBottom(): void {
      requestAnimationFrame(() => {
        const el = document.getElementById('chat-scroll')
        if (el) el.scrollTop = el.scrollHeight
      })
    }

    async function send(body: string): Promise<void> {
      // Option A (recommended): append locally; others get the broadcast
      const { data } = await axios.post(`/chat/${props.conversation.id}/messages`, { body })
      messages.value.push(data.message)
      scrollToBottom()
    }

    onMounted(() => {
      joinChannel()
      scrollToBottom()
    })

    onUnmounted(() => {
      if (presence) presence.leave()
    })

    // expose to template
    return {
      authUser,
      messages,
      typingUsers,
      whisperTyping,
      send,
      conversation: props.conversation,
    }
  },
})
</script>

<template>
  <ChatLayout>
    <Head :title="conversation?.name || 'Conversation'" />

    <div class="h-full grid grid-cols-12">
      <section class="col-span-12 flex flex-col h-[calc(100vh-4rem)]">
        <header class="h-16 border-b flex items-center px-4 justify-between">
          <div>
            <h2 class="font-semibold">{{ conversation?.name || "Conversation" }}</h2>
            <p class="text-xs text-gray-500">
              {{ typingUsers.size ? "Typing…" : "Online" }}
            </p>
          </div>
        </header>

        <main id="chat-scroll" class="flex-1 overflow-y-auto p-4 bg-gray-50">
          <MessageList :items="messages" :auth-id="authUser.id" />
        </main>

        <footer class="border-t p-3">
          <MessageInput @typing="whisperTyping" @send="send" />
        </footer>
      </section>
    </div>
  </ChatLayout>
</template>
