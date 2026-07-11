<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'
import { type PipelineBoard, type PipelineCard, type PipelineOpportunity, type PipelineStageKey, type PipelineStats, api } from '@/services/api'
import { useAuthStore } from '@/stores/AuthStore'

const { t } = useI18n()
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('manage_pipeline'))

type StageColor = 'info' | 'accent' | 'warning' | 'brand' | 'success' | 'error'
const STAGE_META: { key: PipelineStageKey, color: StageColor, icon: string }[] = [
  { key: 'applied', color: 'info', icon: 'mdi-inbox-arrow-down-outline' },
  { key: 'screening', color: 'accent', icon: 'mdi-file-search-outline' },
  { key: 'interview', color: 'warning', icon: 'mdi-account-voice' },
  { key: 'offer', color: 'brand', icon: 'mdi-handshake-outline' },
  { key: 'hired', color: 'success', icon: 'mdi-check-decagram-outline' },
  { key: 'rejected', color: 'error', icon: 'mdi-close-circle-outline' },
]
const stageLabel = (k: string) => t(`admin.pipeline.stage_${k}`)
const stageColor = (k: string) => STAGE_META.find(s => s.key === k)?.color ?? 'neutral'

const snack = ref({ show: false, text: '', color: 'success' })
function toast(text: string, color = 'success') { snack.value = { show: true, text, color } }
function fail(e: unknown) { toast((e as { message?: string })?.message ?? t('admin.toast.failed'), 'error') }

const opportunities = ref<PipelineOpportunity[]>([])
const selectedOpp = ref<number | null>(null)
const board = ref<PipelineBoard | null>(null)
const stats = ref<PipelineStats | null>(null)

const oppItems = computed(() => [
  { value: 0, title: t('admin.pipeline.allOpportunities') },
  ...opportunities.value.map(o => ({ value: o.id, title: `${o.title}${o.company ? ` — ${o.company}` : ''} (${o.applications})` })),
])

async function loadOpportunities() {
  try { opportunities.value = await api.admin.pipelineOpportunities() }
  catch { /* تجاهل */ }
}
async function loadBoard() {
  try {
    const opp = selectedOpp.value || undefined
    board.value = await api.admin.pipelineBoard(opp)
    stats.value = await api.admin.pipelineStats(opp)
  }
  catch (e) { fail(e) }
}
function onOppChange(v: number | null) { selectedOpp.value = v || null; loadBoard() }

async function move(card: PipelineCard, toStage: string) {
  if (toStage === card.stage)
    return
  try {
    await api.admin.movePipeline(card.id, toStage)
    toast(t('admin.pipeline.moved', { name: card.candidate, stage: stageLabel(toStage) }))
    loadBoard()
  }
  catch (e) { fail(e) }
}

const moveItems = computed(() => STAGE_META.map(s => ({ value: s.key, title: stageLabel(s.key) })))
function fmtDate(s: string | null) { return s ? new Date(s).toLocaleDateString() : '—' }

onMounted(() => { loadOpportunities(); loadBoard() })
</script>

<template>
  <div>
    <PageHeader :title="t('admin.pipeline.title')" :subtitle="t('admin.pipeline.subtitle')" icon="mdi-sitemap-outline">
      <template #actions>
        <div class="w-72"><BaseSelect :model-value="selectedOpp ?? 0" :items="oppItems" @update:model-value="v => onOppChange(v as number)" /></div>
      </template>
    </PageHeader>

    <!-- إحصاءات خطّ الأنابيب -->
    <div v-if="stats" class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5">
      <StatCard icon="mdi-account-multiple-outline" :value="stats.total" :title="t('admin.pipeline.total')" color="primary" />
      <StatCard icon="mdi-progress-clock" :value="stats.active" :title="t('admin.pipeline.active')" color="info" />
      <StatCard icon="mdi-check-decagram-outline" :value="stats.hired" :title="t('admin.pipeline.hired')" color="success" />
      <StatCard icon="mdi-close-circle-outline" :value="stats.rejected" :title="t('admin.pipeline.rejected')" color="error" />
      <StatCard icon="mdi-percent-outline" :value="`${stats.hireRate}%`" :title="t('admin.pipeline.hireRate')" color="accent" />
    </div>

    <!-- لوحة كانبان -->
    <div v-if="board" class="flex gap-3 overflow-x-auto pb-3">
      <div v-for="col in board.stages" :key="col.key" class="flex w-[260px] min-w-[260px] flex-col">
        <div class="mb-2 flex items-center justify-between rounded-ui px-3 py-2" :style="{ background: `rgba(var(--v-theme-${stageColor(col.key)}),0.12)` }">
          <div class="flex items-center gap-2">
            <BaseIcon :name="STAGE_META.find(s => s.key === col.key)?.icon || 'mdi-circle'" :size="16" :style="{ color: `rgb(var(--v-theme-${stageColor(col.key)}))` }" />
            <span class="text-sm font-bold text-content">{{ stageLabel(col.key) }}</span>
          </div>
          <span class="rounded-full px-2 py-0.5 text-xs font-bold" :style="{ background: `rgb(var(--v-theme-${stageColor(col.key)}))`, color: 'white' }">{{ col.count }}</span>
        </div>

        <div class="flex flex-1 flex-col gap-2">
          <BaseCard v-for="card in col.items" :key="card.id" class="!p-2.5">
            <div class="mb-1 flex items-center gap-1.5">
              <BaseIcon name="mdi-account-circle-outline" :size="18" class="text-brand" />
              <span class="truncate text-sm font-medium text-content">{{ card.candidate }}</span>
            </div>
            <div class="mb-1 truncate text-xs text-muted">
              <BaseIcon name="mdi-briefcase-outline" :size="12" /> {{ card.opportunity }}
            </div>
            <div class="mb-2 text-[10px] text-muted">{{ t('admin.pipeline.applied') }} {{ fmtDate(card.appliedAt) }}</div>
            <BaseSelect v-if="canManage" :model-value="card.stage" :items="moveItems" @update:model-value="v => move(card, v as string)" />
          </BaseCard>
          <p v-if="!col.items.length" class="rounded-ui border border-dashed border-ui py-6 text-center text-xs text-muted">{{ t('admin.pipeline.emptyStage') }}</p>
        </div>
      </div>
    </div>

    <BaseSnackbar v-model="snack.show" :color="snack.color">{{ snack.text }}</BaseSnackbar>
  </div>
</template>
