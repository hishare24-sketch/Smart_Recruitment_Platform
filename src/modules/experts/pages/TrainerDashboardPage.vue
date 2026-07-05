<script setup lang="ts">
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import ExpertBrandPanel from '../components/ExpertBrandPanel.vue'
import { useExpertRolesStore } from '@/stores/ExpertRolesStore'
import type { TraineeReferral } from '@/stores/ExpertRolesStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'

// لوحة المدرب التقني — حلقة متكاملة: تقييم → تدريب → توظيف
const store = useExpertRolesStore()
const snackbar = ref('')

const courseDialog = ref(false)
const newCourse = ref({ title: '', skill: '', kind: 'ورشة عمل' as 'دورة مكثفة' | 'ورشة عمل', price: 200, seats: 15 })
function saveCourse() {
  if (!newCourse.value.title.trim())
    return
  store.addCourse({ ...newCourse.value, title: newCourse.value.title.trim() })
  courseDialog.value = false
  snackbar.value = 'أُنشئت الدورة وفُتح التسجيل'
}

const enrollDialog = ref(false)
const enrolling = ref<TraineeReferral | null>(null)
const chosenCourseId = ref<number | null>(null)
function openEnroll(t: TraineeReferral) {
  enrolling.value = t
  chosenCourseId.value = store.state.courses.find(c => c.status === 'open')?.id ?? null
  enrollDialog.value = true
}
function doEnroll() {
  if (enrolling.value && chosenCourseId.value !== null && store.enrollTrainee(enrolling.value.id, chosenCourseId.value))
    snackbar.value = `سُجّل ${enrolling.value.name} — الرسوم أُودعت محفظتك (معلقة حتى التسوية)`
  enrollDialog.value = false
}

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
const STATUS: Record<string, { label: string, color: BaseColor }> = {
  open: { label: 'التسجيل مفتوح', color: 'success' },
  running: { label: 'جارية', color: 'info' },
  done: { label: 'منتهية', color: 'neutral' },
}
</script>

<template>
  <div>
    <PageHeader title="لوحة المدرب التقني" subtitle="دورات وورش تعالج فجوات نتائج التقييم" icon="mdi-school-outline" />

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-4">
      <StatCard title="دوراتي" :value="store.trainerStats.courses" icon="mdi-book-open-variant" color="primary" />
      <StatCard title="متدربون" :value="store.trainerStats.trainees" icon="mdi-account-group-outline" color="secondary" />
      <StatCard title="إيرادات الدورات" :value="`${store.trainerStats.revenue} ر.س`" icon="mdi-cash-multiple" color="success" />
      <StatCard title="إحالات جديدة" :value="store.trainerStats.newReferrals" icon="mdi-account-arrow-left-outline" color="accent" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- المحتوى: إحالات + دورات -->
      <div class="space-y-6 lg:col-span-2">
        <!-- إحالات من نتائج التقييم -->
        <BaseCard>
          <h2 class="mb-1 font-bold">مرشحون بحاجة لتدريب</h2>
          <p class="mb-3 text-xs text-muted">إحالات آلية من نتائج مركز التقييم وتقارير المقيّمين — هذه ميزتك التنافسية.</p>
          <template v-if="store.state.trainees.filter(t => t.status === 'new').length">
            <div v-for="t in store.state.trainees.filter(x => x.status === 'new')" :key="t.id" class="mb-2 rounded-ui border-ui p-3">
              <div class="mb-1 flex items-center gap-2">
                <BaseAvatar color="accent" :size="34" tonal>{{ t.initial }}</BaseAvatar>
                <div class="flex-1">
                  <div class="text-sm font-bold">{{ t.name }}</div>
                  <div class="text-xs text-muted">{{ t.source }}</div>
                </div>
              </div>
              <p class="mb-2 flex items-center gap-1 text-xs"><BaseIcon name="mdi-target" :size="12" style="color: rgb(var(--v-theme-warning))" /> {{ t.gap }}</p>
              <BaseButton variant="accent" size="sm" block @click="openEnroll(t)"><BaseIcon name="mdi-school-outline" :size="16" /> سجّله في دورة</BaseButton>
            </div>
          </template>
          <EmptyState v-else icon="mdi-account-check-outline" title="لا إحالات جديدة" description="ستصلك إحالات تلقائية من نتائج التقييم" />
        </BaseCard>

        <!-- دوراتي -->
        <BaseCard>
          <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">دوراتي وورشي</h2>
            <BaseButton variant="tonal-accent" size="sm" @click="courseDialog = true"><BaseIcon name="mdi-plus" :size="16" /> دورة جديدة</BaseButton>
          </div>
          <div v-for="c in store.state.courses" :key="c.id" class="mb-2 rounded-ui border-ui p-3">
            <div class="mb-1 flex flex-wrap items-center gap-2">
              <span class="flex-1 text-sm font-bold">{{ c.title }}</span>
              <BaseChip :color="STATUS[c.status].color">{{ STATUS[c.status].label }}</BaseChip>
            </div>
            <div class="mb-2 text-xs text-muted">{{ c.kind }} · {{ c.skill }} · {{ c.price }} ر.س</div>
            <div class="flex items-center gap-2">
              <BaseProgressBar :value="(c.enrolled / c.seats) * 100" color="secondary" :height="8" class="flex-1" />
              <span class="text-xs">{{ c.enrolled }}/{{ c.seats }} مقعدًا</span>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- الملف والنمو -->
      <div>
        <ExpertBrandPanel />
      </div>
    </div>

    <!-- دورة جديدة -->
    <BaseModal v-model="courseDialog" title="دورة / ورشة جديدة" :max-width="440">
      <div class="space-y-3">
        <BaseInput v-model="newCourse.title" label="العنوان" />
        <BaseInput v-model="newCourse.skill" label="المهارة المستهدفة" />
        <div class="rounded-ui inline-flex overflow-hidden border-ui">
          <button
            class="px-4 py-2 text-sm transition"
            :class="newCourse.kind === 'ورشة عمل' ? 'bg-accent text-on-accent' : 'text-muted hover:bg-surfalt'"
            @click="newCourse.kind = 'ورشة عمل'"
          >ورشة عمل</button>
          <button
            class="px-4 py-2 text-sm transition"
            :class="newCourse.kind === 'دورة مكثفة' ? 'bg-accent text-on-accent' : 'text-muted hover:bg-surfalt'"
            @click="newCourse.kind = 'دورة مكثفة'"
          >دورة مكثفة</button>
        </div>
        <div class="flex gap-2">
          <BaseInput v-model.number="newCourse.price" type="number" label="السعر (ر.س)" class="flex-1" />
          <BaseInput v-model.number="newCourse.seats" type="number" label="المقاعد" class="flex-1" />
        </div>
      </div>
      <template #actions>
        <BaseButton variant="ghost" @click="courseDialog = false">إلغاء</BaseButton>
        <BaseButton variant="accent" :disabled="!newCourse.title.trim()" @click="saveCourse">إنشاء</BaseButton>
      </template>
    </BaseModal>

    <!-- تسجيل متدرب -->
    <BaseModal v-model="enrollDialog" :title="`تسجيل ${enrolling?.name ?? ''}`" :max-width="420">
      <BaseSelect
        v-model="chosenCourseId"
        :items="store.state.courses.filter(c => c.status === 'open' && c.enrolled < c.seats).map(c => ({ value: c.id, title: `${c.title} (${c.price} ر.س)` }))"
        placeholder="اختر الدورة"
      />
      <template #actions>
        <BaseButton variant="ghost" @click="enrollDialog = false">إلغاء</BaseButton>
        <BaseButton variant="accent" :disabled="chosenCourseId === null" @click="doEnroll">تسجيل</BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar :model-value="!!snackbar" color="success" :timeout="3000" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </BaseSnackbar>
  </div>
</template>
