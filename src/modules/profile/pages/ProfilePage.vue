<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/AuthStore'
import { useProfileStore } from '@/stores/ProfileStore'
import { useResumesStore } from '@/stores/ResumesStore'

const authStore = useAuthStore()
const profile = useProfileStore()
const resumesStore = useResumesStore()
const user = computed(() => authStore.authUser)
const tab = ref('skills')

// Add-skill dialog
const skillDialog = ref(false)
const newSkillName = ref('')
const newSkillLevel = ref(3)
function saveSkill() {
  profile.addSkill(newSkillName.value, newSkillLevel.value)
  newSkillName.value = ''
  newSkillLevel.value = 3
  skillDialog.value = false
}

// Add-experience dialog
const expDialog = ref(false)
const newExp = ref({ title: '', company: '', period: '', desc: '' })
function saveExp() {
  profile.addExperience({ ...newExp.value })
  newExp.value = { title: '', company: '', period: '', desc: '' }
  expDialog.value = false
}

// Add-certificate dialog
const certDialog = ref(false)
const newCert = ref({ name: '', issuer: '', date: '' })
function saveCert() {
  profile.addCertificate({ ...newCert.value })
  newCert.value = { name: '', issuer: '', date: '' }
  certDialog.value = false
}

const endorsements = [
  { name: 'أحمد المنصور', relation: 'مدير سابق', type: 'نص', trusted: true },
  { name: 'سارة العتيبي', relation: 'زميلة', type: 'فيديو', trusted: false },
]
const privacySettings = ref([
  { label: 'ظهور الملف الشخصي', value: 'public' },
  { label: 'ظهور التوصيات', value: 'companies' },
  { label: 'ظهور نتائج الاختبارات', value: 'private' },
  { label: 'ظهور الرغبات الواردة', value: 'private' },
  { label: 'ظهور السير الذاتية', value: 'public' },
  { label: 'إشعارات التواصل', value: 'public' },
  { label: 'مشاركة البيانات للتحليل', value: 'public' },
])
const privacyOptions = [
  { value: 'public', title: 'عام' },
  { value: 'companies', title: 'لأصحاب العمل' },
  { value: 'private', title: 'خاص' },
]

const initials = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '?')
const profileCompletion = computed(() => {
  let score = 40
  if (profile.skills.length >= 3)
    score += 20
  if (profile.experiences.length >= 1)
    score += 20
  if (profile.certificates.length >= 1)
    score += 20
  return Math.min(score, 100)
})
</script>

<template>
  <div>
    <!-- Header card -->
    <VCard class="mb-5 overflow-hidden">
      <div class="brand-gradient" style="height: 110px" />
      <VCardText class="pt-0">
        <div class="d-flex align-end flex-wrap ga-4" style="margin-top: -48px">
          <VAvatar color="secondary" size="96" style="border: 4px solid white">
            <span class="text-h4 text-white font-weight-bold">{{ initials }}</span>
          </VAvatar>
          <div class="flex-grow-1 pb-2">
            <h1 class="text-h5 font-weight-bold">{{ user?.name }}</h1>
            <div class="text-body-2 text-medium-emphasis">{{ profile.headline }}</div>
          </div>
          <div class="d-flex ga-2 pb-2">
            <VBtn color="primary" variant="outlined" prepend-icon="mdi-share-variant-outline" :to="{ name: 'public-resume', params: { token: 'me' } }">
              مشاركة الملف
            </VBtn>
            <VBtn color="accent" prepend-icon="mdi-pencil">تعديل</VBtn>
          </div>
        </div>

        <!-- Completion bar -->
        <div class="mt-4">
          <div class="d-flex justify-space-between text-caption mb-1">
            <span class="text-medium-emphasis">اكتمال الملف</span>
            <span class="font-weight-bold text-primary">{{ profileCompletion }}%</span>
          </div>
          <VProgressLinear :model-value="profileCompletion" color="success" height="8" rounded />
        </div>

        <p class="text-body-2 text-medium-emphasis mt-4 mb-0" style="max-width: 700px">{{ profile.summary }}</p>
      </VCardText>
    </VCard>

    <VTabs v-model="tab" color="primary" class="mb-4" show-arrows>
      <VTab value="skills" prepend-icon="mdi-star-outline">المهارات</VTab>
      <VTab value="experience" prepend-icon="mdi-briefcase-outline">الخبرات</VTab>
      <VTab value="certificates" prepend-icon="mdi-certificate-outline">الشهادات</VTab>
      <VTab value="endorsements" prepend-icon="mdi-account-star-outline">التوصيات</VTab>
      <VTab value="resumes" prepend-icon="mdi-file-account-outline">السير الذاتية</VTab>
      <VTab value="privacy" prepend-icon="mdi-shield-lock-outline">الخصوصية</VTab>
    </VTabs>

    <VWindow v-model="tab">
      <!-- Skills -->
      <VWindowItem value="skills">
        <VCard class="pa-5">
          <div class="d-flex justify-space-between mb-4">
            <h3 class="text-subtitle-1 font-weight-bold">المهارات ({{ profile.skills.length }})</h3>
            <VBtn color="accent" size="small" prepend-icon="mdi-plus" @click="skillDialog = true">إضافة مهارة</VBtn>
          </div>
          <div v-for="skill in profile.skills" :key="skill.id" class="mb-3">
            <div class="d-flex justify-space-between align-center mb-1">
              <span class="text-body-2 font-weight-medium">{{ skill.name }}</span>
              <div class="d-flex align-center ga-2">
                <VRating :model-value="skill.level" color="accent" density="compact" size="small" readonly />
                <VBtn icon="mdi-delete-outline" variant="text" size="x-small" color="error" @click="profile.removeSkill(skill.id)" />
              </div>
            </div>
            <VProgressLinear :model-value="skill.level * 20" color="primary" height="6" rounded />
          </div>
          <div v-if="!profile.skills.length" class="text-center text-medium-emphasis py-6">لا مهارات بعد — أضف أول مهارة</div>
        </VCard>
      </VWindowItem>

      <!-- Experience -->
      <VWindowItem value="experience">
        <VCard class="pa-5">
          <div class="d-flex justify-space-between mb-4">
            <h3 class="text-subtitle-1 font-weight-bold">الخبرات العملية ({{ profile.experiences.length }})</h3>
            <VBtn color="accent" size="small" prepend-icon="mdi-plus" @click="expDialog = true">إضافة خبرة</VBtn>
          </div>
          <VTimeline side="end" density="compact" align="start">
            <VTimelineItem v-for="exp in profile.experiences" :key="exp.id" dot-color="primary" size="small">
              <div class="d-flex justify-space-between">
                <div>
                  <div class="text-subtitle-2 font-weight-bold">{{ exp.title }}</div>
                  <div class="text-body-2 text-secondary">{{ exp.company }} · {{ exp.period }}</div>
                  <div class="text-body-2 text-medium-emphasis mt-1">{{ exp.desc }}</div>
                </div>
                <VBtn icon="mdi-delete-outline" variant="text" size="x-small" color="error" @click="profile.removeExperience(exp.id)" />
              </div>
            </VTimelineItem>
          </VTimeline>
        </VCard>
      </VWindowItem>

      <!-- Certificates -->
      <VWindowItem value="certificates">
        <VCard class="pa-5">
          <div class="d-flex justify-space-between mb-4">
            <h3 class="text-subtitle-1 font-weight-bold">الشهادات والدورات ({{ profile.certificates.length }})</h3>
            <VBtn color="accent" size="small" prepend-icon="mdi-plus" @click="certDialog = true">إضافة</VBtn>
          </div>
          <VRow>
            <VCol v-for="cert in profile.certificates" :key="cert.id" cols="12" sm="6">
              <VCard variant="outlined" class="pa-3 d-flex align-center ga-3">
                <VAvatar color="success" variant="tonal" rounded="lg"><VIcon icon="mdi-certificate-outline" /></VAvatar>
                <div class="flex-grow-1">
                  <div class="text-body-2 font-weight-bold">{{ cert.name }}</div>
                  <div class="text-caption text-medium-emphasis">{{ cert.issuer }} · {{ cert.date }}</div>
                </div>
                <VBtn icon="mdi-delete-outline" variant="text" size="x-small" color="error" @click="profile.removeCertificate(cert.id)" />
              </VCard>
            </VCol>
          </VRow>
        </VCard>
      </VWindowItem>

      <!-- Endorsements -->
      <VWindowItem value="endorsements">
        <VCard class="pa-5">
          <div class="d-flex justify-space-between mb-4">
            <h3 class="text-subtitle-1 font-weight-bold">التوصيات والتزكيات</h3>
            <VBtn color="accent" size="small" prepend-icon="mdi-plus">طلب توصية</VBtn>
          </div>
          <VRow>
            <VCol v-for="e in endorsements" :key="e.name" cols="12" sm="6">
              <VCard variant="outlined" class="pa-3">
                <div class="d-flex align-center ga-3">
                  <VAvatar color="secondary" variant="tonal"><VIcon icon="mdi-account" /></VAvatar>
                  <div class="flex-grow-1">
                    <div class="text-body-2 font-weight-bold">
                      {{ e.name }}
                      <VIcon v-if="e.trusted" icon="mdi-check-decagram" color="success" size="16" />
                    </div>
                    <div class="text-caption text-medium-emphasis">{{ e.relation }}</div>
                  </div>
                  <VChip size="x-small" label prepend-icon="mdi-format-quote-close">{{ e.type }}</VChip>
                </div>
              </VCard>
            </VCol>
          </VRow>
        </VCard>
      </VWindowItem>

      <!-- Resumes -->
      <VWindowItem value="resumes">
        <VCard class="pa-5">
          <div class="d-flex justify-space-between mb-4">
            <h3 class="text-subtitle-1 font-weight-bold">السير الذاتية المنشأة ({{ resumesStore.count }})</h3>
            <VBtn color="accent" size="small" prepend-icon="mdi-plus" :to="{ name: 'resume-builder' }">إنشاء سيرة</VBtn>
          </div>
          <VList>
            <VListItem v-for="r in resumesStore.resumes" :key="r.id" class="px-2">
              <template #prepend>
                <VAvatar :color="r.active ? 'success' : 'primary'" variant="tonal" rounded="lg"><VIcon icon="mdi-file-account-outline" /></VAvatar>
              </template>
              <VListItemTitle class="font-weight-bold">
                {{ r.name }}
                <VChip v-if="r.active" color="success" size="x-small" label class="ms-1">نشطة</VChip>
              </VListItemTitle>
              <VListItemSubtitle>{{ r.template }} · {{ r.language }} · أُنشئت {{ r.createdAt }}</VListItemSubtitle>
              <template #append>
                <VBtn v-if="!r.active" variant="tonal" color="success" size="x-small" class="me-1" @click="resumesStore.setActive(r.id)">تعيين كنشطة</VBtn>
                <VBtn icon="mdi-open-in-new" variant="text" size="small" :to="{ name: 'public-resume', params: { token: String(r.id) } }" />
                <VMenu>
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon="mdi-dots-vertical" variant="text" size="small" />
                  </template>
                  <VList density="compact">
                    <VListItem prepend-icon="mdi-pencil" title="تعديل" :to="{ name: 'resume-builder' }" />
                    <VListItem prepend-icon="mdi-file-pdf-box" title="تصدير PDF" />
                    <VListItem prepend-icon="mdi-share-variant" title="مشاركة الرابط" />
                    <VListItem prepend-icon="mdi-delete-outline" title="حذف" base-color="error" @click="resumesStore.remove(r.id)" />
                  </VList>
                </VMenu>
              </template>
            </VListItem>
          </VList>
          <div v-if="!resumesStore.count" class="text-center text-medium-emphasis py-6">لا سير ذاتية بعد — أنشئ أول سيرة</div>
        </VCard>
      </VWindowItem>

      <!-- Privacy -->
      <VWindowItem value="privacy">
        <VCard class="pa-5">
          <h3 class="text-subtitle-1 font-weight-bold mb-4">إعدادات الخصوصية</h3>
          <div v-for="(s, i) in privacySettings" :key="i" class="d-flex align-center justify-space-between flex-wrap ga-2 py-2">
            <span class="text-body-2">{{ s.label }}</span>
            <VBtnToggle v-model="s.value" mandatory density="compact" color="primary" variant="outlined">
              <VBtn v-for="opt in privacyOptions" :key="opt.value" :value="opt.value" size="small">{{ opt.title }}</VBtn>
            </VBtnToggle>
          </div>
        </VCard>
      </VWindowItem>
    </VWindow>

    <!-- Add skill dialog -->
    <VDialog v-model="skillDialog" max-width="420">
      <VCard class="pa-2">
        <VCardTitle>إضافة مهارة</VCardTitle>
        <VCardText>
          <VTextField v-model="newSkillName" label="اسم المهارة" class="mb-3" />
          <div class="text-body-2 mb-1">المستوى</div>
          <VRating v-model="newSkillLevel" color="accent" />
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="skillDialog = false">إلغاء</VBtn>
          <VBtn color="accent" :disabled="!newSkillName.trim()" @click="saveSkill">إضافة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Add experience dialog -->
    <VDialog v-model="expDialog" max-width="480">
      <VCard class="pa-2">
        <VCardTitle>إضافة خبرة</VCardTitle>
        <VCardText>
          <VTextField v-model="newExp.title" label="المسمى الوظيفي" class="mb-2" />
          <VTextField v-model="newExp.company" label="الشركة" class="mb-2" />
          <VTextField v-model="newExp.period" label="الفترة" placeholder="مثال: 2020 - 2023" class="mb-2" />
          <VTextarea v-model="newExp.desc" label="الوصف" rows="2" />
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="expDialog = false">إلغاء</VBtn>
          <VBtn color="accent" :disabled="!newExp.title.trim()" @click="saveExp">إضافة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Add certificate dialog -->
    <VDialog v-model="certDialog" max-width="440">
      <VCard class="pa-2">
        <VCardTitle>إضافة شهادة</VCardTitle>
        <VCardText>
          <VTextField v-model="newCert.name" label="اسم الشهادة" class="mb-2" />
          <VTextField v-model="newCert.issuer" label="الجهة المانحة" class="mb-2" />
          <VTextField v-model="newCert.date" label="السنة" placeholder="مثال: 2024" />
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="certDialog = false">إلغاء</VBtn>
          <VBtn color="accent" :disabled="!newCert.name.trim()" @click="saveCert">إضافة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
