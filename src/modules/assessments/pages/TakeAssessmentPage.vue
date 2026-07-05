<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QuestionRenderer from '../components/QuestionRenderer.vue'
import { QUESTION_TYPE_META, buildQuestions, getAssessmentById, scoreAnswer } from '../services/mockAssessments'
import { useGamificationStore } from '@/stores/GamificationStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', orange: 'warning', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}

const route = useRoute()
const router = useRouter()
const gamification = useGamificationStore()

const assessment = computed(() => getAssessmentById(Number(route.params.id)))
const size = computed(() => Number(route.query.size) || 10)

// Build a unique question set for this attempt
const questions = computed(() => (assessment.value ? buildQuestions(assessment.value.pool, size.value) : []))

const currentIndex = ref(0)
const answers = ref<Record<number, any>>({})
const revealedHints = ref<Set<number>>(new Set())
const secondsLeft = ref(0)
let timer: ReturnType<typeof setInterval> | undefined

const currentQuestion = computed(() => questions.value[currentIndex.value])
const totalQuestions = computed(() => questions.value.length)
const progress = computed(() => (totalQuestions.value ? ((currentIndex.value + 1) / totalQuestions.value) * 100 : 0))
const isLast = computed(() => currentIndex.value === totalQuestions.value - 1)
const answeredCount = computed(() => Object.values(answers.value).filter(v => v !== undefined && v !== '' && !(Array.isArray(v) && !v.length)).length)

const timeLabel = computed(() => {
  const m = Math.floor(secondsLeft.value / 60)
  const s = secondsLeft.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

function revealHint() {
  if (currentQuestion.value)
    revealedHints.value = new Set(revealedHints.value).add(currentQuestion.value.id)
}
const hintShown = computed(() => !!currentQuestion.value && revealedHints.value.has(currentQuestion.value.id))

function next() {
  if (!isLast.value)
    currentIndex.value++
}
function prev() {
  if (currentIndex.value > 0)
    currentIndex.value--
}
function skip() {
  next()
}

function finish() {
  const qs = questions.value
  const correct = qs.filter(q => scoreAnswer(q, answers.value[q.id])).length
  const score = totalQuestions.value ? Math.round((correct / totalQuestions.value) * 100) : 0
  gamification.record('assessment', `أكملت اختبار ${assessment.value?.name ?? ''}`)
  router.replace({
    name: 'assessment-result',
    params: { id: route.params.id },
    query: { score, correct, total: totalQuestions.value },
  })
}

onMounted(() => {
  if (assessment.value) {
    secondsLeft.value = Math.max(assessment.value.durationMinutes, size.value) * 60
    timer = setInterval(() => {
      if (secondsLeft.value > 0)
        secondsLeft.value--
      else finish()
    }, 1000)
  }
})
onBeforeUnmount(() => {
  if (timer)
    clearInterval(timer)
})
</script>

<template>
  <div v-if="assessment" class="mx-auto" style="max-width: 760px">
    <!-- Top bar: title + timer -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-3">
        <BaseAvatar :color="mapColor(assessment.color)" tonal square>
          <BaseIcon :name="assessment.icon" :size="20" />
        </BaseAvatar>
        <h1 class="mb-0 text-lg font-bold text-content">{{ assessment.name }}</h1>
      </div>
      <div
        class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-bold"
        :style="{ background: `rgba(var(--v-theme-${secondsLeft < 60 ? 'error' : 'primary'}), 0.16)`, color: `rgb(var(--v-theme-${secondsLeft < 60 ? 'error' : 'primary'}))` }"
      >
        <BaseIcon name="mdi-clock-outline" :size="16" />{{ timeLabel }}
      </div>
    </div>

    <!-- Progress -->
    <div class="mb-1 flex justify-between text-xs">
      <span class="text-muted">السؤال {{ currentIndex + 1 }} من {{ totalQuestions }}</span>
      <span class="text-muted">تمت الإجابة على {{ answeredCount }}</span>
    </div>
    <BaseProgressBar :value="progress" color="accent" :height="8" class="mb-5" />

    <!-- Question -->
    <BaseCard v-if="currentQuestion" class="mb-4 min-h-[280px] p-6">
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <BaseChip color="emerald"><BaseIcon :name="QUESTION_TYPE_META[currentQuestion.type].icon" :size="12" />{{ QUESTION_TYPE_META[currentQuestion.type].label }}</BaseChip>
      </div>
      <div class="mb-5 text-lg font-bold text-content">{{ currentQuestion.text }}</div>

      <QuestionRenderer :key="currentQuestion.id" v-model="answers[currentQuestion.id]" :question="currentQuestion" />

      <!-- AI hint -->
      <div class="mt-4">
        <div v-if="hintShown && currentQuestion.hint" class="flex items-start gap-2 rounded-ui border-s-4 p-2" style="background: rgba(var(--v-theme-secondary), 0.12); border-color: rgb(var(--v-theme-secondary))">
          <BaseIcon name="mdi-robot-happy-outline" :size="20" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />
          <span class="text-xs text-content">{{ currentQuestion.hint }}</span>
        </div>
        <BaseButton v-if="!hintShown && currentQuestion.hint" size="sm" variant="ghost" @click="revealHint">
          <BaseIcon name="mdi-lightbulb-on-outline" :size="16" :style="{ color: 'rgb(var(--v-theme-secondary))' }" /><span :style="{ color: 'rgb(var(--v-theme-secondary))' }">تلميح من الـ AI</span>
        </BaseButton>
      </div>
    </BaseCard>

    <!-- Navigation -->
    <div class="flex flex-wrap items-center justify-between gap-2">
      <BaseButton variant="outline" :disabled="currentIndex === 0" @click="prev"><BaseIcon name="mdi-arrow-right" :size="16" />السابق</BaseButton>
      <BaseButton variant="ghost" size="sm" @click="skip">تخطّي السؤال</BaseButton>
      <BaseButton v-if="!isLast" variant="accent" @click="next">التالي<BaseIcon name="mdi-arrow-left" :size="16" /></BaseButton>
      <BaseButton v-else variant="emerald" @click="finish"><BaseIcon name="mdi-flag-checkered" :size="16" />إنهاء الاختبار</BaseButton>
    </div>
  </div>

  <BaseCard v-else class="py-12 text-center">
    <BaseIcon name="mdi-alert-circle-outline" :size="64" :style="{ color: 'rgb(var(--v-theme-error))' }" />
    <div class="mt-3 text-lg font-bold text-content">الاختبار غير موجود</div>
    <BaseButton variant="brand" class="mt-3" :to="{ name: 'assessments' }">العودة لمركز التقييم</BaseButton>
  </BaseCard>
</template>
