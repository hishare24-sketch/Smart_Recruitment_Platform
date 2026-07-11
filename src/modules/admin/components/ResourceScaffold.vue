<script setup lang="ts" generic="T extends Record<string, any>">
import { computed } from 'vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import type { TableColumn } from '@/components/ui/BaseTable.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseTooltip from '@/components/ui/BaseTooltip.vue'
import type { PageMeta } from '@/services/api'

export interface FilterDef {
  key: string
  label: string
  options: { value: string, label: string }[]
}

const props = withDefaults(defineProps<{
  columns: TableColumn[]
  items: T[]
  loading?: boolean
  meta?: PageMeta | null
  sortKey?: string
  selected?: (string | number)[]
  search?: string
  filters?: FilterDef[]
  activeFilters?: Record<string, string>
  selectable?: boolean
  rowKey?: (row: T) => string | number
  searchPlaceholder?: string
  /** تصدير CSV للصفحة الحاليّة */
  exportable?: boolean
  exportName?: string
  /** النقر على صفّ يفتح الاستعراض (rowClick) */
  inspectable?: boolean
}>(), {
  loading: false,
  meta: null,
  sortKey: '',
  selected: () => [],
  search: '',
  filters: () => [],
  activeFilters: () => ({}),
  selectable: false,
  exportable: true,
  exportName: 'export',
})

const emit = defineEmits<{
  'update:sortKey': [v: string]
  'update:selected': [v: (string | number)[]]
  'update:search': [v: string]
  'filter': [key: string, value: string | undefined]
  'update:page': [n: number]
  'update:perPage': [n: number]
  'rowClick': [row: T]
}>()

const hasSelection = computed(() => props.selectable && props.selected.length > 0)

function filterItems(f: FilterDef) {
  return [{ value: '', title: 'الكل' }, ...f.options.map(o => ({ value: o.value, title: o.label }))]
}

// رقائق الفلاتر المطبّقة — إزالة فرديّة
const activeChips = computed(() => {
  const chips: { key: string, label: string }[] = []
  if (props.search.trim())
    chips.push({ key: '__q', label: `“${props.search.trim()}”` })
  for (const [k, v] of Object.entries(props.activeFilters)) {
    if (!v)
      continue
    const f = props.filters.find(x => x.key === k)
    const opt = f?.options.find(o => o.value === v)
    chips.push({ key: k, label: `${f?.label ?? k}: ${opt?.label ?? v}` })
  }
  return chips
})
function removeChip(key: string) {
  if (key === '__q')
    emit('update:search', '')
  else
    emit('filter', key, undefined)
}
function clearAll() {
  emit('update:search', '')
  for (const k of Object.keys(props.activeFilters)) emit('filter', k, undefined)
}

// تصدير CSV من الصفحة الحاليّة
function csvCell(v: unknown): string {
  const s = v == null ? '' : Array.isArray(v) ? v.join(' / ') : String(v)
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s
}
function exportCsv() {
  const header = props.columns.map(c => c.label).join(',')
  const rows = props.items.map(row => props.columns.map(c => csvCell(row[c.key])).join(','))
  const csv = [header, ...rows].join('\n')
  const blob = new Blob([`﻿${csv}`], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.exportName}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="space-y-3">
    <!-- شريط الأدوات: بحث + فلاتر + عدّاد + تصدير -->
    <div class="flex flex-wrap items-center gap-2">
      <div class="min-w-[220px] flex-1">
        <BaseInput
          :model-value="search"
          :placeholder="searchPlaceholder ?? 'بحث...'"
          prefix-icon="mdi-magnify"
          @update:model-value="v => emit('update:search', String(v ?? ''))"
        />
      </div>
      <BaseSelect
        v-for="f in filters"
        :key="f.key"
        :model-value="activeFilters[f.key] ?? ''"
        :items="filterItems(f)"
        :placeholder="f.label"
        class="min-w-[150px]"
        @update:model-value="v => emit('filter', f.key, (v as string) || undefined)"
      />
      <slot name="toolbar" />
      <span v-if="meta" class="ms-auto whitespace-nowrap text-sm text-muted">{{ meta.total }} نتيجة</span>
      <BaseTooltip v-if="exportable" text="تصدير CSV">
        <button class="scaf-icon" aria-label="تصدير CSV" :disabled="!items.length" @click="exportCsv">
          <BaseIcon name="mdi-download-outline" :size="18" />
        </button>
      </BaseTooltip>
    </div>

    <!-- رقائق الفلاتر المطبّقة -->
    <div v-if="activeChips.length" class="flex flex-wrap items-center gap-1.5">
      <span class="text-xs text-muted">المطبَّق:</span>
      <button v-for="c in activeChips" :key="c.key" class="scaf-chip" @click="removeChip(c.key)">
        {{ c.label }}<BaseIcon name="mdi-close" :size="13" />
      </button>
      <button class="text-xs text-brand hover:underline" @click="clearAll">مسح الكل</button>
    </div>

    <!-- شريط الإجراءات الجماعيّة -->
    <div v-if="hasSelection" class="flex flex-wrap items-center gap-2 rounded-ui border-ui bg-brand/[0.06] px-3 py-2">
      <BaseIcon name="mdi-checkbox-multiple-marked-outline" :size="18" class="text-brand" />
      <span class="text-sm font-bold text-content">{{ selected.length }} مُختار</span>
      <span class="flex-1" />
      <slot name="bulk" :selected="selected" :clear="() => emit('update:selected', [])" />
      <BaseButton size="sm" variant="ghost" @click="emit('update:selected', [])">
        <BaseIcon name="mdi-close" :size="16" />إلغاء التحديد
      </BaseButton>
    </div>

    <!-- الجدول -->
    <BaseTable
      :columns="columns"
      :rows="items"
      :loading="loading"
      :selectable="selectable"
      :row-key="rowKey"
      :sort-key="sortKey"
      :selected="selected"
      :class="inspectable ? 'cursor-pointer' : ''"
      @update:sort-key="v => emit('update:sortKey', v)"
      @update:selected="v => emit('update:selected', v)"
      @row-click="row => inspectable && emit('rowClick', row)"
    >
      <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps ?? {}" />
      </template>
    </BaseTable>

    <!-- الترقيم -->
    <BasePagination
      v-if="meta && meta.last_page > 1"
      :page="meta.current_page"
      :last-page="meta.last_page"
      :total="meta.total"
      :per-page="meta.itemPerPage"
      @update:page="n => emit('update:page', n)"
      @update:per-page="n => emit('update:perPage', n)"
    />
  </div>
</template>

<style scoped>
.scaf-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  color: rgba(var(--v-theme-on-surface), 0.7);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.14);
  transition: background-color 0.15s ease;
}
.scaf-icon:hover:not(:disabled) {
  background: rgba(var(--v-theme-on-surface), 0.06);
}
.scaf-icon:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.scaf-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.12);
  transition: background-color 0.15s ease;
}
.scaf-chip:hover {
  background: rgba(var(--v-theme-primary), 0.2);
}
</style>
