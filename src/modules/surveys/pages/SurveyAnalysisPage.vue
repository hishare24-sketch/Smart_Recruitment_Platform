<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import { QUESTION_TYPE_META, useSurveysStore } from '@/stores/SurveysStore'
import type { SurveyQuestion } from '@/stores/SurveysStore'
import { ai } from '@/services/ai'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'
import BaseRating from '@/components/ui/BaseRating.vue'

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', orange: 'warning', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}
function colorVar(c: string) {
  return `rgb(var(--v-theme-${c === 'amber' ? 'warning' : c}))`
}

const route = useRoute()
const router = useRouter()
const store = useSurveysStore()

const survey = computed(() => store.byId(Number(route.params.id)))
const responses = computed(() => store.responsesFor(Number(route.params.id)))
const stats = computed(() => store.statsFor(Number(route.params.id)))

// ===== Aggregations per question type =====
function answersOf(q: SurveyQuestion) {
  return responses.value.map(r => r.answers[q.id]).filter(v => v !== undefined && v !== '')
}

function optionCounts(q: SurveyQuestion): { label: string, count: number, pct: number }[] {
  const counts = new Map<string, number>()
  for (const v of answersOf(q)) {
    const picks = Array.isArray(v) && q.type === 'multiple' ? v : [v]
    for (const p of picks as string[])
      counts.set(String(p), (counts.get(String(p)) ?? 0) + 1)
  }
  const total = Math.max(1, [...counts.values()].reduce((s, n) => s + n, 0))
  return (q.options ?? [...counts.keys()]).map(label => ({
    label,
    count: counts.get(label) ?? 0,
    pct: Math.round(((counts.get(label) ?? 0) / total) * 100),
  }))
}

function numericAvg(q: SurveyQuestion): number {
  const vals = answersOf(q).map(Number).filter(n => !Number.isNaN(n))
  return vals.length ? Math.round((vals.reduce((s, n) => s + n, 0) / vals.length) * 10) / 10 : 0
}

function distribution(q: SurveyQuestion, min: number, max: number): { label: string, count: number, pct: number }[] {
  const vals = answersOf(q).map(Number).filter(n => !Number.isNaN(n))
  const out = []
  for (let n = min; n <= max; n++) {
    const count = vals.filter(v => v === n).length
    out.push({ label: String(n), count, pct: vals.length ? Math.round((count / vals.length) * 100) : 0 })
  }
  return out
}

interface NpsBreakdown { score: number, promoters: number, passives: number, detractors: number }
function npsOf(q: SurveyQuestion): NpsBreakdown {
  const vals = answersOf(q).map(Number).filter(n => !Number.isNaN(n))
  if (!vals.length)
    return { score: 0, promoters: 0, passives: 0, detractors: 0 }
  const promoters = Math.round((vals.filter(v => v >= 9).length / vals.length) * 100)
  const passives = Math.round((vals.filter(v => v >= 7 && v <= 8).length / vals.length) * 100)
  const detractors = Math.round((vals.filter(v => v <= 6).length / vals.length) * 100)
  return { score: promoters - detractors, promoters, passives, detractors }
}

function matrixAverages(q: SurveyQuestion): { row: string, avg: number }[] {
  return (q.rows ?? []).map((row) => {
    const vals = answersOf(q)
      .map(v => (v as Record<string, number>)[row])
      .filter(n => typeof n === 'number')
    return { row, avg: vals.length ? Math.round((vals.reduce((s, n) => s + n, 0) / vals.length) * 10) / 10 : 0 }
  })
}

function rankingAverages(q: SurveyQuestion): { label: string, avg: number }[] {
  const positions = new Map<string, number[]>()
  for (const v of answersOf(q)) {
    (v as string[]).forEach((opt, i) => {
      positions.set(opt, [...(positions.get(opt) ?? []), i + 1])
    })
  }
  return [...positions.entries()]
    .map(([label, ps]) => ({ label, avg: Math.round((ps.reduce((s, n) => s + n, 0) / ps.length) * 10) / 10 }))
    .sort((a, b) => a.avg - b.avg)
}

function textAnswers(q: SurveyQuestion): string[] {
  return answersOf(q).map(String).slice(-6).reverse()
}

// Response timeline (per day)
const timeline = computed(() => {
  const byDay = new Map<string, number>()
  for (const r of responses.value)
    byDay.set(r.at, (byDay.get(r.at) ?? 0) + 1)
  const days = [...byDay.entries()].sort((a, b) => a[0].localeCompare(b[0]))
  const max = Math.max(1, ...days.map(d => d[1]))
  return days.map(([day, count]) => ({ day: day.slice(5), count, pct: Math.round((count / max) * 100) }))
})

// ===== AI insights =====
const firstRating = computed(() => survey.value?.questions.find(q => q.type === 'rating'))
const firstNps = computed(() => survey.value?.questions.find(q => q.type === 'nps'))
const insights = computed(() => {
  if (!survey.value)
    return null
  const texts = survey.value.questions
    .filter(q => q.type === 'text' || q.type === 'longtext')
    .flatMap(q => answersOf(q).map(String))
  return ai.surveyInsights({
    title: survey.value.title,
    responses: stats.value.responses,
    completion: stats.value.completion,
    nps: firstNps.value ? npsOf(firstNps.value).score : null,
    avgRating: firstRating.value ? numericAvg(firstRating.value) : null,
    textAnswers: texts,
  })
})
const sentimentColor = computed(() => ({ positive: 'success', neutral: 'warning', negative: 'error' })[insights.value?.sentiment ?? 'neutral'])

function printPage() {
  window.print()
}

// ===== Export =====
function exportCsv() {
  if (!survey.value)
    return
  const header = ['#', 'التاريخ', 'المصدر', ...survey.value.questions.map(q => q.text)]
  const rows = responses.value.map((r, i) => [
    i + 1,
    r.at,
    r.source === 'external' ? 'خارجي' : 'داخلي',
    ...survey.value!.questions.map((q) => {
      const v = r.answers[q.id]
      if (v === undefined)
        return ''
      if (Array.isArray(v))
        return v.join(' | ')
      if (typeof v === 'object')
        return Object.entries(v).map(([k, n]) => `${k}:${n}`).join(' | ')
      return String(v)
    }),
  ])
  const csv = `﻿${[header, ...rows].map(r => r.map(c => `"${String(c).replaceAll('"', '""')}"`).join(',')).join('\n')}`
  const a = document.createElement('a')
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  a.download = `${survey.value.title}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}
</script>

<template>
  <div v-if="survey">
    <PageHeader :title="`تحليل: ${survey.title}`" :subtitle="`${survey.type} · أُنشئ ${survey.createdAt}`" icon="mdi-chart-box-outline" />

    <!-- Actions -->
    <div class="mb-4 flex flex-wrap gap-2">
      <BaseButton variant="tonal-brand" size="sm" @click="exportCsv"><BaseIcon name="mdi-download-outline" :size="16" />تصدير CSV</BaseButton>
      <BaseButton variant="tonal-brand" size="sm" @click="printPage"><BaseIcon name="mdi-printer-outline" :size="16" />طباعة</BaseButton>
      <BaseButton variant="tonal-emerald" size="sm" @click="store.simulateResponses(survey.id, 4)"><BaseIcon name="mdi-account-multiple-plus-outline" :size="16" />محاكاة مستجيبين</BaseButton>
    </div>

    <!-- Overview stats -->
    <div class="mb-1 grid grid-cols-2 gap-4 md:grid-cols-4">
      <BaseCard v-for="s in [
        { title: 'الاستجابات', value: stats.responses, icon: 'mdi-account-group-outline', color: 'primary' },
        { title: 'نسبة الإكمال', value: `${stats.completion}%`, icon: 'mdi-progress-check', color: 'success' },
        { title: 'متوسط الوقت', value: stats.avgTime, icon: 'mdi-timer-outline', color: 'info' },
        { title: 'داخلي / خارجي', value: `${stats.internal} / ${stats.external}`, icon: 'mdi-swap-horizontal', color: 'secondary' },
      ]" :key="s.title" class="text-center">
        <BaseIcon :name="s.icon" :size="26" class="mb-1" :style="{ color: colorVar(s.color) }" />
        <div class="text-lg font-bold text-content">{{ s.value }}</div>
        <div class="text-xs text-muted">{{ s.title }}</div>
      </BaseCard>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
      <div class="lg:col-span-8">
        <!-- Per-question visualizations -->
        <BaseCard v-for="(q, qi) in survey.questions" :key="q.id" class="mb-4">
          <div class="mb-3 flex flex-wrap items-center gap-2">
            <BaseChip color="brand"><BaseIcon :name="QUESTION_TYPE_META[q.type].icon" :size="12" />{{ QUESTION_TYPE_META[q.type].label }}</BaseChip>
            <h3 class="text-base font-bold text-content">{{ qi + 1 }}. {{ q.text }}</h3>
          </div>

          <!-- choices -->
          <template v-if="q.type === 'single' || q.type === 'multiple' || q.type === 'dropdown'">
            <div v-for="o in optionCounts(q)" :key="o.label" class="mb-2">
              <div class="mb-1 flex justify-between text-sm text-content">
                <span>{{ o.label }}</span>
                <span class="font-bold">{{ o.count }} ({{ o.pct }}%)</span>
              </div>
              <BaseProgressBar :value="o.pct" color="primary" :height="10" />
            </div>
          </template>

          <!-- rating -->
          <template v-else-if="q.type === 'rating'">
            <div class="mb-3 flex items-center gap-3">
              <span class="text-3xl font-bold text-content">{{ numericAvg(q) }}</span>
              <BaseRating :model-value="numericAvg(q)" color="warning" :size="20" readonly />
              <span class="text-xs text-muted">متوسط التقييم</span>
            </div>
            <div v-for="d in distribution(q, 1, 5)" :key="d.label" class="mb-1 flex items-center gap-2">
              <span class="text-xs text-muted" style="width: 20px">{{ d.label }}★</span>
              <BaseProgressBar :value="d.pct" color="warning" :height="8" class="flex-1" />
              <span class="text-xs text-muted" style="width: 32px">{{ d.count }}</span>
            </div>
          </template>

          <!-- NPS -->
          <template v-else-if="q.type === 'nps'">
            <div class="mb-3 flex items-center gap-3">
              <span class="text-3xl font-bold" :style="{ color: colorVar(npsOf(q).score >= 30 ? 'success' : npsOf(q).score >= 0 ? 'warning' : 'error') }">
                {{ npsOf(q).score > 0 ? '+' : '' }}{{ npsOf(q).score }}
              </span>
              <span class="text-xs text-muted">صافي نقاط الترويج (NPS)</span>
            </div>
            <div class="mb-2 flex overflow-hidden rounded-lg" style="height: 18px">
              <div :style="{ width: `${npsOf(q).detractors}%`, background: 'rgb(var(--v-theme-error))' }" />
              <div :style="{ width: `${npsOf(q).passives}%`, background: 'rgb(var(--v-theme-warning))' }" />
              <div :style="{ width: `${npsOf(q).promoters}%`, background: 'rgb(var(--v-theme-success))' }" />
            </div>
            <div class="flex justify-between text-xs">
              <span :style="{ color: colorVar('error') }">منتقدون {{ npsOf(q).detractors }}%</span>
              <span :style="{ color: colorVar('warning') }">محايدون {{ npsOf(q).passives }}%</span>
              <span :style="{ color: colorVar('success') }">مروّجون {{ npsOf(q).promoters }}%</span>
            </div>
          </template>

          <!-- scale -->
          <template v-else-if="q.type === 'scale'">
            <div class="mb-2 flex items-center gap-3">
              <span class="text-2xl font-bold text-content">{{ numericAvg(q) }}</span>
              <span class="text-xs text-muted">من 10 · «{{ q.scaleMin || 'الأدنى' }}» إلى «{{ q.scaleMax || 'الأعلى' }}»</span>
            </div>
            <BaseProgressBar :value="numericAvg(q) * 10" color="primary" :height="12" />
          </template>

          <!-- matrix -->
          <template v-else-if="q.type === 'matrix'">
            <div v-for="m in matrixAverages(q)" :key="m.row" class="mb-2 flex items-center gap-2">
              <span class="text-sm text-content" style="min-width: 120px">{{ m.row }}</span>
              <BaseProgressBar :value="m.avg * 20" color="warning" :height="10" class="flex-1" />
              <span class="text-xs font-bold text-content" style="width: 32px">{{ m.avg }}</span>
            </div>
          </template>

          <!-- ranking -->
          <template v-else-if="q.type === 'ranking'">
            <div v-for="(r, ri) in rankingAverages(q)" :key="r.label" class="mb-1 flex items-center gap-2">
              <BaseChip :color="ri === 0 ? 'success' : 'brand'">{{ ri + 1 }}</BaseChip>
              <span class="flex-1 text-sm text-content">{{ r.label }}</span>
              <span class="text-xs text-muted">متوسط الترتيب {{ r.avg }}</span>
            </div>
          </template>

          <!-- text -->
          <template v-else>
            <div v-if="textAnswers(q).length" class="flex flex-col gap-2">
              <div v-for="(a, i) in textAnswers(q)" :key="i" class="rounded-ui p-3 text-sm text-content" style="background: rgba(var(--v-theme-on-surface), 0.06)">
                «{{ a }}»
              </div>
            </div>
            <p v-else class="text-xs text-muted">لا إجابات نصية بعد.</p>
          </template>
        </BaseCard>
      </div>

      <div class="lg:col-span-4">
        <!-- AI insights -->
        <BaseCard v-if="insights" class="mb-4">
          <div class="mb-2 flex items-center gap-2">
            <BaseIcon name="mdi-robot-happy-outline" :size="22" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />
            <h3 class="text-base font-bold text-content">تحليل الذكاء الاصطناعي</h3>
            <BaseChip :color="mapColor(sentimentColor)" class="ms-auto">{{ insights.sentimentLabel }}</BaseChip>
          </div>
          <p class="mb-3 text-sm text-content">{{ insights.summary }}</p>
          <div class="mb-1 text-sm font-bold text-content">أبرز الملاحظات</div>
          <div class="mb-3 flex flex-col gap-1">
            <div v-for="t in insights.themes" :key="t" class="flex items-center gap-1 text-xs text-content">
              <BaseIcon name="mdi-circle-small" :size="16" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />{{ t }}
            </div>
          </div>
          <div class="mb-1 text-sm font-bold text-content">توصيات قابلة للتنفيذ</div>
          <div v-for="r in insights.recommendations" :key="r" class="mb-1 rounded-ui border-s-4 p-2 text-xs text-content" style="background: rgba(var(--v-theme-secondary), 0.12); border-color: rgb(var(--v-theme-secondary))">
            {{ r }}
          </div>
        </BaseCard>

        <!-- Response timeline -->
        <BaseCard>
          <h3 class="mb-3 text-base font-bold text-content">الاستجابات عبر الزمن</h3>
          <div v-if="timeline.length" class="flex items-end gap-1" style="height: 90px">
            <div v-for="d in timeline" :key="d.day" class="flex flex-1 flex-col justify-end" style="height: 100%" :title="`${d.day}: ${d.count}`">
              <div class="bar-lime rounded-t" :style="{ height: `${Math.max(8, d.pct)}%` }" />
            </div>
          </div>
          <p v-else class="text-xs text-muted">لا استجابات بعد.</p>
          <div v-if="timeline.length" class="mt-1 flex justify-between text-xs text-muted">
            <span>{{ timeline[0].day }}</span>
            <span>{{ timeline[timeline.length - 1].day }}</span>
          </div>
        </BaseCard>
      </div>
    </div>
  </div>

  <BaseCard v-else class="py-8 text-center">
    <p class="text-content">الاستبيان غير موجود.</p>
    <BaseButton variant="tonal-brand" class="mt-2" @click="router.push({ name: 'surveys' })">عودة للاستبيانات</BaseButton>
  </BaseCard>
</template>
