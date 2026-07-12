<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import LineChart from '@/components/charts/LineChart.vue'
import ResourceScaffold from '@/modules/admin/components/ResourceScaffold.vue'
import type { FilterDef } from '@/modules/admin/components/ResourceScaffold.vue'
import type { TableColumn } from '@/components/ui/BaseTable.vue'
import { useAdminResource } from '@/modules/admin/composables/useAdminResource'
import { type QualityAtom, type QualityOverview, api } from '@/services/api'

const { t } = useI18n()

type ChipColor = 'brand' | 'accent' | 'emerald' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
const STATUS_COLOR: Record<string, ChipColor> = { automated: 'success', gap: 'warning', failing: 'error' }
const STATUS_ICON: Record<string, string> = { automated: 'mdi-check-circle-outline', gap: 'mdi-checkbox-blank-outline', failing: 'mdi-close-circle-outline' }
const PRIORITY_COLOR: Record<string, ChipColor> = { critical: 'error', important: 'warning', normal: 'neutral' }
const LAYER_COLOR: Record<string, ChipColor> = { backend: 'info', frontend: 'brand', ops: 'accent', filters: 'emerald' }
const STATUS_THEME: Record<string, string> = { automated: 'success', gap: 'warning', failing: 'error' }
const statusLabel = (s: string) => t(`admin.qcc.status_${s}`)
const priorityLabel = (p: string) => t(`admin.qcc.priority_${p}`)
const layerLabel = (l: string) => t(`admin.qcc.layer_${l}`)
const typeLabel = (t2: string) => t(`admin.qcc.type_${t2}`)

// ——— النظرة العامّة (KPIs + توزيعات + اتّجاه) ———
const overview = ref<QualityOverview | null>(null)
const loadingOverview = ref(false)
async function loadOverview() {
  loadingOverview.value = true
  try { overview.value = await api.admin.qualityOverview() }
  catch { /* تجاهل */ }
  finally { loadingOverview.value = false }
}

const statusDonut = computed(() => (overview.value?.byStatus ?? []).map(s => ({
  label: statusLabel(s.key), value: s.count, color: `rgb(var(--v-theme-${STATUS_THEME[s.key] ?? 'neutral'}))`,
})))
const coverageSeries = computed(() => (overview.value?.series ?? []).map(s => ({ label: s.date.slice(5), value: s.coverage })))
const maxLayer = computed(() => Math.max(1, ...(overview.value?.byLayer ?? []).map(l => l.count)))

// ——— مستكشف الذرّات (خادميّ التقسيم) ———
const r = useAdminResource<QualityAtom>({ fetcher: params => api.admin.qualityAtoms(params), initialSort: 'caseId', perPage: 20 })
const { items, meta, loading, sortKey, search, filters } = r

function refreshAll() { r.refresh(); loadOverview() }
onMounted(loadOverview)

const columns: TableColumn[] = [
  { key: 'caseId', label: t('admin.qcc.colId'), sortable: true },
  { key: 'title', label: t('admin.qcc.colTitle'), sortable: false },
  { key: 'layer', label: t('admin.qcc.colLayer'), sortable: true, align: 'center' },
  { key: 'type', label: t('admin.qcc.colType'), sortable: true, align: 'center' },
  { key: 'priority', label: t('admin.qcc.colPriority'), sortable: true, align: 'center' },
  { key: 'status', label: t('admin.qcc.colStatus'), sortable: true, align: 'center' },
  { key: 'testFile', label: t('admin.qcc.colTest'), sortable: false },
]
const filterDefs = computed<FilterDef[]>(() => [
  { key: 'layer', label: t('admin.qcc.colLayer'), options: ['backend', 'frontend', 'ops', 'filters'].map(l => ({ value: l, label: layerLabel(l) })) },
  { key: 'status', label: t('admin.qcc.colStatus'), options: ['automated', 'gap', 'failing'].map(s => ({ value: s, label: statusLabel(s) })) },
  { key: 'priority', label: t('admin.qcc.colPriority'), options: ['critical', 'important', 'normal'].map(p => ({ value: p, label: priorityLabel(p) })) },
  { key: 'type', label: t('admin.qcc.colType'), options: ['U', 'F', 'E'].map(x => ({ value: x, label: typeLabel(x) })) },
])
</script>

<template>
  <div>
    <PageHeader :title="t('admin.qcc.title')" :subtitle="t('admin.qcc.subtitle')" icon="mdi-shield-star-outline">
      <template #actions>
        <BaseButton variant="ghost" size="sm" :disabled="loading || loadingOverview" @click="refreshAll">
          <BaseIcon name="mdi-refresh" :size="18" />{{ t('admin.qcc.refresh') }}
        </BaseButton>
      </template>
    </PageHeader>

    <!-- بطاقات المؤشّرات -->
    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
      <StatCard icon="mdi-atom" :value="overview?.total ?? 0" :title="t('admin.qcc.statTotal')" color="primary" />
      <StatCard icon="mdi-check-decagram-outline" :value="`${overview?.coverage ?? 0}%`" :title="t('admin.qcc.statCoverage')" color="success" />
      <StatCard icon="mdi-check-circle-outline" :value="overview?.automated ?? 0" :title="t('admin.qcc.statAutomated')" color="emerald" />
      <StatCard icon="mdi-checkbox-blank-outline" :value="overview?.gap ?? 0" :title="t('admin.qcc.statGaps')" color="warning" />
      <StatCard icon="mdi-fire" :value="overview?.criticalGaps ?? 0" :title="t('admin.qcc.statCriticalGaps')" color="error" />
      <StatCard icon="mdi-close-circle-outline" :value="overview?.failing ?? 0" :title="t('admin.qcc.statFailing')" color="error" />
    </div>

    <!-- التوزيعات والاتّجاه -->
    <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
      <!-- دونات الحالة -->
      <BaseCard>
        <div class="mb-2 flex items-center gap-2">
          <BaseIcon name="mdi-chart-donut" :size="18" class="text-brand" />
          <h2 class="text-sm font-bold text-content">{{ t('admin.qcc.byStatus') }}</h2>
        </div>
        <DonutChart v-if="statusDonut.length" :data="statusDonut" :size="160" :center-label="t('admin.qcc.statTotal')" />
        <p v-else class="py-8 text-center text-xs text-muted">—</p>
      </BaseCard>

      <!-- اتّجاه التغطية -->
      <BaseCard class="lg:col-span-2">
        <div class="mb-2 flex items-center gap-2">
          <BaseIcon name="mdi-trending-up" :size="18" class="text-brand" />
          <h2 class="text-sm font-bold text-content">{{ t('admin.qcc.coverageTrend') }}</h2>
          <span class="text-xs text-muted">· {{ t('admin.qcc.statCoverage') }}: <b class="text-content">{{ overview?.coverage ?? 0 }}%</b></span>
        </div>
        <LineChart v-if="coverageSeries.length > 1" :data="coverageSeries" color="success" :height="180" />
        <p v-else class="py-10 text-center text-xs text-muted">{{ t('admin.qcc.trendSoon') }}</p>
      </BaseCard>
    </div>

    <!-- الطبقات + أعلى الأقسام فجوةً -->
    <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
      <BaseCard>
        <div class="mb-3 flex items-center gap-2">
          <BaseIcon name="mdi-layers-outline" :size="18" class="text-brand" />
          <h2 class="text-sm font-bold text-content">{{ t('admin.qcc.byLayer') }}</h2>
        </div>
        <div class="space-y-2.5">
          <div v-for="l in overview?.byLayer ?? []" :key="l.key" class="flex items-center gap-3">
            <BaseChip :color="LAYER_COLOR[l.key] || 'neutral'" class="w-24 justify-center">{{ layerLabel(l.key) }}</BaseChip>
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-ui">
              <div class="h-full rounded-full" :style="{ width: `${(l.count / maxLayer) * 100}%`, background: `rgb(var(--v-theme-${LAYER_COLOR[l.key] || 'neutral'}))` }" />
            </div>
            <span class="w-10 text-end font-mono text-xs text-content">{{ l.count }}</span>
          </div>
          <p v-if="!(overview?.byLayer?.length)" class="py-4 text-center text-xs text-muted">—</p>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="mb-3 flex items-center gap-2">
          <BaseIcon name="mdi-alert-decagram-outline" :size="18" class="text-brand" />
          <h2 class="text-sm font-bold text-content">{{ t('admin.qcc.topGaps') }}</h2>
        </div>
        <div class="space-y-1.5">
          <div v-for="g in overview?.topGapSections ?? []" :key="`${g.layer}-${g.section}`" class="flex items-center gap-2 text-sm">
            <BaseChip :color="LAYER_COLOR[g.layer] || 'neutral'" class="shrink-0">{{ layerLabel(g.layer) }}</BaseChip>
            <span class="flex-1 truncate text-content" :title="g.section">{{ g.section }}</span>
            <BaseChip color="warning">{{ g.gaps }}</BaseChip>
          </div>
          <p v-if="!(overview?.topGapSections?.length)" class="py-4 text-center text-xs text-muted">—</p>
        </div>
      </BaseCard>
    </div>

    <!-- مستكشف الذرّات -->
    <ResourceScaffold
      :columns="columns"
      :items="items"
      :loading="loading"
      :meta="meta"
      :sort-key="sortKey"
      :search="search"
      :filters="filterDefs"
      :active-filters="filters"
      :search-placeholder="t('admin.qcc.searchPlaceholder')"
      export-name="quality-atoms"
      @update:sort-key="r.setSort"
      @update:search="r.setSearch"
      @filter="r.setFilter"
      @update:page="r.setPage"
      @update:per-page="r.setPerPage"
    >
      <template #cell-caseId="{ row }">
        <span class="font-mono text-xs font-medium text-content">{{ row.caseId }}</span>
      </template>
      <template #cell-title="{ row }">
        <div class="max-w-md">
          <div class="truncate text-content" :title="row.title">{{ row.title }}</div>
          <div class="truncate text-[11px] text-muted" :title="row.section">{{ row.section }}</div>
        </div>
      </template>
      <template #cell-layer="{ row }">
        <BaseChip :color="LAYER_COLOR[row.layer] || 'neutral'">{{ layerLabel(row.layer) }}</BaseChip>
      </template>
      <template #cell-type="{ row }">
        <BaseChip v-if="row.type" color="neutral">{{ row.type }}</BaseChip>
        <span v-else class="text-muted">—</span>
      </template>
      <template #cell-priority="{ row }">
        <BaseChip :color="PRIORITY_COLOR[row.priority] || 'neutral'">{{ priorityLabel(row.priority) }}</BaseChip>
      </template>
      <template #cell-status="{ row }">
        <BaseChip :color="STATUS_COLOR[row.status] || 'neutral'">
          <BaseIcon :name="STATUS_ICON[row.status]" :size="12" />{{ statusLabel(row.status) }}
        </BaseChip>
      </template>
      <template #cell-testFile="{ row }">
        <span v-if="row.testFile" class="font-mono text-[11px] text-muted" dir="ltr">{{ row.testFile }}</span>
        <span v-else class="text-muted">—</span>
      </template>
    </ResourceScaffold>
  </div>
</template>
