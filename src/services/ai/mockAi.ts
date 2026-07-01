import type {
  AiService,
  AnswerEvaluation,
  InterviewLevel,
  InterviewQuestion,
  InterviewType,
  SkillLevelResult,
  TrustTip,
  VideoAnalysis,
} from './types'

// Convincing mock implementation. Deterministic where useful, varied where natural.

function skillLevel(proofScore: number): SkillLevelResult {
  let level: SkillLevelResult['level'] = 'entry'
  if (proofScore >= 80)
    level = 'expert'
  else if (proofScore >= 55)
    level = 'advanced'
  else if (proofScore >= 30)
    level = 'mid'

  const rationaleByLevel: Record<SkillLevelResult['level'], string> = {
    entry: 'إثباتات محدودة — أضف اختباراً أو مشروعاً لرفع المستوى.',
    mid: 'إثباتات جيدة — توصية موثّقة سترفعك للمستوى المتقدم.',
    advanced: 'إثباتات قوية متعددة المصادر تدعم هذا المستوى.',
    expert: 'إثباتات ممتازة (اختبارات + توصيات + مشاريع) تؤكد الخبرة.',
  }
  return { level, confidence: Math.min(100, Math.round(proofScore)), rationale: rationaleByLevel[level] }
}

function trustAnalysis(factors: { key: string, label: string, value: number }[]): TrustTip[] {
  const tips: TrustTip[] = []
  const byKey = Object.fromEntries(factors.map(f => [f.key, f]))

  if ((byKey.endorsements?.value ?? 100) < 80)
    tips.push({ text: 'أضف توصيتين موثّقتين من مدراء سابقين لرفع مصداقيتك.', gain: 6, action: 'profile', actionLabel: 'طلب توصية' })
  if ((byKey.assessments?.value ?? 100) < 75)
    tips.push({ text: 'أكمل اختبار مهارة في مجالك لإثبات قدراتك.', gain: 5, action: 'assessments', actionLabel: 'مركز التقييم' })
  if ((byKey.skills?.value ?? 100) < 70)
    tips.push({ text: 'أضف إثباتاً لمهاراتك غير الموثّقة (مشروع أو شهادة).', gain: 4, action: 'profile', actionLabel: 'ملفي' })
  if ((byKey.interviews?.value ?? 100) < 50)
    tips.push({ text: 'أجرِ مقابلة AI لتحديد مستواك ورفع الثقة.', gain: 5, action: 'interviews', actionLabel: 'المقابلات' })
  if ((byKey.completeness?.value ?? 100) < 90)
    tips.push({ text: 'أكمل بيانات ملفك (خبرات وتعليم) للوصول لاكتمال أعلى.', gain: 3, action: 'profile', actionLabel: 'ملفي' })

  return tips.length ? tips : [{ text: 'ملفك قوي! حافظ على تحديثه دوريًا للإبقاء على نسبة ثقتك.', gain: 0 }]
}

const QUESTION_BANK: Record<string, InterviewQuestion[]> = {
  basic: [
    { id: 1, text: 'عرّف بنفسك وخبرتك المهنية باختصار.', competency: 'التواصل' },
    { id: 2, text: 'ما أبرز المهارات التي تتقنها في مجالك؟', competency: 'المعرفة التقنية' },
    { id: 3, text: 'صف مشروعاً فخور بإنجازه ودورك فيه.', competency: 'الإنجاز' },
  ],
  intermediate: [
    { id: 1, text: 'كيف تعاملت مع مشكلة تقنية معقّدة مؤخراً؟ وما الحل؟', competency: 'حل المشكلات' },
    { id: 2, text: 'اشرح كيف تضمن جودة عملك وقابليته للصيانة.', competency: 'الجودة' },
    { id: 3, text: 'كيف تتعامل مع تعارض الأولويات تحت ضغط الوقت؟', competency: 'إدارة الوقت' },
    { id: 4, text: 'صف موقفاً تعاونت فيه مع فريق لتحقيق هدف.', competency: 'العمل الجماعي' },
  ],
  advanced: [
    { id: 1, text: 'كيف تصمّم نظاماً قابلاً للتوسّع من الصفر؟', competency: 'التصميم المعماري' },
    { id: 2, text: 'اشرح قراراً تقنياً استراتيجياً اتخذته وأثره.', competency: 'التفكير الاستراتيجي' },
    { id: 3, text: 'كيف تقود فريقاً تقنياً وتطوّر أفراده؟', competency: 'القيادة' },
    { id: 4, text: 'حلّل حالة: تراجع أداء منتج بنسبة 40% — ما خطتك؟', competency: 'تحليل الحالات' },
  ],
  expert: [
    { id: 1, text: 'كيف تبني رؤية تقنية طويلة المدى تتماشى مع أهداف العمل؟', competency: 'الرؤية' },
    { id: 2, text: 'صف تحوّلاً مؤسسياً قدته وكيف أدرت مقاومته.', competency: 'إدارة التغيير' },
    { id: 3, text: 'كيف توازن بين الابتكار والاستقرار في أنظمة حرجة؟', competency: 'الحوكمة' },
    { id: 4, text: 'قيّم مقايضة معمارية معقّدة اتخذتها ونتائجها.', competency: 'الحكم الهندسي' },
  ],
}

function interviewQuestions(_type: InterviewType, level: InterviewLevel): InterviewQuestion[] {
  return QUESTION_BANK[level] ?? QUESTION_BANK.basic
}

function evaluateAnswer(_question: string, answer: string): AnswerEvaluation {
  // Heuristic scoring by answer richness (length + keyword variety) — feels reasonable
  const len = answer.trim().length
  const words = new Set(answer.trim().split(/\s+/).filter(Boolean)).size
  let score = Math.min(100, Math.round(len / 4 + words * 2))
  if (len === 0)
    score = 0
  score = Math.max(score, len > 0 ? 45 : 0)

  let feedback = 'إجابة مختصرة — أضف مثالاً ملموساً وأرقاماً لتعزيزها.'
  if (score >= 80)
    feedback = 'إجابة قوية وواضحة مع أمثلة ملموسة.'
  else if (score >= 60)
    feedback = 'إجابة جيدة — يمكن دعمها بنتائج قابلة للقياس.'
  return { score, feedback }
}

function videoAnalysis(): VideoAnalysis {
  return {
    bodyLanguage: 82,
    tone: 78,
    confidence: 85,
    note: 'حضور واثق ونبرة متزنة. حافظ على التواصل البصري ووتيرة كلام معتدلة.',
  }
}

function suggestProofRequest(skillName: string): string {
  return `اطلب من مديرك أو زميلك السابق تأكيد مهارتك في «${skillName}» عبر توصية موثّقة مرتبطة بها.`
}

export const mockAi: AiService = {
  skillLevel,
  trustAnalysis,
  interviewQuestions,
  evaluateAnswer,
  videoAnalysis,
  suggestProofRequest,
}
