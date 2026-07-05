<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import { LEVEL_META, TYPE_META, useInterviewsStore } from '@/stores/InterviewsStore'
import type { Interview } from '@/stores/InterviewsStore'
import type { InterviewLevel, InterviewTrack, InterviewType } from '@/services/ai'
import { TRACK_META } from '@/services/ai'
import { BOOKING_STATUS_META, KIND_META, useInterviewersStore } from '@/stores/InterviewersStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseModal from '@/components/ui/BaseModal.vue'

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral' } as Record<string, BaseColor>)[c] ?? c) as BaseColor
}

const router = useRouter()
const store = useInterviewsStore()
const interviewers = useInterviewersStore()

// — Upcoming schedule (weekly calendar + smart reminders) —
const AR_DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
function parseDT(s: string): Date | null {
  const m = s.match(/(\d{4})-(\d{2})-(\d{2})(?:[^\d]*(\d{1,2}):(\d{2}))?/)
  if (!m)
    return null
  const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), m[4] ? Number(m[4]) : 0, m[5] ? Number(m[5]) : 0)
  return Number.isNaN(d.getTime()) ? null : d
}
const nowRef = new Date()
const todayMid = new Date(nowRef.getFullYear(), nowRef.getMonth(), nowRef.getDate())
function daysUntil(d: Date) {
  const dm = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  return Math.round((dm.getTime() - todayMid.getTime()) / 86_400_000)
}
function relLabel(d: Date | null) {
  if (!d)
    return ''
  const n = daysUntil(d)
  if (n < 0)
    return 'سابقة'
  if (n === 0)
    return 'اليوم'
  if (n === 1)
    return 'غدًا'
  if (n <= 7)
    return `خلال ${n} أيام`
  return `بعد ${n} يومًا`
}
function sameDay(a: Date, b: Date) {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
}

interface AgendaEntry { id: string, title: string, icon: string, raw: string, dt: Date | null, statusLabel: string, color: string, to: any }
const upcoming = computed<AgendaEntry[]>(() => {
  const fromInterviews: AgendaEntry[] = store.interviews
    .filter(i => i.status !== 'completed')
    .map(i => ({
      id: `iv${i.id}`, title: `${TYPE_META[i.type].label} · ${LEVEL_META[i.level].label}`, icon: TYPE_META[i.type].icon,
      raw: i.date, dt: parseDT(i.date),
      statusLabel: i.status === 'in_progress' ? 'قيد التنفيذ' : 'مجدولة', color: i.status === 'in_progress' ? 'primary' : 'warning',
      to: i.status === 'in_progress' ? { name: 'interview-session', params: { id: i.id } } : { name: 'interviews' },
    }))
  const fromBookings: AgendaEntry[] = interviewers.bookings
    .filter(b => b.status === 'requested' || b.status === 'scheduled')
    .map(b => ({
      id: `bk${b.id}`, title: `${b.interviewerName} · ${KIND_META[b.kind].label}`, icon: 'mdi-account-tie-voice-outline',
      raw: b.datetime, dt: parseDT(b.datetime),
      statusLabel: BOOKING_STATUS_META[b.status].label, color: BOOKING_STATUS_META[b.status].color,
      to: { name: 'interviewers' },
    }))
  return [...fromInterviews, ...fromBookings].sort((a, b) => (a.dt ? a.dt.getTime() : Infinity) - (b.dt ? b.dt.getTime() : Infinity))
})
const nextSession = computed(() => upcoming.value.find(u => u.dt && daysUntil(u.dt) >= 0) ?? upcoming.value[0] ?? null)
const week = computed(() => Array.from({ length: 7 }, (_, k) => {
  const d = new Date(todayMid)
  d.setDate(d.getDate() + k)
  return { key: k, label: AR_DAYS[d.getDay()], num: d.getDate(), items: upcoming.value.filter(u => u.dt && sameDay(u.dt, d)), isToday: k === 0 }
}))

const types = Object.keys(TYPE_META) as InterviewType[]
const levels = Object.keys(LEVEL_META) as InterviewLevel[]
const tracks = Object.keys(TRACK_META) as InterviewTrack[]

const setupDialog = ref(false)
const chosenType = ref<InterviewType>('ai_text')
const chosenLevel = ref<InterviewLevel>('intermediate')
const chosenTrack = ref<InterviewTrack>('tech')

const isAiInterview = computed(() => chosenType.value === 'ai_text' || chosenType.value === 'ai_video')

function openSetup(type: InterviewType) {
  chosenType.value = type
  chosenLevel.value = 'intermediate'
  setupDialog.value = true
}

function startNow() {
  const id = store.start(chosenType.value, chosenLevel.value, isAiInterview.value ? chosenTrack.value : undefined)
  setupDialog.value = false
  if (isAiInterview.value)
    router.push({ name: 'interview-session', params: { id } })
  else
    router.push({ name: 'interviews' })
}

function viewResult(iv: Interview) {
  router.push({ name: 'interview-result', params: { id: iv.id } })
}
</script>

<template>
  <div>
    <PageHeader
      title="المقابلات وتحديد المستوى"
      subtitle="أثبت مستواك عبر مقابلات ذكية أو مع خبراء — ترفع نسبة ثقتك"
      icon="mdi-account-tie-voice-outline"
    />

    <!-- Upcoming schedule (weekly calendar + smart reminder) -->
    <BaseCard v-if="upcoming.length" class="mb-4">
      <div class="mb-3 flex items-center gap-2">
        <BaseIcon name="mdi-calendar-clock-outline" :size="20" style="color: rgb(var(--v-theme-primary))" />
        <h3 class="font-bold text-content">جدولك القادم</h3>
      </div>

      <!-- Smart reminder -->
      <div v-if="nextSession" class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-ui p-3" style="background: rgba(var(--v-theme-accent), 0.14)">
        <span class="flex flex-wrap items-center gap-1 text-sm" style="color: rgb(var(--v-theme-accent))">
          <BaseIcon name="mdi-bell-ring-outline" :size="16" />
          <span class="font-bold">مقابلتك القادمة:</span> {{ nextSession.title }}
          <BaseChip v-if="nextSession.dt" color="accent">{{ relLabel(nextSession.dt) }}</BaseChip>
          <span class="text-xs text-muted">{{ nextSession.raw }}</span>
        </span>
        <BaseButton variant="accent" size="sm" :to="nextSession.to">التفاصيل</BaseButton>
      </div>

      <!-- Weekly strip -->
      <div class="week-strip">
        <div
          v-for="day in week"
          :key="day.key"
          class="week-day p-2 text-center"
          :class="{ 'week-day--today': day.isToday, 'week-day--has': day.items.length }"
        >
          <div class="text-xs text-muted">{{ day.label }}</div>
          <div class="text-lg font-bold text-content">{{ day.num }}</div>
          <div class="mt-1 flex justify-center gap-1" style="min-height: 8px">
            <span v-for="it in day.items.slice(0, 3)" :key="it.id" class="week-dot" :style="{ background: `rgb(var(--v-theme-${it.color}))` }" />
          </div>
        </div>
      </div>

      <!-- Upcoming list -->
      <div class="mt-2">
        <RouterLink
          v-for="u in upcoming"
          :key="u.id"
          :to="u.to"
          class="flex items-center gap-3 rounded-ui px-2 py-2 transition hover:bg-surfalt"
        >
          <BaseAvatar :color="mapColor(u.color)" tonal square :size="38"><BaseIcon :name="u.icon" :size="20" /></BaseAvatar>
          <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-bold text-content">{{ u.title }}</div>
            <div class="text-xs text-muted">{{ u.raw }}</div>
          </div>
          <div class="flex items-center gap-2">
            <BaseChip v-if="u.dt" color="neutral">{{ relLabel(u.dt) }}</BaseChip>
            <BaseChip :color="mapColor(u.color)">{{ u.statusLabel }}</BaseChip>
          </div>
        </RouterLink>
      </div>
    </BaseCard>

    <!-- Available -->
    <h3 class="mb-3 text-lg font-bold text-content">المقابلات المتاحة</h3>
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <BaseCard v-for="t in types" :key="t" class="flex flex-col text-center">
        <BaseAvatar color="brand" tonal square :size="56" class="mx-auto mb-3"><BaseIcon :name="TYPE_META[t].icon" :size="30" /></BaseAvatar>
        <div class="text-sm font-bold text-content">{{ TYPE_META[t].label }}</div>
        <div class="mb-3 flex-1 text-xs text-muted">{{ TYPE_META[t].desc }}</div>
        <BaseButton variant="accent" size="sm" block @click="openSetup(t)">اختيار المستوى</BaseButton>
      </BaseCard>
    </div>

    <!-- History -->
    <h3 class="mb-3 text-lg font-bold text-content">سجل المقابلات ({{ store.count }})</h3>
    <BaseCard v-if="store.count" :padded="false">
      <div>
        <div
          v-for="(iv, i) in store.interviews"
          :key="iv.id"
          class="flex items-center gap-3 p-4"
          :style="i > 0 ? 'border-top: 1px solid rgba(var(--v-theme-on-surface), 0.12)' : ''"
        >
          <BaseAvatar :color="iv.status === 'completed' ? 'success' : 'warning'" tonal square>
            <BaseIcon :name="TYPE_META[iv.type].icon" :size="22" />
          </BaseAvatar>
          <div class="min-w-0 flex-1">
            <div class="font-bold text-content">{{ TYPE_META[iv.type].label }} · {{ LEVEL_META[iv.level].label }}</div>
            <div class="text-sm text-muted">
              {{ iv.date }} ·
              <span v-if="iv.result">النتيجة {{ iv.result.score }}% ({{ iv.result.level }})</span>
              <span v-else>{{ iv.status === 'in_progress' ? 'قيد التنفيذ' : 'مجدولة' }}</span>
            </div>
          </div>
          <BaseButton v-if="iv.status === 'completed'" variant="tonal-brand" size="sm" @click="viewResult(iv)">التقرير</BaseButton>
          <BaseButton v-else-if="iv.status === 'in_progress'" variant="accent" size="sm" @click="router.push({ name: 'interview-session', params: { id: iv.id } })">متابعة</BaseButton>
        </div>
      </div>
    </BaseCard>
    <BaseCard v-else class="py-8 text-center">
      <BaseIcon name="mdi-account-voice" :size="48" class="text-muted" />
      <div class="mt-2 text-sm text-muted">لم تُجرِ أي مقابلة بعد — ابدأ بمقابلة AI أساسية مجانية</div>
    </BaseCard>

    <!-- Setup dialog -->
    <BaseModal v-model="setupDialog" :title="TYPE_META[chosenType].label" :max-width="520">
      <template v-if="isAiInterview">
        <div class="mb-2 text-sm font-bold text-content">اختر مسار المقابلة</div>
        <p class="mb-2 text-xs text-muted">أسئلة تفاعلية تكيّفية مضادة للغش حسب مجالك</p>
        <div class="mb-3 grid grid-cols-2 gap-2">
          <button
            v-for="tr in tracks"
            :key="tr"
            type="button"
            class="flex items-center gap-2 rounded-ui border-ui p-2 text-start transition"
            :class="{ 'is-selected': chosenTrack === tr }"
            @click="chosenTrack = tr"
          >
            <BaseIcon :name="TRACK_META[tr].icon" :size="20" style="color: rgb(var(--v-theme-primary))" />
            <div class="text-xs font-bold">{{ TRACK_META[tr].label }}</div>
          </button>
        </div>
      </template>

      <div class="mb-2 text-sm font-bold text-content">اختر المستوى</div>
      <button
        v-for="lvl in levels"
        :key="lvl"
        type="button"
        class="mb-2 flex w-full items-center justify-between rounded-ui border-ui p-3 text-start transition"
        :class="{ 'is-selected': chosenLevel === lvl }"
        @click="chosenLevel = lvl"
      >
        <div class="flex items-center gap-2 text-content">
          <BaseIcon :name="chosenLevel === lvl ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank'" :size="20" />
          <span>{{ LEVEL_META[lvl].label }}</span>
        </div>
        <BaseChip :color="chosenLevel === lvl ? 'neutral' : 'accent'">
          {{ LEVEL_META[lvl].cost === 0 ? 'مجاني' : `${LEVEL_META[lvl].cost} ريال` }}
        </BaseChip>
      </button>

      <template #actions>
        <BaseButton variant="ghost" size="sm" @click="setupDialog = false">إلغاء</BaseButton>
        <BaseButton v-if="chosenType === 'external' || chosenType === 'expert'" variant="tonal-emerald" size="sm">
          <BaseIcon name="mdi-calendar" :size="16" /> جدولة
        </BaseButton>
        <BaseButton variant="accent" size="sm" @click="startNow">
          <BaseIcon name="mdi-play" :size="16" /> {{ chosenType === 'external' || chosenType === 'expert' ? 'طلب المقابلة' : 'بدء الآن' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.week-strip {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}
.week-day {
  border: 1px solid rgba(140, 163, 150, 0.18);
  border-radius: var(--ui-radius);
  transition: border-color 0.18s ease, background 0.18s ease;
}
.week-day--has {
  border-color: rgba(var(--v-theme-primary), 0.4);
}
.week-day--today {
  background: rgba(var(--v-theme-primary), 0.1);
  border-color: rgb(var(--v-theme-primary));
}
.week-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}
/* بطاقة اختيار محدّدة (المسار/المستوى) */
.is-selected {
  background: rgba(var(--v-theme-primary), 0.14);
  border-color: rgb(var(--v-theme-primary));
}
</style>
