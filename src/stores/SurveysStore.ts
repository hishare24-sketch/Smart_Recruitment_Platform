import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export type QuestionType = 'single' | 'multiple' | 'text' | 'scale' | 'yesno'

export interface SurveyQuestion {
  id: number
  text: string
  type: QuestionType
  options?: string[]
}

export interface Survey {
  id: number
  title: string
  type: string
  audience: string
  questions: SurveyQuestion[]
  responses: number
  completion: number
  avgTime: string
  status: 'active' | 'draft'
}

const STORAGE_KEY = 'surveys'

const seed: Survey[] = [
  { id: 1, title: 'رضا المرشحين - يونيو', type: 'رضا المرشح', audience: 'داخل المنصة', questions: [], responses: 47, completion: 82, avgTime: '3:12', status: 'active' },
  { id: 2, title: 'احتياجات سوق التقنية', type: 'احتياجات السوق', audience: 'رابط عام', questions: [], responses: 128, completion: 64, avgTime: '5:40', status: 'active' },
]

function load(): Survey[] {
  const raw = localStorage.getItem(STORAGE_KEY)
  if (!raw)
    return seed.map(s => ({ ...s }))
  try {
    return JSON.parse(raw) as Survey[]
  }
  catch {
    return seed.map(s => ({ ...s }))
  }
}

let nextId = 300

export const useSurveysStore = defineStore('surveys', () => {
  const surveys = ref<Survey[]>(load())

  watch(surveys, val => localStorage.setItem(STORAGE_KEY, JSON.stringify(val)), { deep: true })

  function add(survey: Omit<Survey, 'id' | 'responses' | 'completion' | 'avgTime'>) {
    surveys.value.unshift({
      id: nextId++,
      responses: 0,
      completion: 0,
      avgTime: '—',
      ...survey,
    })
  }

  function remove(id: number) {
    surveys.value = surveys.value.filter(s => s.id !== id)
  }

  return { surveys, add, remove }
})

// AI-style question generation by survey type
export function generateQuestions(surveyType: string): SurveyQuestion[] {
  const bank: Record<string, SurveyQuestion[]> = {
    'رضا المرشح': [
      { id: 1, text: 'كيف تقيّم سرعة الرد على طلبك؟', type: 'scale' },
      { id: 2, text: 'هل كانت عملية التوظيف واضحة؟', type: 'yesno' },
      { id: 3, text: 'ما الذي يمكن تحسينه في تجربتك؟', type: 'text' },
    ],
    'تقييم وظيفي': [
      { id: 1, text: 'كيف تقيّم أداء المرشح بشكل عام؟', type: 'scale' },
      { id: 2, text: 'ما أبرز نقاط قوته؟', type: 'text' },
      { id: 3, text: 'هل توصي بتوظيفه؟', type: 'yesno' },
    ],
    'احتياجات السوق': [
      { id: 1, text: 'ما المهارات الأكثر طلباً لديكم؟', type: 'multiple', options: ['تطوير', 'تصميم', 'بيانات', 'تسويق'] },
      { id: 2, text: 'ما متوسط الرواتب المتوقعة؟', type: 'text' },
      { id: 3, text: 'كيف تقيّم توفّر المواهب في السوق؟', type: 'scale' },
    ],
  }
  return bank[surveyType] ?? [
    { id: 1, text: 'ما مدى رضاك عن الخدمة؟', type: 'scale' },
    { id: 2, text: 'ما اقتراحاتك للتحسين؟', type: 'text' },
  ]
}
