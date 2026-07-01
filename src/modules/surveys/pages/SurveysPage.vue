<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import { generateQuestions, useSurveysStore } from '@/stores/SurveysStore'
import type { QuestionType, SurveyQuestion } from '@/stores/SurveysStore'

const router = useRouter()
const store = useSurveysStore()

const surveyTypes = [
  { id: 1, name: 'تقييم وظيفي', desc: 'تقييم أداء المرشح من مديره السابق', icon: 'mdi-star-check-outline', color: 'primary' },
  { id: 2, name: 'توصية مهنية', desc: 'جمع توصيات من زملاء العمل', icon: 'mdi-account-star-outline', color: 'secondary' },
  { id: 3, name: 'رضا المرشح', desc: 'انطباع المرشح عن عملية التوظيف', icon: 'mdi-emoticon-happy-outline', color: 'success' },
  { id: 4, name: 'رضا جهة التوظيف', desc: 'تقييم جودة الترشيحات وسرعة الإنجاز', icon: 'mdi-domain', color: 'info' },
  { id: 5, name: 'تحليل شخصية', desc: 'فهم السمات الشخصية للباحث', icon: 'mdi-head-cog-outline', color: 'accent' },
  { id: 6, name: 'احتياجات السوق', desc: 'مهارات مطلوبة، رواتب، اتجاهات', icon: 'mdi-chart-line', color: 'warning' },
  { id: 7, name: 'جودة الخدمة', desc: 'تقييم تجربة المستخدم مع المنصة', icon: 'mdi-thumb-up-outline', color: 'primary' },
  { id: 8, name: 'التدريب والتطوير', desc: 'احتياجات المستخدمين من دورات', icon: 'mdi-school-outline', color: 'secondary' },
]

const questionTypeLabels: Record<QuestionType, string> = {
  single: 'اختيار واحد',
  multiple: 'اختيار متعدد',
  text: 'سؤال مفتوح',
  scale: 'مقياس (1-5)',
  yesno: 'نعم / لا',
}

const dialog = ref(false)
const selectedType = ref('')
const surveyTitle = ref('')
const surveyDesc = ref('')
const audience = ref('داخل المنصة')
const questions = ref<SurveyQuestion[]>([])
let qId = 1

function createSurvey(name: string) {
  selectedType.value = name
  surveyTitle.value = `استبيان ${name}`
  surveyDesc.value = ''
  audience.value = 'داخل المنصة'
  questions.value = []
  qId = 1
  dialog.value = true
}

function addQuestion(type: QuestionType = 'single') {
  questions.value.push({ id: qId++, text: '', type, options: type === 'single' || type === 'multiple' ? ['خيار 1', 'خيار 2'] : undefined })
}
function removeQuestion(id: number) {
  questions.value = questions.value.filter(q => q.id !== id)
}
function aiGenerate() {
  const generated = generateQuestions(selectedType.value)
  questions.value = generated.map(q => ({ ...q, id: qId++ }))
}
function saveSurvey() {
  store.add({
    title: surveyTitle.value || `استبيان ${selectedType.value}`,
    type: selectedType.value,
    audience: audience.value,
    questions: questions.value,
    status: 'active',
  })
  dialog.value = false
}
</script>

<template>
  <div>
    <PageHeader title="الاستبيانات التفاعلية" subtitle="ثمانية أنواع ذكية مدعومة بالذكاء الاصطناعي" icon="mdi-poll" />

    <h3 class="text-h6 font-weight-bold mb-3">أنشئ استبياناً جديداً</h3>
    <VRow class="mb-5">
      <VCol v-for="s in surveyTypes" :key="s.id" cols="12" sm="6" md="3">
        <VCard class="pa-4 text-center cursor-pointer h-100" @click="createSurvey(s.name)">
          <VAvatar :color="s.color" variant="tonal" size="52" rounded="lg" class="mb-2"><VIcon :icon="s.icon" size="28" /></VAvatar>
          <div class="text-subtitle-2 font-weight-bold">{{ s.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ s.desc }}</div>
        </VCard>
      </VCol>
    </VRow>

    <h3 class="text-h6 font-weight-bold mb-3">الاستبيانات النشطة ({{ store.surveys.length }})</h3>
    <VCard>
      <VTable>
        <thead>
          <tr>
            <th class="text-start">الاستبيان</th>
            <th class="text-start">النوع</th>
            <th class="text-start">الأسئلة</th>
            <th class="text-start">المستجيبون</th>
            <th class="text-start">نسبة الإكمال</th>
            <th class="text-start">إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in store.surveys" :key="s.id">
            <td class="font-weight-bold">{{ s.title }}</td>
            <td>{{ s.type }}</td>
            <td>{{ s.questions.length || '—' }}</td>
            <td>{{ s.responses }}</td>
            <td style="min-width: 140px">
              <VProgressLinear :model-value="s.completion" color="success" height="16" rounded>
                <span class="text-caption text-white">{{ s.completion }}%</span>
              </VProgressLinear>
            </td>
            <td>
              <VBtn variant="text" size="small" color="primary" prepend-icon="mdi-chart-box-outline" @click="router.push({ name: 'survey-analysis', params: { id: s.id } })">التحليل</VBtn>
              <VBtn variant="text" size="small" prepend-icon="mdi-eye-outline" @click="router.push({ name: 'survey-answer', params: { token: String(s.id) } })">معاينة</VBtn>
              <VBtn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="store.remove(s.id)" />
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <!-- Full builder dialog -->
    <VDialog v-model="dialog" max-width="720" scrollable>
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>منشئ الاستبيان: {{ selectedType }}</span>
          <VBtn icon="mdi-close" variant="text" size="small" @click="dialog = false" />
        </VCardTitle>
        <VCardText style="max-height: 70vh">
          <VTextField v-model="surveyTitle" label="عنوان الاستبيان" class="mb-2" />
          <VTextarea v-model="surveyDesc" label="وصف مختصر" rows="2" class="mb-2" />
          <VSelect v-model="audience" label="الجمهور المستهدف" :items="['داخل المنصة', 'بريد إلكتروني', 'واتساب', 'رابط عام']" class="mb-3" />

          <div class="d-flex align-center justify-space-between mb-2">
            <h4 class="text-subtitle-2 font-weight-bold">الأسئلة ({{ questions.length }})</h4>
            <VBtn color="secondary" variant="tonal" size="small" prepend-icon="mdi-robot-happy-outline" @click="aiGenerate">توليد بالذكاء الاصطناعي</VBtn>
          </div>

          <VCard v-for="(q, i) in questions" :key="q.id" variant="outlined" class="pa-3 mb-2">
            <div class="d-flex align-center ga-2 mb-2">
              <span class="text-body-2 font-weight-bold">{{ i + 1 }}.</span>
              <VTextField v-model="q.text" placeholder="نص السؤال" hide-details density="compact" class="flex-grow-1" />
              <VBtn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="removeQuestion(q.id)" />
            </div>
            <VSelect
              v-model="q.type"
              :items="Object.entries(questionTypeLabels).map(([value, title]) => ({ value, title }))"
              density="compact"
              hide-details
              style="max-width: 200px"
            />
            <div v-if="q.options" class="mt-2 d-flex flex-wrap ga-1">
              <VChip v-for="(opt, oi) in q.options" :key="oi" size="small" label>{{ opt }}</VChip>
            </div>
          </VCard>

          <div class="d-flex ga-2 mt-2">
            <VBtn variant="tonal" size="small" prepend-icon="mdi-plus" @click="addQuestion('single')">سؤال اختيار</VBtn>
            <VBtn variant="tonal" size="small" prepend-icon="mdi-plus" @click="addQuestion('text')">سؤال مفتوح</VBtn>
            <VBtn variant="tonal" size="small" prepend-icon="mdi-plus" @click="addQuestion('scale')">مقياس</VBtn>
          </div>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="dialog = false">إلغاء</VBtn>
          <VBtn color="accent" :disabled="!questions.length" prepend-icon="mdi-send" @click="saveSurvey">إنشاء وإرسال</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
