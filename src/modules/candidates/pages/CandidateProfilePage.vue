<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CANDIDATE_STATUS_META } from '../interfaces/Candidate'
import { useCandidatesStore } from '@/stores/CandidatesStore'
import { KIND_META, useInterviewersStore } from '@/stores/InterviewersStore'
import type { MarketInterviewKind } from '@/stores/InterviewersStore'
import { useNotificationsStore } from '@/stores/NotificationsStore'
import { useWishesStore } from '@/stores/WishesStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'
import BaseProgressRing from '@/components/ui/BaseProgressRing.vue'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'

const route = useRoute()
const router = useRouter()
const store = useCandidatesStore()
const interviewersStore = useInterviewersStore()
const notifications = useNotificationsStore()
const candidate = computed(() => store.getById(Number(route.params.id)))
const snackbar = ref('')

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}

// Certified-interviewer reports already on the candidate's record (mock)
const candidateReports = [
  { id: 1, interviewer: 'م. خالد الشمري', kind: 'skills' as MarketInterviewKind, level: 'متقدم', overall: 88, verified: true, strengths: ['حلول تقنية منظّمة', 'إلمام عميق بأنماط التصميم'], improvements: ['تحسين تغطية الاختبارات'], recommendation: 'مرشح تقني قوي جاهز لأدوار متقدمة.' },
  { id: 2, interviewer: 'أ. سلمى العنزي', kind: 'behavioral' as MarketInterviewKind, level: 'متقدم', overall: 84, verified: true, strengths: ['تواصل واضح', 'وعي ذاتي عالٍ'], improvements: ['تعزيز الحزم في المواقف الصعبة'], recommendation: 'مرشح متوازن سلوكيًا مناسب للعمل التعاوني.' },
]
const reportDialog = ref(false)
const activeReport = ref<typeof candidateReports[number] | null>(null)
function openReport(r: typeof candidateReports[number]) {
  activeReport.value = r
  reportDialog.value = true
}

// Send a real wish to this candidate (lands in إدارة الرغبات + simulated reply)
const wishesStore = useWishesStore()
const wishDialog = ref(false)
const wishForm = ref({ role: '', amount: '', duration: 'دائم', reason: '' })
const DURATIONS = ['دائم', 'سنة', '6 أشهر', 'مهمة']
const DURATION_OPTIONS = DURATIONS.map(d => ({ value: d, title: d }))
function openWishDialog() {
  wishForm.value = {
    role: candidate.value?.appliedFor ?? candidate.value?.title ?? '',
    amount: '',
    duration: 'دائم',
    reason: candidate.value ? `مهاراتك في ${candidate.value.skills.slice(0, 2).join(' و')} تطابق احتياجنا تمامًا.` : '',
  }
  wishDialog.value = true
}
function sendWish() {
  if (!candidate.value)
    return
  wishesStore.sendWish({
    candidateId: candidate.value.id,
    candidateName: candidate.value.name,
    role: wishForm.value.role,
    amount: wishForm.value.amount || 'قابل للتفاوض',
    duration: wishForm.value.duration,
    reason: wishForm.value.reason,
  })
  wishDialog.value = false
  snackbar.value = `أُرسلت رغبتك إلى ${candidate.value.name} — ستصلك استجابته كإشعار`
}

// Request interview via a certified interviewer
const requestInterviewDialog = ref(false)
const chosenInterviewerId = ref<number | null>(interviewersStore.interviewers[0]?.id ?? null)
const chosenKind = ref<MarketInterviewKind>('skills')
const kinds = Object.keys(KIND_META) as MarketInterviewKind[]
const chosenInterviewer = computed(() => interviewersStore.getById(chosenInterviewerId.value ?? -1))
const requestPrice = computed(() => {
  const iv = chosenInterviewer.value
  if (!iv)
    return 0
  const weight: Record<MarketInterviewKind, number> = { level: 0.2, behavioral: 0.4, skills: 0.6, leadership: 0.85, comprehensive: 1 }
  return Math.round((iv.priceMin + (iv.priceMax - iv.priceMin) * weight[chosenKind.value]) / 5) * 5
})
function sendInterviewRequest() {
  requestInterviewDialog.value = false
  const ivName = chosenInterviewer.value?.name ?? 'مقيّم معتمد'
  notifications.push({
    icon: 'mdi-account-tie',
    color: 'primary',
    title: 'طلب مقابلة عبر مقيّم معتمد',
    body: `طلبت ${KIND_META[chosenKind.value].label} للمرشح ${candidate.value?.name} عبر ${ivName} (${requestPrice.value} ﷼)`,
    category: 'interview',
  })
  snackbar.value = `تم إرسال طلب مقابلة للمرشح عبر ${ivName}`
}

const matchBreakdown = [
  { label: 'المهارات', value: 90 },
  { label: 'الخبرات', value: 85 },
  { label: 'التعليم', value: 95 },
  { label: 'الموقع', value: 100 },
]

const endorsements = [
  { name: 'محمد العلي', relation: 'مدير سابق', type: 'فيديو', trusted: true },
  { name: 'لينا سعد', relation: 'زميلة', type: 'نص', trusted: false },
]
</script>

<template>
  <div v-if="candidate">
    <BaseButton variant="ghost" size="sm" class="mb-3" @click="router.back()">
      <BaseIcon name="mdi-arrow-right" :size="16" />رجوع للترشيحات
    </BaseButton>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
      <div class="md:col-span-8">
        <BaseCard class="mb-4">
          <div class="mb-4 flex items-center gap-4">
            <BaseAvatar color="emerald" :size="72">
              <span class="text-2xl font-bold">{{ candidate.name.charAt(0) }}</span>
            </BaseAvatar>
            <div>
              <h1 class="text-xl font-bold text-content">{{ candidate.name }}</h1>
              <div class="text-muted">{{ candidate.title }} · {{ candidate.location }}</div>
              <BaseChip color="neutral" class="mt-1">{{ candidate.level }} · {{ candidate.experienceYears }} سنوات خبرة</BaseChip>
            </div>
          </div>
          <p class="text-sm text-muted">{{ candidate.summary }}</p>

          <hr class="my-4 border-ui">
          <h3 class="mb-2 text-base font-bold text-content">المهارات</h3>
          <div class="mb-4 flex flex-wrap gap-2">
            <BaseChip v-for="s in candidate.skills" :key="s" color="brand">{{ s }}</BaseChip>
          </div>

          <h3 class="mb-2 text-base font-bold text-content">التوصيات</h3>
          <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div v-for="e in endorsements" :key="e.name" class="flex items-center gap-3 rounded-ui-lg border-ui p-3">
              <BaseAvatar color="emerald" tonal><BaseIcon name="mdi-account" :size="20" /></BaseAvatar>
              <div class="flex-1">
                <div class="flex items-center gap-1 text-sm font-bold text-content">
                  {{ e.name }}
                  <BaseIcon v-if="e.trusted" name="mdi-check-decagram" :size="16" :style="{ color: 'rgb(var(--v-theme-success))' }" />
                </div>
                <div class="text-xs text-muted">{{ e.relation }}</div>
              </div>
              <BaseChip color="neutral">{{ e.type }}</BaseChip>
            </div>
          </div>

          <!-- Certified-interviewer reports -->
          <div class="mb-2 flex items-center gap-2">
            <BaseIcon name="mdi-account-tie" :size="20" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />
            <h3 class="text-base font-bold text-content">تقارير المقيّمين المعتمدين ({{ candidateReports.length }})</h3>
          </div>
          <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button v-for="r in candidateReports" :key="r.id" type="button" class="rounded-ui-lg border-ui p-3 text-start transition hover:bg-surfalt" @click="openReport(r)">
              <div class="mb-1 flex items-center justify-between">
                <span class="text-sm font-bold text-content">{{ r.interviewer }}</span>
                <BaseChip color="success">{{ r.overall }}%</BaseChip>
              </div>
              <div class="mb-2 text-xs text-muted">{{ KIND_META[r.kind].label }} · المستوى {{ r.level }}</div>
              <span class="inline-flex items-center gap-1 text-xs font-medium" :style="{ color: 'rgb(var(--v-theme-primary))' }"><BaseIcon name="mdi-file-document-outline" :size="14" />عرض التقرير</span>
            </button>
          </div>

          <div class="mb-2 flex items-center justify-between">
            <h3 class="text-base font-bold text-content">السير الذاتية المُقدّمة</h3>
            <BaseButton variant="ghost" size="sm" @click="snackbar = 'تم إرسال طلب سيرة ذاتية محدّثة للمرشح'">
              <BaseIcon name="mdi-refresh" :size="14" :style="{ color: 'rgb(var(--v-theme-primary))' }" /><span :style="{ color: 'rgb(var(--v-theme-primary))' }">طلب سيرة محدّثة</span>
            </BaseButton>
          </div>
          <div class="flex items-center gap-3 rounded-ui-lg border-ui p-3">
            <BaseAvatar color="brand" tonal square><BaseIcon name="mdi-file-account-outline" :size="20" /></BaseAvatar>
            <div class="flex-1">
              <div class="text-sm font-bold text-content">سيرة {{ candidate.name.split(' ')[0] }} - {{ candidate.title }}</div>
              <div class="text-xs text-muted">قالب حديث · عربي · قُدّمت مع الطلب</div>
            </div>
            <BaseButton variant="ghost" size="sm" :to="{ name: 'public-resume', params: { token: String(candidate.id) } }"><BaseIcon name="mdi-eye-outline" :size="18" /></BaseButton>
            <button class="icon-btn h-9 w-9" aria-label="تنزيل"><BaseIcon name="mdi-download" :size="18" /></button>
          </div>
        </BaseCard>
      </div>

      <div class="md:col-span-4">
        <BaseCard class="mb-4 text-center">
          <BaseProgressRing :value="candidate.matchRate" :size="110" :width="10" color="success" class="mx-auto">
            <span class="text-xl font-bold text-content">{{ candidate.matchRate }}%</span>
          </BaseProgressRing>
          <div class="mb-2 mt-2 text-sm text-muted">تطابق مع: {{ candidate.appliedFor }}</div>
          <div class="mb-4">
            <BaseChip :color="mapColor(CANDIDATE_STATUS_META[candidate.status].color)">الحالة: {{ CANDIDATE_STATUS_META[candidate.status].label }}</BaseChip>
          </div>

          <BaseButton variant="accent" block class="mb-2" @click="openWishDialog"><BaseIcon name="mdi-hand-heart-outline" :size="16" />إبداء رغبة</BaseButton>
          <BaseButton variant="brand" block class="mb-2" @click="requestInterviewDialog = true"><BaseIcon name="mdi-account-tie-voice-outline" :size="16" />طلب مقابلة</BaseButton>
          <BaseButton variant="tonal-brand" block class="mb-2" @click="store.setStatus(candidate.id, 'interview'); snackbar = 'تمت دعوة المرشح لمقابلة'"><BaseIcon name="mdi-calendar-clock-outline" :size="16" />جدولة مقابلة</BaseButton>
          <BaseButton variant="outline" block class="mb-2" :to="{ name: 'messages' }"><BaseIcon name="mdi-message-outline" :size="16" />إرسال رسالة</BaseButton>
          <BaseButton variant="ghost" block @click="store.setStatus(candidate.id, 'rejected'); snackbar = 'تم رفض الترشيح'"><BaseIcon name="mdi-close" :size="16" :style="{ color: 'rgb(var(--v-theme-error))' }" /><span :style="{ color: 'rgb(var(--v-theme-error))' }">رفض الترشيح</span></BaseButton>
        </BaseCard>

        <BaseCard>
          <div class="mb-3 text-base font-bold text-content">تحليل التطابق</div>
          <div v-for="item in matchBreakdown" :key="item.label" class="mb-3">
            <div class="mb-1 flex justify-between text-sm text-content">
              <span>{{ item.label }}</span>
              <span class="font-bold">{{ item.value }}%</span>
            </div>
            <BaseProgressBar :value="item.value" color="primary" :height="6" />
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Request interview via certified interviewer -->
    <BaseModal v-model="requestInterviewDialog" :title="`طلب مقابلة تقييمية — ${candidate.name}`" :max-width="520">
      <p class="mb-3 text-sm text-muted">اختر مقيّمًا معتمدًا يُجري المقابلة ويوثّق نتيجتها:</p>
      <label class="mb-1 block text-sm font-medium text-muted">المقيّم المعتمد</label>
      <BaseSelect v-model="chosenInterviewerId" :items="interviewersStore.interviewers.map(i => ({ value: i.id, title: `${i.name} · ${i.title}` }))" prefix-icon="mdi-account-tie" class="mb-3" />
      <label class="mb-1 block text-sm font-medium text-muted">نوع المقابلة</label>
      <BaseSelect :model-value="chosenKind" :items="kinds.map(k => ({ value: k, title: `${KIND_META[k].label} · ${KIND_META[k].minutes}` }))" class="mb-2" @update:model-value="v => v && (chosenKind = v)" />
      <div class="flex items-center justify-between rounded-ui p-2" style="background: rgba(var(--v-theme-accent), 0.16)">
        <span class="text-sm text-content">التكلفة التقديرية</span>
        <span class="font-bold text-content">{{ requestPrice }} ﷼</span>
      </div>
      <template #actions>
        <BaseButton variant="ghost" @click="requestInterviewDialog = false">إلغاء</BaseButton>
        <BaseButton variant="accent" :disabled="!chosenInterviewerId" @click="sendInterviewRequest"><BaseIcon name="mdi-send" :size="16" />إرسال الطلب</BaseButton>
      </template>
    </BaseModal>

    <!-- Report view dialog -->
    <BaseModal v-model="reportDialog" :max-width="480">
      <template v-if="activeReport" #title>
        <div class="flex items-center justify-between gap-2">
          <span>تقرير المقابلة</span>
          <BaseChip color="success">{{ activeReport.overall }}%</BaseChip>
        </div>
      </template>
      <template v-if="activeReport">
        <div class="mb-1 flex items-center gap-1 text-xs text-muted">
          <BaseIcon name="mdi-check-decagram" :size="14" :style="{ color: 'rgb(var(--v-theme-primary))' }" /> {{ activeReport.interviewer }} · {{ KIND_META[activeReport.kind].label }} · المستوى {{ activeReport.level }}
        </div>
        <hr class="my-2 border-ui">
        <div class="mb-1 text-sm font-bold text-content">نقاط القوة</div>
        <div class="flex flex-wrap gap-1">
          <BaseChip v-for="s in activeReport.strengths" :key="s" color="success">{{ s }}</BaseChip>
        </div>
        <div class="mb-1 mt-2 text-sm font-bold text-content">نقاط التحسين</div>
        <div class="flex flex-wrap gap-1">
          <BaseChip v-for="w in activeReport.improvements" :key="w" color="warning">{{ w }}</BaseChip>
        </div>
        <div class="mt-3 flex items-start gap-2 rounded-ui p-2 text-sm text-content" style="background: rgba(var(--v-theme-secondary), 0.12)">
          <BaseIcon name="mdi-lightbulb-on-outline" :size="18" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />
          {{ activeReport.recommendation }}
        </div>
      </template>
      <template #actions>
        <BaseButton variant="ghost" @click="reportDialog = false">إغلاق</BaseButton>
      </template>
    </BaseModal>

    <!-- Send-wish dialog -->
    <BaseModal v-model="wishDialog" :title="`إبداء رغبة — ${candidate?.name}`" :max-width="480">
      <BaseInput v-model="wishForm.role" label="الدور المعروض" prefix-icon="mdi-briefcase-outline" class="mb-3" />
      <BaseInput v-model="wishForm.amount" label="المقابل (مثال: 16,000 ريال)" prefix-icon="mdi-cash-multiple" class="mb-3" />
      <label class="mb-1 block text-sm font-medium text-muted">المدة</label>
      <BaseSelect :model-value="wishForm.duration" :items="DURATION_OPTIONS" prefix-icon="mdi-calendar-range-outline" class="mb-3" @update:model-value="v => v && (wishForm.duration = v)" />
      <BaseTextarea v-model="wishForm.reason" label="لماذا هذا المرشح؟" :rows="2" />
      <template #actions>
        <BaseButton variant="ghost" @click="wishDialog = false">إلغاء</BaseButton>
        <BaseButton variant="accent" :disabled="!wishForm.role.trim()" @click="sendWish"><BaseIcon name="mdi-send" :size="16" />إرسال الرغبة</BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar :model-value="!!snackbar" color="success" :timeout="2500" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </BaseSnackbar>
  </div>

  <BaseCard v-else class="py-12 text-center">
    <BaseIcon name="mdi-account-alert-outline" :size="64" :style="{ color: 'rgb(var(--v-theme-error))' }" />
    <div class="mt-3 text-lg font-bold text-content">المرشح غير موجود</div>
    <BaseButton variant="brand" class="mt-3" :to="{ name: 'candidates' }">العودة للترشيحات</BaseButton>
  </BaseCard>
</template>
