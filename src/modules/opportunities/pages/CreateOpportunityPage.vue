<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'

const router = useRouter()

const title = ref('')
const department = ref<string | null>(null)
const location = ref('')
const type = ref<string | null>('full_time')
const skills = ref<string[]>([])
const educationLevel = ref<string | null>(null)
const experienceYears = ref<number | null>(null)
const salaryFrom = ref<number | null>(null)
const salaryTo = ref<number | null>(null)
const benefits = ref<string[]>([])
const channels = ref<string[]>(['internal_chat'])
const blindReview = ref(false)
const description = ref('')

const typeOptions = [
  { value: 'full_time', title: 'دوام كامل' },
  { value: 'part_time', title: 'دوام جزئي' },
  { value: 'remote', title: 'عن بُعد' },
  { value: 'temporary', title: 'مؤقت' },
  { value: 'task', title: 'مهمة' },
]
const departmentOptions = ['التقنية', 'التسويق', 'المبيعات', 'الموارد البشرية', 'المالية', 'التصميم']
const educationOptions = ['ثانوي', 'دبلوم', 'بكالوريوس', 'ماجستير', 'دكتوراه']
const benefitOptions = ['سكن', 'مواصلات', 'تأمين طبي', 'تذاكر سفر', 'بدل اتصالات']
const channelOptions = [
  { value: 'internal_chat', title: 'دردشة داخلية' },
  { value: 'email', title: 'بريد إلكتروني' },
  { value: 'whatsapp', title: 'واتساب' },
  { value: 'phone', title: 'هاتف' },
]

const snackbar = ref(false)

function publish() {
  snackbar.value = true
  setTimeout(() => router.push({ name: 'candidates' }), 1200)
}
</script>

<template>
  <div>
    <PageHeader
      title="نشر فرصة جديدة"
      subtitle="أنشئ طلب احتياج ديناميكي لاستقطاب أفضل المرشحين"
      icon="mdi-briefcase-plus-outline"
    />

    <VForm @submit.prevent="publish">
      <!-- Basic info -->
      <VCard class="pa-5 mb-4">
        <h3 class="text-subtitle-1 font-weight-bold mb-4">
          <VIcon icon="mdi-information-outline" color="primary" class="me-1" /> المعلومات الأساسية
        </h3>
        <VRow dense>
          <VCol cols="12" md="6">
            <VTextField v-model="title" label="المسمى الوظيفي" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="department" label="القسم" :items="departmentOptions" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="location" label="الموقع" prepend-inner-icon="mdi-map-marker-outline" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="type" label="نوع الدوام" :items="typeOptions" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="description" label="وصف الفرصة" rows="3" />
          </VCol>
        </VRow>
      </VCard>

      <!-- Requirements -->
      <VCard class="pa-5 mb-4">
        <h3 class="text-subtitle-1 font-weight-bold mb-4">
          <VIcon icon="mdi-checkbox-marked-circle-outline" color="primary" class="me-1" /> المتطلبات
        </h3>
        <VRow dense>
          <VCol cols="12">
            <VCombobox v-model="skills" label="المهارات المطلوبة" multiple chips closable-chips />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="educationLevel" label="المستوى التعليمي" :items="educationOptions" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model.number="experienceYears" label="سنوات الخبرة المطلوبة" type="number" />
          </VCol>
        </VRow>
      </VCard>

      <!-- Compensation -->
      <VCard class="pa-5 mb-4">
        <h3 class="text-subtitle-1 font-weight-bold mb-4">
          <VIcon icon="mdi-cash-multiple" color="primary" class="me-1" /> المزايا والحوافز
        </h3>
        <VRow dense>
          <VCol cols="12" md="6">
            <VTextField v-model.number="salaryFrom" label="الراتب من" type="number" suffix="ريال" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model.number="salaryTo" label="الراتب إلى" type="number" suffix="ريال" />
          </VCol>
          <VCol cols="12">
            <VSelect v-model="benefits" label="البدلات" :items="benefitOptions" multiple chips />
          </VCol>
        </VRow>
      </VCard>

      <!-- Options -->
      <VCard class="pa-5 mb-4">
        <h3 class="text-subtitle-1 font-weight-bold mb-4">
          <VIcon icon="mdi-tune" color="primary" class="me-1" /> آلية التواصل وخيارات إضافية
        </h3>
        <VSelect v-model="channels" label="قنوات التواصل" :items="channelOptions" multiple chips class="mb-2" />
        <VSwitch v-model="blindReview" label="تفعيل التقييم الأعمى (إخفاء الأسماء والصور)" color="secondary" hide-details />
      </VCard>

      <!-- Actions -->
      <div class="d-flex flex-wrap justify-end ga-2">
        <VBtn variant="outlined" color="primary" prepend-icon="mdi-eye-outline">معاينة</VBtn>
        <VBtn variant="tonal" color="secondary" prepend-icon="mdi-content-save-outline">حفظ كمسودة</VBtn>
        <VBtn type="submit" color="accent" prepend-icon="mdi-publish">نشر الفرصة</VBtn>
      </div>
    </VForm>

    <VSnackbar v-model="snackbar" color="success" timeout="1200">
      تم نشر الفرصة بنجاح! جارٍ تحويلك للترشيحات...
    </VSnackbar>
  </div>
</template>
