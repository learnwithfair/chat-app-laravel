<script lang="ts">
import { defineComponent, onMounted, onUnmounted, ref, type PropType } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import ChatLayout from '@/components/chat/ChatLayout.vue'
import MessageList from '@/components/chat/MessageList.vue'
import MessageInput from '@/components/chat/MessageInput.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import axios from 'axios'
import { Phone, Video, MoreVertical } from 'lucide-vue-next'

type Conversation = { id:number; name?:string|null; is_group:boolean }
type Sender = { id:number; name:string }
type Message = { id:number; body:string; sender:Sender; conversation_id:number; created_at?:string }
type Paginator<T> = { data:T[] }
type PresenceChannel = {
  here(cb:(users:any[])=>void):PresenceChannel
  joining(cb:(user:any)=>void):PresenceChannel
  leaving(cb:(user:any)=>void):PresenceChannel
  listen(event:string, cb:(payload:any)=>void):PresenceChannel
  listenForWhisper(event:string, cb:(payload:any)=>void):PresenceChannel
  whisper(event:string, payload:any):void
  leave():void
}

export default defineComponent({
  name: 'ChatShow',
  components: { Head, ChatLayout, MessageList, MessageInput, Avatar, AvatarFallback, Phone, Video, MoreVertical },
  props: {
    conversation: { type: Object as PropType<Conversation>, required: true },
    initialMessages: { type: Object as PropType<Paginator<Message>>, required: true },
  },
  setup(props) {
    const page = usePage()
    const authUser = (page.props as any).auth.user as { id:number; name:string }

    const messages = ref<Message[]>([...props.initialMessages.data].reverse())
    const typingUsers = ref<Map<number, number>>(new Map())
    let presence: PresenceChannel | null = null

    function initials(name?: string|null) {
      return (name || 'C').split(' ').map(s => s[0]).join('').slice(0,2).toUpperCase()
    }

    async function markReadAll() {
      await axios.post(`/chat/${props.conversation.id}/read-all`)
    }

    function joinChannel(): void {
      const channelName = `presence.chat.${props.conversation.id}`
      presence = (window as any).Echo.join(channelName) as PresenceChannel
      presence
        .here(() => {})
        .joining(() => {})
        .leaving((user: { id:number }) => { typingUsers.value.delete(user.id) })
        .listen('MessageCreated', (payload: Message) => {
          messages.value.push(payload)
          scrollToBottom()
          if (payload.sender.id !== authUser.id) markReadAll()
        })
        .listenForWhisper('typing', ({ userId }: { userId:number }) => {
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
      // Option A: sender-side immediate append; others via broadcast
      const { data } = await axios.post(`/chat/${props.conversation.id}/messages`, { body })
      messages.value.push(data.message)
      scrollToBottom()
      await markReadAll() // your own reads kept up to date
    }

    onMounted(() => {
      joinChannel()
      scrollToBottom()
      markReadAll()
    })
    onUnmounted(() => { if (presence) presence.leave() })

    return { authUser, messages, typingUsers, whisperTyping, send, initials }
  },
})
</script>

<template>
  <ChatLayout>
    <Head :title="conversation.name || 'Conversation'" />

    <div class="h-full grid grid-cols-[380px_1fr]">
      <!-- Sidebar -->
      <aside class="border-r flex flex-col">
        <div class="px-4 py-3">
          <h1 class="text-xl font-semibold">Chats</h1>
        </div>
        <!-- You can reuse ConversationList here via a shared prop if you fetch it -->
        <div
          class="flex-1 flex items-center justify-center text-sm text-gray-500 px-6 text-center"
        >
          Sidebar list can live here when rendering Show directly (optional)
        </div>
      </aside>

      <!-- Chat pane -->
      <section class="flex flex-col h-[calc(100vh-0rem)]">
        <!-- Header -->
        <header class="h-16 border-b px-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <Avatar class="h-9 w-9">
              <AvatarFallback>{{ initials(conversation.name) }}</AvatarFallback>
            </Avatar>
            <div>
              <div class="font-semibold leading-tight">
                {{ conversation.name || "Conversation" }}
              </div>
              <div class="text-[11px] text-gray-500">
                {{ typingUsers.size ? "Typing…" : "Online" }}
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <button class="p-2 rounded hover:bg-gray-100" aria-label="Voice call">
              <Phone class="h-4 w-4" />
            </button>
            <button class="p-2 rounded hover:bg-gray-100" aria-label="Video call">
              <Video class="h-4 w-4" />
            </button>
            <button class="p-2 rounded hover:bg-gray-100" aria-label="More">
              <MoreVertical class="h-4 w-4" />
            </button>
          </div>
        </header>

        <!-- Messages -->
        <main id="chat-scroll" class="flex-1 overflow-y-auto p-4 bg-[rgb(240,242,245)]">
          <MessageList :items="messages" :auth-id="authUser.id" />
        </main>

        <!-- Composer -->
        <footer class="border-t p-3 bg-white">
          <MessageInput @typing="whisperTyping" @send="send" />
        </footer>
      </section>
    </div>
  </ChatLayout>
</template>
