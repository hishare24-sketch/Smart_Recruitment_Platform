<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import { useMessagesStore } from '@/stores/MessagesStore'

const store = useMessagesStore()
const activeId = ref(store.conversations[0]?.id ?? 0)
const draft = ref('')
const threadRef = ref<HTMLElement | null>(null)

const active = computed(() => store.conversations.find(c => c.id === activeId.value))

async function scrollBottom() {
  await nextTick()
  if (threadRef.value)
    threadRef.value.scrollTop = threadRef.value.scrollHeight
}

function selectConversation(id: number) {
  activeId.value = id
  store.markRead(id)
  scrollBottom()
}

async function sendMessage() {
  const text = draft.value.trim()
  if (!text || !active.value)
    return
  store.send(active.value.id, text)
  draft.value = ''
  await scrollBottom()
}

onMounted(() => {
  if (active.value)
    store.markRead(active.value.id)
  scrollBottom()
})
</script>

<template>
  <div>
    <h1 class="mb-4 text-2xl font-bold text-content">الرسائل</h1>
    <BaseCard :padded="false" class="flex overflow-hidden" style="height: calc(100vh - 180px)">
      <!-- Conversation list -->
      <div class="hidden w-[300px] min-w-[300px] border-e border-ui md:block">
        <ul class="overflow-y-auto" style="max-height: 100%">
          <li v-for="(conv, i) in store.conversations" :key="conv.id">
            <button
              type="button"
              class="conv-row"
              :class="{ 'is-active': conv.id === activeId }"
              @click="selectConversation(conv.id)"
            >
              <BaseAvatar color="emerald">{{ conv.initial }}</BaseAvatar>
              <div class="min-w-0 flex-1">
                <div class="truncate font-bold text-content">{{ conv.name }}</div>
                <div class="truncate text-sm text-muted">
                  {{ conv.messages[conv.messages.length - 1]?.text }}
                </div>
              </div>
              <span
                v-if="conv.unread"
                class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1.5 text-xs font-bold text-on-accent"
              >{{ conv.unread }}</span>
            </button>
            <div v-if="i < store.conversations.length - 1" class="border-t border-ui" />
          </li>
        </ul>
      </div>

      <!-- Thread -->
      <div v-if="active" class="flex flex-1 flex-col">
        <div class="flex items-center gap-3 border-b border-ui p-4">
          <BaseAvatar color="emerald">{{ active.initial }}</BaseAvatar>
          <div>
            <div class="font-bold text-content">{{ active.name }}</div>
            <div class="text-xs text-muted">{{ active.role }}</div>
          </div>
        </div>

        <div ref="threadRef" class="flex-1 overflow-y-auto bg-bg p-4">
          <div
            v-for="(m, i) in active.messages"
            :key="i"
            class="mb-2 flex"
            :class="m.from === 'me' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="rounded-ui-lg p-3 text-sm"
              :class="m.from === 'me' ? 'bg-brand text-on-brand' : 'border-ui bg-surface text-content'"
              style="max-width: 70%"
            >
              {{ m.text }}
              <div class="mt-1 text-xs opacity-70">{{ m.time }}</div>
            </div>
          </div>
        </div>

        <div class="border-t border-ui p-3">
          <BaseInput
            v-model="draft"
            placeholder="اكتب رسالة..."
            @keyup.enter="sendMessage"
          >
            <template #suffix>
              <button
                type="button"
                class="icon-btn ms-1 !h-8 !w-8 text-brand"
                aria-label="إرسال"
                @click="sendMessage"
              >
                <BaseIcon name="mdi-send" :size="20" />
              </button>
            </template>
          </BaseInput>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
