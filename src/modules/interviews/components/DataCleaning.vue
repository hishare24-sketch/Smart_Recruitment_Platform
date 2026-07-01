<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AdaptiveQuestion, DirtyDataPayload } from '@/services/ai'

const props = defineProps<{ question: AdaptiveQuestion }>()
const answer = defineModel<string>('answer', { default: '' })
const payload = computed(() => props.question.payload as DirtyDataPayload)

const ACTIONS = [
  { key: 'dedupe', label: 'إزالة الصفوف المكرّرة' },
  { key: 'neg', label: 'معالجة القيم السالبة' },
  { key: 'outlier', label: 'حذف القيم الشاذة (عمر > 100)' },
  { key: 'dates', label: 'توحيد تنسيق التواريخ' },
  { key: 'fill', label: 'تعبئة القيم الناقصة (متوسط)' },
]
const selected = ref<string[]>([])
const note = ref('')
const previewed = ref(false)

function toggle(k: string) {
  selected.value = selected.value.includes(k) ? selected.value.filter(x => x !== k) : [...selected.value, k]
  previewed.value = false
}

// Simulated cleaned dataset
const cleanedRows = computed(() => {
  let rows = payload.value.rows.map(r => [...r])
  if (selected.value.includes('dedupe')) {
    const seen = new Set<string>()
    rows = rows.filter((r) => { const k = r.join('|'); if (seen.has(k)) return false; seen.add(k); return true })
  }
  if (selected.value.includes('neg'))
    rows = rows.filter(r => !(Number(r[1]) < 0))
  if (selected.value.includes('outlier'))
    rows = rows.filter(r => !(Number(r[1]) > 100))
  return rows
})

watch([selected, note], () => {
  const actions = ACTIONS.filter(a => selected.value.includes(a.key)).map(a => a.label)
  answer.value = actions.length
    ? `خطوات التنظيف: ${actions.join('، ')}. الأثر على التحليل: ${note.value || 'تحسين دقة النتائج ومنع الانحياز.'}`
    : note.value
})
</script>

<template>
  <div>
    <!-- Dirty table -->
    <div class="text-caption font-weight-bold mb-1">الجدول الأصلي (به مشاكل):</div>
    <VTable density="compact" class="border rounded mb-2">
      <thead><tr><th v-for="c in payload.columns" :key="c" class="text-caption font-weight-bold">{{ c }}</th></tr></thead>
      <tbody>
        <tr v-for="(r, ri) in payload.rows" :key="ri">
          <td v-for="(cell, ci) in r" :key="ci" class="text-caption" :class="{ 'bad-cell': (ci === 1 && (Number(cell) < 0 || Number(cell) > 100)) || cell === '' }">
            {{ cell || '—' }}
          </td>
        </tr>
      </tbody>
    </VTable>
    <div class="d-flex flex-wrap ga-1 mb-3">
      <VChip v-for="iss in payload.issues" :key="iss" size="x-small" color="warning" variant="tonal" prepend-icon="mdi-alert-outline">{{ iss }}</VChip>
    </div>

    <!-- Cleaning actions -->
    <div class="text-caption font-weight-bold mb-2">اختر خطوات التنظيف:</div>
    <div class="d-flex flex-wrap ga-1 mb-3">
      <VChip
        v-for="a in ACTIONS"
        :key="a.key"
        :color="selected.includes(a.key) ? 'success' : undefined"
        :variant="selected.includes(a.key) ? 'flat' : 'outlined'"
        size="small"
        @click="toggle(a.key)"
      >
        <VIcon v-if="selected.includes(a.key)" icon="mdi-check" size="14" start />{{ a.label }}
      </VChip>
    </div>

    <VBtn size="small" color="primary" variant="tonal" prepend-icon="mdi-table-check" class="mb-2" :disabled="!selected.length" @click="previewed = true">
      معاينة بعد التنظيف
    </VBtn>

    <VExpandTransition>
      <div v-if="previewed">
        <div class="text-caption font-weight-bold mb-1 text-success">الجدول بعد التنظيف ({{ cleanedRows.length }} صف):</div>
        <VTable density="compact" class="border rounded mb-3">
          <thead><tr><th v-for="c in payload.columns" :key="c" class="text-caption font-weight-bold">{{ c }}</th></tr></thead>
          <tbody>
            <tr v-for="(r, ri) in cleanedRows" :key="ri">
              <td v-for="(cell, ci) in r" :key="ci" class="text-caption">{{ cell || '—' }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VExpandTransition>

    <VTextarea v-model="note" label="أثر هذا التنظيف على التحليل النهائي" rows="2" auto-grow />
  </div>
</template>

<style scoped>
.bad-cell {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
  font-weight: 700;
}
</style>
