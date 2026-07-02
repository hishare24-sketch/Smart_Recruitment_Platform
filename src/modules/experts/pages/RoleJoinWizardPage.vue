<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import type { UserRole } from '@/interfaces/Auth'
import { ROLE_META, roleHome } from '@/services/roles'
import { useAuthStore } from '@/stores/AuthStore'
import { useGamificationStore } from '@/stores/GamificationStore'
import { useNotificationsStore } from '@/stores/NotificationsStore'

// ===== مسار الانضمام للأدوار — القبول تلقائي بعد اكتمال المسار =====
// الديمو: كل الخطوات معبأة افتراضيًا لفهم السياق؛ التحقق الفعلي من البيانات
// والمرفقات والتوصيات يُنفَّذ برمجيًا مع الباك-إند لاحقًا.

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()

const role = computed(() => String(route.params.role) as UserRole)
const meta = computed(() => ROLE_META[role.value])
const roleLabel = computed(() => t(`roles.${role.value}`))

const step = ref(1)

// —— الخطوة 1: البيانات المهنية (معبأة افتراضيًا حسب الدور) ——
const PRESETS: Record<string, { headline: string, years: number, specialties: string[], bio: string }> = {
  coach: { headline: 'مرشد مسارات تقنية ومهنية', years: 7, specialties: ['تخطيط المسار المهني', 'تحسين السير الذاتية', 'المهارات الناعمة'], bio: 'رافقت أكثر من 100 محترف في تحولاتهم المهنية، من أول وظيفة إلى الأدوار القيادية.' },
  trainer: { headline: 'مدرب تطوير واجهات وأدوات حديثة', years: 6, specialties: ['Vue.js', 'TypeScript', 'اختبارات البرمجيات'], bio: 'صممت وقدمت ورشًا مكثفة لأكثر من 400 متدرب بمعدل رضا 4.8.' },
  consultant: { headline: 'مستشار استقطاب وهيكلة فرق تقنية', years: 10, specialties: ['اتجاهات سوق العمل', 'هيكلة الفرق', 'استراتيجيات الرواتب'], bio: 'قدت مشاريع استشارية لشركات متوسطة وكبرى في بناء فرقها التقنية.' },
}
const form = ref({ ...(PRESETS[role.value] ?? PRESETS.coach) })

// —— الخطوة 2: المرفقات (نماذج افتراضية + إضافة حقيقية) ——
const attachments = ref([
  { name: 'شهادة-اعتماد-مهني.pdf', size: '1.2 MB', icon: 'mdi-certificate-outline' },
  { name: 'السيرة-الذاتية-المهنية.pdf', size: '640 KB', icon: 'mdi-file-account-outline' },
  { name: 'نموذج-أعمال-سابقة.zip', size: '4.8 MB', icon: 'mdi-folder-zip-outline' },
])
const fileInput = ref<HTMLInputElement | null>(null)
function addFiles(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (!files)
    return
  for (const f of files)
    attachments.value.push({ name: f.name, size: `${Math.max(1, Math.round(f.size / 1024))} KB`, icon: 'mdi-paperclip' })
}
function removeAttachment(i: number) {
  attachments.value.splice(i, 1)
}

// —— الخطوة 3: التوصيات والتزكيات (معبأة افتراضيًا) ——
const endorsements = ref([
  { name: 'أحمد المنصور', relation: 'مدير سابق', text: 'من أكفأ من عملت معهم — أرشحه بلا تردد لهذا الدور.', verified: true },
  { name: 'سارة العتيبي', relation: 'زميلة مهنية', text: 'خبرة عملية عميقة وقدرة نادرة على نقل المعرفة.', verified: true },
])
const newEndorsement = ref({ name: '', relation: '', text: '' })
function addEndorsement() {
  if (!newEndorsement.value.name.trim() || !newEndorsement.value.text.trim())
    return
  endorsements.value.push({ ...newEndorsement.value, verified: false })
  newEndorsement.value = { name: '', relation: '', text: '' }
}

// —— الخطوة 4: الإقرار والاعتماد التلقائي ——
const agreed = ref(false)
const canFinish = computed(() =>
  agreed.value
  && form.value.headline.trim().length > 0
  && form.value.specialties.length > 0
  && attachments.value.length >= 1
  && endorsements.value.length >= 1,
)
const requirements = computed(() => [
  { label: 'البيانات المهنية مكتملة', ok: !!form.value.headline.trim() && form.value.specialties.length > 0 },
  { label: 'مرفق واحد على الأقل (شهادة/سيرة)', ok: attachments.value.length >= 1 },
  { label: 'توصية أو تزكية واحدة على الأقل', ok: endorsements.value.length >= 1 },
  { label: 'الإقرار بصحة البيانات', ok: agreed.value },
])

const done = ref(false)
function finish() {
  if (!canFinish.value)
    return
  // أرشفة ملف الانضمام — يجهز التحقق البرمجي الحقيقي لاحقًا (backend)
  const applications = JSON.parse(localStorage.getItem('roleApplications') ?? '{}')
  applications[role.value] = {
    form: { ...form.value },
    attachments: attachments.value.map(a => a.name),
    endorsements: endorsements.value,
    submittedAt: new Date().toISOString(),
    decision: 'auto-accepted',
  }
  localStorage.setItem('roleApplications', JSON.stringify(applications))

  // القبول التلقائي بعد اكتمال المسار
  authStore.requestRole(role.value)
  authStore.activateRole(role.value)
  authStore.switchRole(role.value)
  useGamificationStore().record('roleActivated', `اكتمل مسار انضمامك — فُعّل دور ${roleLabel.value}`)
  useNotificationsStore().push({
    icon: 'mdi-shield-check-outline',
    color: 'success',
    title: `قُبلت تلقائيًا — أهلًا بك ${roleLabel.value}`,
    body: 'اكتمل مسار الانضمام (بيانات + مرفقات + تزكيات) فاعتُمد دورك فورًا.',
    category: 'system',
  })
  done.value = true
  setTimeout(() => router.push({ name: roleHome(role.value) }), 1500)
}
</script>

<template>
  <div class="mx-auto" style="max-width: 780px">
    <PageHeader
      :title="`الانضمام كـ${roleLabel}`"
      subtitle="القبول تلقائي فور اكتمال مسار الانضمام — البيانات معبأة كنموذج توضيحي"
      :icon="meta.icon"
    />

    <VStepper v-model="step" :items="['البيانات المهنية', 'المرفقات', 'التوصيات والتزكيات', 'الإقرار والاعتماد']" hide-actions alt-labels class="mb-4">
      <!-- 1: البيانات -->
      <template #item.1>
        <VCard class="pa-5" flat>
          <VAlert color="secondary" variant="tonal" density="compact" border="start" class="mb-4 text-caption">
            نموذج توضيحي: الحقول معبأة افتراضيًا لتوضيح المطلوب — عدّلها بحرية. التحقق الفعلي يتم لاحقًا.
          </VAlert>
          <VTextField v-model="form.headline" label="العنوان المهني" prepend-inner-icon="mdi-briefcase-outline" class="mb-3" />
          <VSlider v-model="form.years" :min="1" :max="25" :step="1" label="سنوات الخبرة" thumb-label="always" color="primary" class="mb-3" />
          <VCombobox v-model="form.specialties" label="التخصصات" multiple chips closable-chips prepend-inner-icon="mdi-star-outline" class="mb-3" />
          <VTextarea v-model="form.bio" label="نبذة مهنية" rows="3" auto-grow />
          <div class="d-flex justify-end mt-2">
            <VBtn color="primary" append-icon="mdi-chevron-left" @click="step = 2">التالي</VBtn>
          </div>
        </VCard>
      </template>

      <!-- 2: المرفقات -->
      <template #item.2>
        <VCard class="pa-5" flat>
          <p class="text-caption text-medium-emphasis mb-3">أرفق ما يثبت خبرتك (شهادات، سيرة، نماذج أعمال) — أُضيفت نماذج افتراضية للتوضيح.</p>
          <div v-for="(a, i) in attachments" :key="a.name + i" class="d-flex align-center ga-3 py-2">
            <VAvatar color="info" variant="tonal" size="36" rounded="lg"><VIcon :icon="a.icon" size="18" /></VAvatar>
            <div class="flex-grow-1">
              <div class="text-body-2 font-weight-bold">{{ a.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ a.size }}</div>
            </div>
            <VBtn icon="mdi-delete-outline" size="x-small" variant="text" color="error" @click="removeAttachment(i)" />
          </div>
          <input ref="fileInput" type="file" multiple class="d-none" @change="addFiles">
          <VBtn variant="tonal" color="secondary" size="small" prepend-icon="mdi-paperclip" class="mt-2" @click="fileInput?.click()">إضافة مرفق</VBtn>
          <div class="d-flex justify-space-between mt-4">
            <VBtn variant="text" prepend-icon="mdi-chevron-right" @click="step = 1">السابق</VBtn>
            <VBtn color="primary" append-icon="mdi-chevron-left" @click="step = 3">التالي</VBtn>
          </div>
        </VCard>
      </template>

      <!-- 3: التوصيات -->
      <template #item.3>
        <VCard class="pa-5" flat>
          <p class="text-caption text-medium-emphasis mb-3">توصيات وتزكيات من زملاء أو مدراء تدعم طلبك — نموذجان افتراضيان موثقان للتوضيح.</p>
          <VCard v-for="(e, i) in endorsements" :key="i" variant="outlined" class="pa-3 mb-2">
            <div class="d-flex align-center ga-2 mb-1">
              <VAvatar color="secondary" variant="tonal" size="30"><span class="text-caption font-weight-bold">{{ e.name.charAt(0) }}</span></VAvatar>
              <span class="text-body-2 font-weight-bold">{{ e.name }}</span>
              <span class="text-caption text-medium-emphasis">— {{ e.relation }}</span>
              <VChip v-if="e.verified" size="x-small" color="success" variant="tonal" label class="ms-auto" prepend-icon="mdi-check-decagram">موثقة</VChip>
            </div>
            <p class="text-body-2 mb-0">«{{ e.text }}»</p>
          </VCard>
          <VDivider class="my-3" />
          <div class="text-body-2 font-weight-bold mb-2">أضف تزكية</div>
          <div class="d-flex ga-2 mb-2 flex-wrap">
            <VTextField v-model="newEndorsement.name" label="الاسم" density="compact" hide-details style="min-width: 160px" />
            <VTextField v-model="newEndorsement.relation" label="العلاقة" density="compact" hide-details style="min-width: 160px" />
          </div>
          <VTextarea v-model="newEndorsement.text" label="نص التزكية" rows="2" density="compact" hide-details class="mb-2" />
          <VBtn size="small" variant="tonal" color="secondary" prepend-icon="mdi-plus" :disabled="!newEndorsement.name.trim() || !newEndorsement.text.trim()" @click="addEndorsement">إضافة</VBtn>
          <div class="d-flex justify-space-between mt-4">
            <VBtn variant="text" prepend-icon="mdi-chevron-right" @click="step = 2">السابق</VBtn>
            <VBtn color="primary" append-icon="mdi-chevron-left" @click="step = 4">التالي</VBtn>
          </div>
        </VCard>
      </template>

      <!-- 4: الإقرار والاعتماد التلقائي -->
      <template #item.4>
        <VCard class="pa-5" flat>
          <h3 class="text-subtitle-1 font-weight-bold mb-3">شروط القبول التلقائي</h3>
          <div v-for="r in requirements" :key="r.label" class="d-flex align-center ga-2 py-1">
            <VIcon :icon="r.ok ? 'mdi-check-circle' : 'mdi-circle-outline'" :color="r.ok ? 'success' : 'medium-emphasis'" size="20" />
            <span class="text-body-2" :class="{ 'text-medium-emphasis': !r.ok }">{{ r.label }}</span>
          </div>
          <VCheckbox v-model="agreed" class="mt-2" hide-details>
            <template #label>
              <span class="text-body-2">أقرّ بصحة البيانات والمرفقات، وأتحمل مسؤولية دقتها أمام المنصة والعملاء.</span>
            </template>
          </VCheckbox>
          <VAlert v-if="canFinish" color="success" variant="tonal" density="compact" border="start" class="my-3 text-caption">
            اكتملت الشروط — سيُعتمد دورك تلقائيًا فور الإرسال.
          </VAlert>
          <div class="d-flex justify-space-between mt-3">
            <VBtn variant="text" prepend-icon="mdi-chevron-right" @click="step = 3">السابق</VBtn>
            <VBtn color="accent" size="large" prepend-icon="mdi-shield-check-outline" :disabled="!canFinish" @click="finish">
              إرسال واعتماد تلقائي
            </VBtn>
          </div>
        </VCard>
      </template>
    </VStepper>

    <VSnackbar :model-value="done" color="success" timeout="1600">
      قُبلت تلقائيًا! جارٍ تحويلك للوحة {{ roleLabel }}...
    </VSnackbar>
  </div>
</template>
