<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import { useApplicationsStore } from '@/stores/ApplicationsStore'
import type { ApplicationStatus } from '@/stores/ApplicationsStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseDropdown from '@/components/ui/BaseDropdown.vue'

const statusMeta: Record<ApplicationStatus, { label: string, color: string, icon: string }> = {
  submitted: { label: 'تم التقديم', color: 'info', icon: 'mdi-send-check-outline' },
  reviewing: { label: 'قيد المراجعة', color: 'secondary', icon: 'mdi-file-search-outline' },
  interview: { label: 'مقابلة', color: 'success', icon: 'mdi-calendar-check-outline' },
  rejected: { label: 'مرفوض', color: 'error', icon: 'mdi-close-circle-outline' },
  accepted: { label: 'مقبول', color: 'accent', icon: 'mdi-check-decagram-outline' },
}

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', orange: 'warning', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}
function toggleStyle(active: boolean) {
  if (active)
    return { background: 'rgb(var(--v-theme-primary))', color: 'rgb(var(--v-theme-on-primary))', borderColor: 'transparent' }
  return { background: 'transparent', color: 'rgba(var(--v-theme-on-surface), 0.75)', borderColor: 'rgba(var(--v-theme-on-surface), 0.2)' }
}

const router = useRouter()
const store = useApplicationsStore()
const filter = ref<ApplicationStatus | 'all'>('all')

const filtered = computed(() =>
  filter.value === 'all' ? store.applications : store.applications.filter(a => a.status === filter.value),
)

const stats = computed(() => [
  { title: 'إجمالي الطلبات', value: store.count, icon: 'mdi-file-send-outline', color: 'primary' },
  { title: 'قيد المراجعة', value: store.byStatus('reviewing').length, icon: 'mdi-file-search-outline', color: 'secondary' },
  { title: 'مقابلات', value: store.byStatus('interview').length, icon: 'mdi-calendar-check-outline', color: 'success' },
  { title: 'مقبولة', value: store.byStatus('accepted').length, icon: 'mdi-check-decagram-outline', color: 'accent' },
])

const filterChips: { value: ApplicationStatus | 'all', label: string }[] = [
  { value: 'all', label: 'الكل' },
  { value: 'submitted', label: 'تم التقديم' },
  { value: 'reviewing', label: 'قيد المراجعة' },
  { value: 'interview', label: 'مقابلة' },
  { value: 'accepted', label: 'مقبول' },
  { value: 'rejected', label: 'مرفوض' },
]
</script>

<template>
  <div>
    <PageHeader title="طلباتي" subtitle="تابع حالة تقديماتك على الفرص" icon="mdi-file-send-outline" />

    <div class="mb-2 grid grid-cols-2 gap-4 md:grid-cols-4">
      <StatCard v-for="s in stats" :key="s.title" v-bind="s" />
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="c in filterChips"
        :key="c.value"
        type="button"
        class="rounded-full border px-3 py-1 text-sm font-medium transition"
        :style="toggleStyle(filter === c.value)"
        @click="filter = c.value"
      >
        {{ c.label }}
      </button>
    </div>

    <BaseCard v-if="filtered.length" :padded="false" class="overflow-hidden">
      <div v-for="(app, i) in filtered" :key="app.id" class="flex items-center gap-3 p-3" :class="{ 'border-t border-ui': i > 0 }">
        <BaseAvatar :color="mapColor(statusMeta[app.status].color)" tonal square>
          <BaseIcon :name="statusMeta[app.status].icon" :size="20" />
        </BaseAvatar>
        <div class="min-w-0 flex-1">
          <button type="button" class="font-bold text-content hover:underline" @click="router.push({ name: 'opportunity-details', params: { id: app.opportunityId } })">
            {{ app.title }}
          </button>
          <div class="truncate text-xs text-muted">
            {{ app.company }} · قُدّم {{ app.appliedAt }} · بـ «{{ app.resume }}»
          </div>
        </div>
        <BaseChip :color="mapColor(statusMeta[app.status].color)">{{ statusMeta[app.status].label }}</BaseChip>
        <BaseDropdown>
          <template #trigger="{ toggle }">
            <button class="icon-btn h-9 w-9" aria-label="خيارات" @click="toggle"><BaseIcon name="mdi-dots-vertical" :size="18" /></button>
          </template>
          <div class="min-w-[170px] py-1">
            <button class="menu-row" @click="router.push({ name: 'opportunity-details', params: { id: app.opportunityId } })"><BaseIcon name="mdi-eye-outline" :size="18" />عرض الفرصة</button>
            <button class="menu-row" style="color: rgb(var(--v-theme-error))" @click="store.withdraw(app.id)"><BaseIcon name="mdi-delete-outline" :size="18" />سحب الطلب</button>
          </div>
        </BaseDropdown>
      </div>
    </BaseCard>

    <BaseCard v-else :padded="false">
      <EmptyState
        icon="mdi-file-search-outline"
        title="لا توجد طلبات بهذه الحالة"
        description="تقدّم على الفرص المناسبة وتابع حالتها من هنا."
        action-text="تصفّح الفرص"
        action-icon="mdi-briefcase-search-outline"
        @action="router.push({ name: 'opportunities' })"
      />
    </BaseCard>
  </div>
</template>
