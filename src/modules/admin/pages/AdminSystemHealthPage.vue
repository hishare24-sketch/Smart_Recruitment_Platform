<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import LineChart from '@/components/charts/LineChart.vue'
import { type SystemHealth, api } from '@/services/api'

const { t } = useI18n()

const health = ref<SystemHealth | null>(null)
const loading = ref(false)
async function load() {
  loading.value = true
  try { health.value = await api.admin.systemHealth() }
  catch { /* تجاهل */ }
  finally { loading.value = false }
}
onMounted(load)

type ChipColor = 'brand' | 'accent' | 'emerald' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
const STATUS_META: Record<string, { color: ChipColor, icon: string, label: string }> = {
  ok: { color: 'success', icon: 'mdi-check-circle', label: 'سليم' },
  warn: { color: 'warning', icon: 'mdi-alert-circle', label: 'تحذير' },
  down: { color: 'error', icon: 'mdi-close-circle', label: 'متوقّف' },
}
const SVC_ICON: Record<string, string> = {
  database: 'mdi-database-outline', cache: 'mdi-lightning-bolt-outline', queue: 'mdi-tray-full',
  broadcast: 'mdi-broadcast', ai_provider: 'mdi-robot-outline',
}
const overall = computed(() => STATUS_META[health.value?.overall ?? 'ok'])
const seriesData = computed(() => (health.value?.series ?? []).map(s => ({ label: s.date.slice(5), value: s.value })))
const errorSeries = computed(() => (health.value?.series ?? []).map(s => ({ label: s.date.slice(5), value: s.errors })))
const hasErrors = computed(() => errorSeries.value.some(s => s.value > 0))
function fmtTime(s: string | null) { return s ? new Date(s).toLocaleString() : '—' }
function statusColor(st: number): ChipColor { return st >= 500 ? 'error' : (st >= 400 ? 'warning' : 'neutral') }
</script>

<template>
  <div>
    <PageHeader :title="t('admin.health.title')" :subtitle="t('admin.health.subtitle')" icon="mdi-heart-pulse">
      <template #actions>
        <BaseButton variant="ghost" size="sm" :disabled="loading" @click="load"><BaseIcon name="mdi-refresh" :size="18" />{{ t('admin.health.refresh') }}</BaseButton>
      </template>
    </PageHeader>

    <!-- الحالة العامّة -->
    <BaseCard v-if="health" class="mb-4 !py-3" :style="{ borderInlineStart: `3px solid rgb(var(--v-theme-${overall.color}))` }">
      <div class="flex items-center gap-3">
        <BaseIcon :name="overall.icon" :size="28" :style="{ color: `rgb(var(--v-theme-${overall.color}))` }" />
        <div>
          <div class="font-bold text-content">{{ t('admin.health.overall') }}: {{ t(`admin.health.st_${health.overall}`) }}</div>
          <div class="text-xs text-muted">{{ health.metrics.laravel }} · PHP {{ health.metrics.php }} · {{ health.metrics.env }}<span v-if="health.metrics.debug"> · debug</span></div>
        </div>
      </div>
    </BaseCard>

    <!-- شبكة الخدمات -->
    <div v-if="health" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <BaseCard v-for="s in health.services" :key="s.key" :style="{ borderInlineStart: `3px solid rgb(var(--v-theme-${STATUS_META[s.status].color}))` }">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-2">
            <BaseIcon :name="SVC_ICON[s.key] || 'mdi-server'" :size="20" class="text-brand" />
            <span class="font-medium text-content">{{ s.label }}</span>
          </div>
          <BaseChip :color="STATUS_META[s.status].color">{{ t(`admin.health.st_${s.status}`) }}</BaseChip>
        </div>
        <div class="mt-1.5 text-xs text-muted">{{ s.detail }}</div>
        <div v-if="s.driver" class="mt-0.5 font-mono text-[10px] text-muted" dir="ltr">{{ s.driver }}</div>
      </BaseCard>
    </div>

    <!-- المقاييس -->
    <div v-if="health" class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5">
      <StatCard icon="mdi-account-multiple-outline" :value="health.metrics.users" :title="t('admin.health.mUsers')" color="primary" />
      <StatCard icon="mdi-tray-full" :value="health.metrics.pendingJobs" :title="t('admin.health.mPending')" color="info" />
      <StatCard icon="mdi-alert-octagon-outline" :value="health.metrics.failedJobs" :title="t('admin.health.mFailed')" :color="health.metrics.failedJobs ? 'error' : 'neutral'" />
      <StatCard icon="mdi-swap-horizontal" :value="health.metrics.requestsToday" :title="t('admin.health.mRequests')" color="accent" />
      <StatCard icon="mdi-bug-outline" :value="health.metrics.errorsToday" :title="t('admin.health.mErrors')" :color="health.metrics.errorsToday ? 'warning' : 'success'" />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <!-- سلسلة النشاط/الأخطاء -->
      <BaseCard v-if="health">
        <div class="mb-2 flex items-center gap-2"><BaseIcon name="mdi-chart-line" :size="18" class="text-brand" /><h2 class="text-sm font-bold text-content">{{ t('admin.health.activity') }}</h2></div>
        <LineChart v-if="seriesData.length" :data="seriesData" color="primary" :height="150" />
        <div v-if="hasErrors" class="mt-2">
          <div class="mb-1 text-xs text-muted">{{ t('admin.health.errorsTrend') }}</div>
          <LineChart :data="errorSeries" color="error" :height="90" />
        </div>
      </BaseCard>

      <!-- آخر الأخطاء -->
      <BaseCard v-if="health">
        <div class="mb-2 flex items-center gap-2"><BaseIcon name="mdi-alert-outline" :size="18" class="text-brand" /><h2 class="text-sm font-bold text-content">{{ t('admin.health.recentErrors') }}</h2></div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="(e, i) in health.recentErrors" :key="i" class="border-b border-ui">
                <td class="p-1.5"><BaseChip :color="statusColor(e.status)">{{ e.status }}</BaseChip></td>
                <td class="p-1.5 text-content">{{ e.action }} <span class="text-muted">/ {{ e.resource || '—' }}</span></td>
                <td class="p-1.5 text-xs text-muted">{{ e.actor || '—' }}</td>
                <td class="whitespace-nowrap p-1.5 text-xs text-muted">{{ fmtTime(e.at) }}</td>
              </tr>
              <tr v-if="!health.recentErrors.length"><td class="p-6 text-center text-sm text-muted">{{ t('admin.health.noErrors') }}</td></tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
