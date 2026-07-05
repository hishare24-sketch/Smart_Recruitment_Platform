<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import type { AppNotification } from '@/stores/NotificationsStore'
import { useNotificationsStore } from '@/stores/NotificationsStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'

const store = useNotificationsStore()
const router = useRouter()
const filter = ref<'all' | 'unread'>('all')

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', orange: 'warning', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}

// الإجراء المباشر من الإشعار: تعليمه كمقروء ثم الانتقال لتنفيذ الإجراء
function runAction(n: AppNotification) {
  if (!n.actionTo)
    return
  store.markRead(n.id)
  router.push(n.actionTo)
}

const filtered = computed(() =>
  filter.value === 'unread' ? store.notifications.filter(n => !n.read) : store.notifications,
)
</script>

<template>
  <div class="mx-auto" style="max-width: 820px">
    <PageHeader title="الإشعارات" :subtitle="`لديك ${store.unreadCount} إشعارات غير مقروءة`" icon="mdi-bell-outline">
      <template #actions>
        <BaseButton variant="ghost" size="sm" :disabled="!store.unreadCount" @click="store.markAllRead">
          <BaseIcon name="mdi-check-all" :size="16" />تعليم الكل كمقروء
        </BaseButton>
      </template>
    </PageHeader>

    <div class="seg mb-4">
      <button type="button" class="seg-btn" :class="{ 'is-active': filter === 'all' }" @click="filter = 'all'">الكل</button>
      <button type="button" class="seg-btn" :class="{ 'is-active': filter === 'unread' }" @click="filter = 'unread'">غير المقروءة ({{ store.unreadCount }})</button>
    </div>

    <BaseCard :padded="false" class="overflow-hidden">
      <div
        v-for="(n, i) in filtered"
        :key="n.id"
        class="flex cursor-pointer items-start gap-3 p-3 transition hover:bg-surfalt"
        :class="[{ 'border-t border-ui': i > 0 }]"
        :style="!n.read ? { background: 'rgba(var(--v-theme-primary), 0.06)' } : {}"
        @click="store.toggleRead(n.id)"
      >
        <BaseAvatar :color="mapColor(n.color)" tonal square><BaseIcon :name="n.icon" :size="20" /></BaseAvatar>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1 font-bold text-content">
            {{ n.title }}
            <span v-if="!n.read" class="inline-block h-2 w-2 rounded-full" style="background: rgb(var(--v-theme-error))" />
          </div>
          <div class="text-sm text-muted">{{ n.body }}</div>
          <BaseButton
            v-if="n.actionTo"
            size="sm"
            variant="tonal-brand"
            class="mt-2"
            @click.stop="runAction(n)"
          >
            <BaseIcon name="mdi-arrow-left-circle-outline" :size="16" />{{ n.actionLabel ?? 'تنفيذ الإجراء' }}
          </BaseButton>
        </div>
        <span class="shrink-0 text-xs text-muted">{{ n.time }}</span>
      </div>
      <div v-if="!filtered.length" class="py-10 text-center text-muted">
        <BaseIcon name="mdi-bell-check-outline" :size="48" />
        <div class="mt-2">لا توجد إشعارات</div>
      </div>
    </BaseCard>
  </div>
</template>
