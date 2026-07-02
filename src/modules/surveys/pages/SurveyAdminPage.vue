<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import type { SurveyStatus } from '@/stores/SurveysStore'
import { GENDER_META, STATUS_TRANSITIONS, SURVEY_REGIONS, SURVEY_STATUS_META, useSurveysStore } from '@/stores/SurveysStore'
import { useNotificationsStore } from '@/stores/NotificationsStore'

// ===== مركز إدارة الاستبيان — الدورة الإدارية الكاملة =====
const route = useRoute()
const router = useRouter()
const store = useSurveysStore()
const notifications = useNotificationsStore()

const survey = computed(() => store.byId(Number(route.params.id)))
const admin = computed(() => store.adminFor(Number(route.params.id)))
const stats = computed(() => store.statsFor(Number(route.params.id)))

const snackbar = ref('')

// —— دورة الحالة ——
const LIFECYCLE: SurveyStatus[] = ['draft', 'scheduled', 'active', 'paused', 'closed', 'archived']
const allowedNext = computed(() => (survey.value ? STATUS_TRANSITIONS[survey.value.status] : []))
function transition(to: SurveyStatus) {
  if (!survey.value)
    return
  const ok = store.setStatus(survey.value.id, to)
  snackbar.value = ok ? `انتقل الاستبيان إلى «${SURVEY_STATUS_META[to].label}»` : 'انتقال غير مسموح في دورة الحياة'
}

// —— استيراد المستبينين (شيت CSV) ——
const importDialog = ref(false)
const importSource = ref<'internal' | 'external'>('external')
const csvText = ref('')
const fileError = ref('')

/** يقرأ صفوف CSV: عمودان (اسم، بريد/جوال) بفواصل أو فواصل منقوطة أو Tab */
function parseCsv(text: string): { name: string, contact: string }[] {
  return text
    .split(/\r?\n/)
    .map(line => line.split(/[,;\t]/).map(c => c.trim()))
    .filter(cols => cols.length >= 2 && cols[0] && cols[1])
    .filter(cols => !/^(الاسم|name)$/i.test(cols[0])) // تخطي صف العناوين
    .map(cols => ({ name: cols[0], contact: cols[1] }))
}

function onFile(e: Event) {
  fileError.value = ''
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file)
    return
  if (!/\.csv$|\.txt$/i.test(file.name)) {
    fileError.value = 'ملفات Excel: صدّر الشيت بصيغة CSV أولًا (حفظ باسم ← CSV) ثم ارفعه هنا.'
    return
  }
  const reader = new FileReader()
  reader.onload = () => (csvText.value = String(reader.result ?? ''))
  reader.readAsText(file)
}

function doImport() {
  if (!survey.value)
    return
  const rows = parseCsv(csvText.value)
  const added = store.importInvitees(survey.value.id, rows, importSource.value)
  importDialog.value = false
  csvText.value = ''
  snackbar.value = added
    ? `استُورد ${added} مستبينًا (${importSource.value === 'internal' ? 'من داخل المنصة' : 'من خارج المنصة'})`
    : 'لا صفوف صالحة — تأكد من عمودَي الاسم وجهة الاتصال'
}

function downloadTemplate() {
  const csv = 'الاسم,البريد أو الجوال\nسارة العتيبي,sara@mail.com\nمحمد الحارثي,0551234567\n'
  const a = document.createElement('a')
  a.href = URL.createObjectURL(new Blob([`﻿${csv}`], { type: 'text/csv;charset=utf-8' }))
  a.download = 'respondents-template.csv'
  a.click()
}

function sendInvites() {
  if (!survey.value)
    return
  const n = store.inviteAll(survey.value.id)
  snackbar.value = n ? `أُرسلت ${n} دعوة — تابع الاستجابات هنا` : 'لا مدعوين معلّقين'
}

const INVITEE_STATUS_META = {
  pending: { label: 'معلّق', color: 'warning' },
  invited: { label: 'دُعي', color: 'info' },
  responded: { label: 'استجاب', color: 'success' },
} as const

function saved() {
  notifications.push({
    icon: 'mdi-content-save-check-outline',
    color: 'success',
    title: 'حُفظت إعدادات الاستبيان',
    body: `«${survey.value?.title}» — الاستهداف والجدولة والحوافز محدّثة.`,
    category: 'system',
  })
}
</script>

<template>
  <div v-if="survey && admin">
    <PageHeader
      :title="`إدارة: ${survey.title}`"
      :subtitle="`${survey.type} · أُنشئ ${survey.createdAt}`"
      icon="mdi-cog-transfer-outline"
    >
      <template #actions>
        <VBtn variant="text" color="primary" prepend-icon="mdi-chart-box-outline" :to="{ name: 'survey-analysis', params: { id: survey.id } }">التحليل</VBtn>
        <VBtn variant="tonal" color="secondary" prepend-icon="mdi-arrow-right" :to="{ name: 'surveys' }">استبياناتي</VBtn>
      </template>
    </PageHeader>

    <!-- دورة الحالة -->
    <VCard class="pa-5 mb-4">
      <div class="d-flex align-center ga-2 mb-3 flex-wrap">
        <VIcon icon="mdi-sync-circle" color="primary" />
        <h2 class="text-subtitle-1 font-weight-bold">دورة الحياة</h2>
        <VChip :color="SURVEY_STATUS_META[survey.status].color" label :prepend-icon="SURVEY_STATUS_META[survey.status].icon">
          {{ SURVEY_STATUS_META[survey.status].label }}
        </VChip>
      </div>
      <div class="d-flex align-center flex-wrap ga-1 mb-3">
        <template v-for="(st, i) in LIFECYCLE" :key="st">
          <VChip
            size="small"
            :color="survey.status === st ? SURVEY_STATUS_META[st].color : undefined"
            :variant="survey.status === st ? 'flat' : 'outlined'"
            label
            :prepend-icon="SURVEY_STATUS_META[st].icon"
          >
            {{ SURVEY_STATUS_META[st].label }}
          </VChip>
          <VIcon v-if="i < LIFECYCLE.length - 1" icon="mdi-chevron-left" size="16" color="medium-emphasis" />
        </template>
      </div>
      <div class="d-flex ga-2 flex-wrap">
        <VBtn
          v-for="to in allowedNext"
          :key="to"
          size="small"
          :color="SURVEY_STATUS_META[to].color"
          variant="tonal"
          :prepend-icon="SURVEY_STATUS_META[to].icon"
          @click="transition(to)"
        >
          {{ to === 'active' && survey.status === 'closed' ? 'إعادة فتح' : SURVEY_STATUS_META[to].label }}
        </VBtn>
        <span v-if="!allowedNext.length" class="text-caption text-medium-emphasis">الأرشيف نهاية الدورة — انسخ الاستبيان لبدء دورة جديدة.</span>
      </div>
      <VAlert v-if="survey.status === 'scheduled'" color="info" variant="tonal" density="compact" border="start" class="mt-3 text-caption">
        سيُفعَّل تلقائيًا في {{ survey.settings.startsAt ?? 'موعد البداية' }} — ويُغلق تلقائيًا عند انتهاء الموعد أو اكتمال الحصة أو استنفاد المجمع.
      </VAlert>
    </VCard>

    <!-- مؤشرات الإدارة -->
    <VRow class="mb-1">
      <VCol cols="6" md="3">
        <VCard class="pa-4">
          <div class="text-caption text-medium-emphasis mb-1">حصة المستبينين</div>
          <div class="text-h6 font-weight-bold">{{ admin.quota.used }}<span v-if="admin.quota.limit" class="text-body-2 text-medium-emphasis"> / {{ admin.quota.limit }}</span></div>
          <VProgressLinear v-if="admin.quota.pct !== null" :model-value="admin.quota.pct" color="primary" height="6" rounded class="mt-1" />
          <div v-else class="text-caption text-medium-emphasis">بلا حد</div>
        </VCard>
      </VCol>
      <VCol cols="6" md="3">
        <VCard class="pa-4">
          <div class="text-caption text-medium-emphasis mb-1">مجمع النقاط</div>
          <div class="text-h6 font-weight-bold">{{ admin.budget.spent }}<span v-if="admin.budget.total" class="text-body-2 text-medium-emphasis"> / {{ admin.budget.total }}</span></div>
          <VProgressLinear v-if="admin.budget.pct !== null" :model-value="admin.budget.pct" :color="admin.budget.pct > 85 ? 'error' : 'warning'" height="6" rounded class="mt-1" />
          <div v-else class="text-caption text-medium-emphasis">بلا سقف</div>
        </VCard>
      </VCol>
      <VCol cols="6" md="3">
        <VCard class="pa-4">
          <div class="text-caption text-medium-emphasis mb-1">نسبة الإكمال</div>
          <div class="text-h6 font-weight-bold">{{ stats.completion }}%</div>
          <div class="text-caption text-medium-emphasis">{{ stats.internal }} داخلي · {{ stats.external }} خارجي</div>
        </VCard>
      </VCol>
      <VCol cols="6" md="3">
        <VCard class="pa-4">
          <div class="text-caption text-medium-emphasis mb-1">المهلة</div>
          <div class="text-h6 font-weight-bold">{{ admin.daysLeft !== null ? `${admin.daysLeft} يومًا` : 'مفتوحة' }}</div>
          <div class="text-caption text-medium-emphasis">{{ survey.settings.closesAt ? `تُغلق ${survey.settings.closesAt}` : 'بلا موعد إغلاق' }}</div>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <!-- الجدولة والحدود والحوافز -->
      <VCol cols="12" md="5">
        <VCard class="pa-5 mb-4">
          <h2 class="text-subtitle-1 font-weight-bold mb-3"><VIcon icon="mdi-calendar-range-outline" size="20" color="info" class="me-1" />الجدولة والحدود</h2>
          <VRow dense>
            <VCol cols="6"><VTextField v-model="survey.settings.startsAt" type="date" label="بداية المشاركة" density="compact" clearable @change="saved" /></VCol>
            <VCol cols="6"><VTextField v-model="survey.settings.closesAt" type="date" label="نهاية المشاركة" density="compact" clearable @change="saved" /></VCol>
            <VCol cols="12">
              <VTextField v-model.number="survey.settings.responseLimit" type="number" label="حد عدد المستبينين" placeholder="بلا حد" density="compact" clearable @change="saved" />
            </VCol>
          </VRow>
          <VAlert color="secondary" variant="tonal" density="compact" border="start" class="text-caption">
            عند بلوغ الحد أو انتهاء الموعد يُغلق الاستبيان تلقائيًا ويصلك إشعار.
          </VAlert>
        </VCard>

        <VCard class="pa-5 mb-4">
          <h2 class="text-subtitle-1 font-weight-bold mb-3"><VIcon icon="mdi-gift-outline" size="20" color="warning" class="me-1" />الحوافز</h2>
          <VRow dense>
            <VCol cols="6"><VTextField v-model.number="survey.settings.rewardPoints" type="number" label="نقاط لكل مشارك" density="compact" @change="saved" /></VCol>
            <VCol cols="6"><VTextField v-model.number="survey.settings.rewardBudget" type="number" label="مجمع النقاط (حد القيمة)" placeholder="بلا سقف" density="compact" clearable @change="saved" /></VCol>
          </VRow>
          <p class="text-caption text-medium-emphasis mb-0">
            المصروف حتى الآن: <b>{{ survey.rewardsSpent }}</b> نقطة
            <template v-if="survey.settings.rewardBudget"> — المتبقي {{ Math.max(0, survey.settings.rewardBudget - survey.rewardsSpent) }} تكفي
              {{ survey.settings.rewardPoints ? Math.floor(Math.max(0, survey.settings.rewardBudget - survey.rewardsSpent) / survey.settings.rewardPoints) : '∞' }} مشاركًا.</template>
          </p>
        </VCard>

        <VCard class="pa-5">
          <h2 class="text-subtitle-1 font-weight-bold mb-3"><VIcon icon="mdi-target-account" size="20" color="accent" class="me-1" />الاستهداف</h2>
          <VSelect
            v-model="survey.targeting.regions"
            :items="SURVEY_REGIONS"
            label="النطاق الجغرافي"
            multiple chips closable-chips clearable
            placeholder="كل المناطق"
            density="compact"
            class="mb-2"
            @update:model-value="saved"
          />
          <VBtnToggle v-model="survey.targeting.gender" mandatory color="accent" variant="outlined" density="compact" class="mb-3 w-100" @update:model-value="saved">
            <VBtn v-for="(label, g) in GENDER_META" :key="g" :value="g" size="small" class="flex-grow-1">{{ label }}</VBtn>
          </VBtnToggle>
          <VRow dense>
            <VCol cols="6"><VTextField v-model.number="survey.targeting.ageMin" type="number" label="العمر من" placeholder="بلا حد" density="compact" clearable @change="saved" /></VCol>
            <VCol cols="6"><VTextField v-model.number="survey.targeting.ageMax" type="number" label="العمر إلى" placeholder="بلا حد" density="compact" clearable @change="saved" /></VCol>
          </VRow>
          <p class="text-caption text-medium-emphasis mb-0">يظهر الاستبيان في «استبيانات للمشاركة» للجمهور المطابق، ويُعرض الاستهداف في صفحة الإجابة.</p>
        </VCard>
      </VCol>

      <!-- المستبينون -->
      <VCol cols="12" md="7">
        <VCard class="pa-5">
          <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-1">
            <h2 class="text-subtitle-1 font-weight-bold"><VIcon icon="mdi-account-multiple-check-outline" size="20" color="primary" class="me-1" />المستبينون ({{ admin.invitees.total }})</h2>
            <div class="d-flex ga-2">
              <VBtn size="small" variant="tonal" color="secondary" prepend-icon="mdi-file-delimited-outline" @click="importDialog = true">استيراد شيت</VBtn>
              <VBtn size="small" color="primary" variant="flat" prepend-icon="mdi-email-fast-outline" :disabled="!admin.invitees.pending || survey.status !== 'active'" @click="sendInvites">
                دعوة المعلّقين ({{ admin.invitees.pending }})
              </VBtn>
            </div>
          </div>

          <!-- قُمع الدعوات -->
          <div class="d-flex ga-2 mb-3 flex-wrap">
            <VChip size="small" color="warning" variant="tonal" label>معلّق: {{ admin.invitees.pending }}</VChip>
            <VChip size="small" color="info" variant="tonal" label>دُعي: {{ admin.invitees.invited }}</VChip>
            <VChip size="small" color="success" variant="tonal" label>استجاب: {{ admin.invitees.responded }}</VChip>
          </div>

          <VTable v-if="survey.invitees.length" density="compact">
            <thead>
              <tr>
                <th class="text-start">الاسم</th>
                <th class="text-start">جهة الاتصال</th>
                <th class="text-start">المصدر</th>
                <th class="text-start">الحالة</th>
                <th />
              </tr>
            </thead>
            <tbody>
              <tr v-for="i in survey.invitees" :key="i.id">
                <td class="font-weight-bold">{{ i.name }}</td>
                <td dir="ltr" class="text-start">{{ i.contact }}</td>
                <td><VChip size="x-small" :color="i.source === 'internal' ? 'primary' : 'secondary'" variant="tonal" label>{{ i.source === 'internal' ? 'داخل المنصة' : 'خارجها' }}</VChip></td>
                <td><VChip size="x-small" :color="INVITEE_STATUS_META[i.status].color" label>{{ INVITEE_STATUS_META[i.status].label }}</VChip></td>
                <td><VBtn icon="mdi-delete-outline" size="x-small" variant="text" color="error" @click="store.removeInvitee(survey.id, i.id)" /></td>
              </tr>
            </tbody>
          </VTable>
          <div v-else class="pa-6 text-center text-medium-emphasis">
            <VIcon icon="mdi-account-multiple-plus-outline" size="40" />
            <p class="text-body-2 mt-1 mb-0">استورد قائمة مستبينين من شيت CSV/Excel — من داخل المنصة أو خارجها.</p>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- استيراد شيت -->
    <VDialog v-model="importDialog" max-width="560">
      <VCard class="pa-2">
        <VCardTitle>استيراد المستبينين من شيت</VCardTitle>
        <VCardText>
          <VBtnToggle v-model="importSource" mandatory color="primary" variant="outlined" divided class="mb-3 w-100">
            <VBtn value="internal" class="flex-grow-1" prepend-icon="mdi-account-check-outline">من داخل المنصة</VBtn>
            <VBtn value="external" class="flex-grow-1" prepend-icon="mdi-account-arrow-left-outline">من خارج المنصة</VBtn>
          </VBtnToggle>
          <VFileInput label="ملف CSV (من Excel: حفظ باسم ← CSV)" accept=".csv,.txt" density="compact" prepend-icon="mdi-file-delimited-outline" @change="onFile" />
          <VAlert v-if="fileError" color="warning" variant="tonal" density="compact" border="start" class="mb-2 text-caption">{{ fileError }}</VAlert>
          <VTextarea v-model="csvText" label="أو الصق الصفوف مباشرة" rows="5" placeholder="الاسم,البريد أو الجوال&#10;سارة العتيبي,sara@mail.com&#10;محمد الحارثي,0551234567" dir="ltr" />
          <div class="d-flex align-center justify-space-between">
            <VBtn size="x-small" variant="text" color="secondary" prepend-icon="mdi-download-outline" @click="downloadTemplate">تحميل قالب جاهز</VBtn>
            <span class="text-caption text-medium-emphasis">{{ parseCsv(csvText).length }} صفًا صالحًا</span>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="importDialog = false">إلغاء</VBtn>
          <VBtn color="primary" variant="flat" :disabled="!parseCsv(csvText).length" prepend-icon="mdi-import" @click="doImport">استيراد</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar :model-value="!!snackbar" timeout="2600" color="secondary" @update:model-value="snackbar = ''">{{ snackbar }}</VSnackbar>
  </div>

  <VCard v-else class="pa-8 text-center ma-4">
    <VIcon icon="mdi-help-circle-outline" size="48" color="medium-emphasis" class="mb-2" />
    <p class="text-body-1">الاستبيان غير موجود.</p>
    <VBtn color="primary" variant="tonal" :to="{ name: 'surveys' }">عودة لاستبياناتي</VBtn>
  </VCard>
</template>
