// Adaptive, anti-cheat interview engine (mock now, Claude API later — same shapes).
// The next question depends on the candidate's track and prior performance, and
// each pattern renders its own interactive widget so answers can't be pasted from
// a generic chatbot out of context.

export type InterviewTrack = 'tech' | 'management' | 'design' | 'data' | 'support'

export type QuestionPattern =
  | 'live_case' // 1 — spot & fix bugs in dynamic code
  | 'fill_code' // 5 — complete a function under a constraint
  | 'strategic' // 2 — decision matrix + market twist
  | 'angry_customer' // 4 — reply to an escalating customer
  | 'dirty_data' // 6 — clean a messy dataset
  | 'reverse_plan' // 7 — order tasks backwards from a deadline
  | 'project_dive' // 3 — deep questions about the user's own work
  | 'pressure' // 8 — scenario flashes, then answer from memory

export type Difficulty = 'basic' | 'intermediate' | 'advanced'

export interface LiveCasePayload { language: string, code: string, bugCount: number }
export interface FillCodePayload { language: string, signature: string, constraint: string }
export interface StrategicPayload { context: string, options: { key: string, label: string, pros: string, cons: string }[], twist: string }
export interface AngryCustomerPayload { channel: string, thread: { from: 'customer' | 'agent', text: string }[], constraint: string, escalation: string }
export interface DirtyDataPayload { columns: string[], rows: string[][], issues: string[] }
export interface ReversePlanPayload { goal: string, deadline: string, tasks: string[] }
export interface ProjectDivePayload { placeholder: string }
export interface PressurePayload { scenario: string, flashSeconds: number }

export type PatternPayload =
  | LiveCasePayload | FillCodePayload | StrategicPayload | AngryCustomerPayload
  | DirtyDataPayload | ReversePlanPayload | ProjectDivePayload | PressurePayload

export interface AdaptiveQuestion {
  id: number
  index: number
  pattern: QuestionPattern
  competency: string
  difficulty: Difficulty
  prompt: string
  scenario?: string
  seconds: number
  payload: PatternPayload
}

export interface InteractiveEvaluation {
  score: number // 0-100
  feedback: string
  signals: string[] // live "AI noticed" bullets
  followUp?: string // adaptive nudge shown before allowing "next"
}

export const TRACK_META: Record<InterviewTrack, { label: string, icon: string, desc: string }> = {
  tech: { label: 'تقني وهندسي', icon: 'mdi-code-tags', desc: 'أكواد حيّة، إكمال دوال، قرارات معمارية' },
  management: { label: 'إداري وقيادي', icon: 'mdi-account-tie', desc: 'مفاضلات استراتيجية، تخطيط، إدارة صراع' },
  design: { label: 'تصميم وإبداع', icon: 'mdi-palette-outline', desc: 'تعمّق في مشاريعك وقرارات التصميم' },
  data: { label: 'بيانات وتحليل', icon: 'mdi-chart-scatter-plot', desc: 'تنظيف بيانات ملوّثة واستدلال تحليلي' },
  support: { label: 'خدمة عملاء ومبيعات', icon: 'mdi-face-agent', desc: 'ردود احترافية وذكاء عاطفي تحت الضغط' },
}

export const PATTERN_META: Record<QuestionPattern, { label: string, icon: string }> = {
  live_case: { label: 'تحليل حالة فورية', icon: 'mdi-bug-outline' },
  fill_code: { label: 'أكواد ناقصة', icon: 'mdi-function-variant' },
  strategic: { label: 'مفاضلة استراتيجية', icon: 'mdi-sitemap-outline' },
  angry_customer: { label: 'رد على عميل غاضب', icon: 'mdi-emoticon-angry-outline' },
  dirty_data: { label: 'بيانات ملوّثة', icon: 'mdi-table-alert' },
  reverse_plan: { label: 'تخطيط عكسي', icon: 'mdi-timeline-clock-outline' },
  project_dive: { label: 'تعمّق في مشروعك', icon: 'mdi-folder-star-outline' },
  pressure: { label: 'مقابلة الضغط', icon: 'mdi-timer-alert-outline' },
}

const TRACK_SEQUENCE: Record<InterviewTrack, QuestionPattern[]> = {
  tech: ['live_case', 'fill_code', 'strategic', 'pressure'],
  management: ['strategic', 'reverse_plan', 'angry_customer', 'pressure'],
  design: ['project_dive', 'strategic', 'reverse_plan', 'pressure'],
  data: ['dirty_data', 'strategic', 'live_case', 'pressure'],
  support: ['angry_customer', 'strategic', 'reverse_plan', 'pressure'],
}

// ── deterministic payload generators (vary by index so it feels non-static) ──

const BUGGY_SNIPPETS: LiveCasePayload[] = [
  {
    language: 'javascript',
    bugCount: 3,
    code: `async function getUser(id) {\n  const res = fetch('/api/users/' + id)\n  const data = res.json()\n  return data.nmae\n}`,
  },
  {
    language: 'javascript',
    bugCount: 3,
    code: `function total(items) {\n  let sum\n  for (let i = 0; i <= items.length; i++)\n    sum += items[i].price\n  return sum\n}`,
  },
  {
    language: 'python',
    bugCount: 2,
    code: `def average(nums):\n    total = 0\n    for n in nums\n        total += n\n    return total / len(numbers)`,
  },
]

const FILL_SNIPPETS: FillCodePayload[] = [
  { language: 'javascript', signature: 'function average(...nums) {\n  // اكتب الجسم هنا\n}', constraint: 'احسب المتوسط الحسابي متجاهلًا القيم السالبة، وعالج قائمة فارغة بأمان.' },
  { language: 'javascript', signature: 'function retry(fn, times) {\n  // اكتب الجسم هنا\n}', constraint: 'أعد تنفيذ fn حتى النجاح أو نفاد المحاولات، مع تمرير الخطأ الأخير.' },
  { language: 'python', signature: 'def dedupe(items):\n    # اكتب الجسم هنا', constraint: 'أزل التكرار مع الحفاظ على الترتيب الأصلي بأقل تعقيد ممكن.' },
]

const STRATEGIC_PAYLOADS: StrategicPayload[] = [
  {
    context: 'لديك ميزة يطلبها كبار العملاء لكن هندستها الحالية هشّة. اختر المسار:',
    options: [
      { key: 'a', label: 'إطلاق سريع بحل مؤقّت', pros: 'رضا فوري للعملاء', cons: 'دين تقني يتراكم' },
      { key: 'b', label: 'إعادة هيكلة أولًا', pros: 'أساس متين', cons: 'تأخير قد يفقد صفقات' },
      { key: 'c', label: 'حل هجين تدريجي', pros: 'توازن', cons: 'يتطلب انضباطًا عاليًا' },
    ],
    twist: 'فجأة: أعلن منافس نفس الميزة الأسبوع القادم. هل يتغيّر قرارك؟ ولماذا؟',
  },
  {
    context: 'فريقك (5 أفراد) منقسم بين Agile وWaterfall ويجب الحسم خلال 10 دقائق. اختر نهجك:',
    options: [
      { key: 'a', label: 'فرض القرار كقائد', pros: 'سرعة', cons: 'مقاومة صامتة' },
      { key: 'b', label: 'تصويت الفريق', pros: 'تبنٍّ أعلى', cons: 'قد يطيل النقاش' },
      { key: 'c', label: 'تجربة أسبوعين لكل نهج', pros: 'قرار مبني على بيانات', cons: 'تكلفة وقت' },
    ],
    twist: 'الإدارة العليا طلبت التسليم أبكر بأسبوعين. صِغ الرسالة التي ستقولها للفريق الآن.',
  },
]

const ANGRY_CUSTOMER_PAYLOADS: AngryCustomerPayload[] = [
  {
    channel: 'محادثة الدعم',
    thread: [
      { from: 'customer', text: 'طلبي متأخّر 5 أيام ولا أحد يرد! هذه آخر مرة أتعامل معكم.' },
      { from: 'agent', text: 'نعتذر، سنتحقق من الأمر.' },
      { from: 'customer', text: '«سنتحقق»؟! أريد حلًّا الآن أو سألغي اشتراكي.' },
    ],
    constraint: 'اكتب ردًّا يعتذر ويقدّم حلًّا دون منح خصم كبير يضرّ الأرباح.',
    escalation: 'العميل ردّ: «الحل الذي عرضته لا يكفي، وسأكتب مراجعة سيئة». اكتب ردًّا جديدًا يحتوي الموقف.',
  },
]

const DIRTY_DATA_PAYLOADS: DirtyDataPayload[] = [
  {
    columns: ['الاسم', 'العمر', 'تاريخ التسجيل', 'المدينة'],
    rows: [
      ['أحمد', '34', '2025-03-01', 'الرياض'],
      ['سارة', '-5', '01/03/2025', 'جدة'],
      ['أحمد', '34', '2025-03-01', 'الرياض'],
      ['خالd', '250', '', 'الدمام'],
      ['ليان', '', '2025/13/40', ''],
    ],
    issues: ['قيمة عمر سالبة (-5)', 'عمر غير منطقي (250)', 'صف مكرّر (أحمد)', 'تنسيقات تاريخ مختلطة', 'قيم ناقصة (عمر/مدينة)'],
  },
]

const REVERSE_PLAN_PAYLOADS: ReversePlanPayload[] = [
  {
    goal: 'إطلاق متجر إلكتروني',
    deadline: '1 ديسمبر',
    tasks: ['تصميم الواجهات', 'اختبار النظام', 'ربط بوابة الدفع', 'إدخال المنتجات', 'حملة تسويقية', 'مراجعة أمنية'],
  },
  {
    goal: 'إطلاق حملة تسويقية كبرى',
    deadline: '20 نوفمبر',
    tasks: ['تحديد الجمهور', 'إنتاج المحتوى', 'اختبار A/B', 'جدولة النشر', 'قياس النتائج', 'ضبط الميزانية'],
  },
]

const PRESSURE_PAYLOADS: PressurePayload[] = [
  { scenario: 'أنت في اجتماع مع الشركاء، وفجأة انقطعت الكهرباء وضاع العرض التقديمي بكل بياناته.', flashSeconds: 10 },
  { scenario: 'قبل دقيقتين من عرض المنتج، اكتشفت خطأً فادحًا في الأرقام التي ستعرضها على الإدارة.', flashSeconds: 10 },
]

const PROJECT_DIVE: ProjectDivePayload = { placeholder: 'ألصق رابط مشروعك (GitHub / Behance) أو صف أهم مشروع نفّذته...' }

function pick<T>(arr: T[], index: number): T {
  return arr[index % arr.length]
}

const DIFFICULTY_BY_INDEX: Difficulty[] = ['basic', 'intermediate', 'advanced', 'advanced']

function buildQuestion(pattern: QuestionPattern, index: number, difficulty: Difficulty): AdaptiveQuestion {
  const base = { id: 1000 + index, index, pattern, difficulty }
  switch (pattern) {
    case 'live_case': {
      const p = pick(BUGGY_SNIPPETS, index)
      return { ...base, competency: 'التصحيح والتحليل', seconds: 240, prompt: `هذا الكود به ${p.bugCount} أخطاء تمنعه من العمل. حدّدها وأعد كتابة الدالة بشكل صحيح.`, payload: p }
    }
    case 'fill_code': {
      const p = pick(FILL_SNIPPETS, index)
      return { ...base, competency: 'كتابة الكود', seconds: 300, prompt: 'أكمل جسم الدالة وفق الشرط المحدّد — اكتب كودًا فعّالًا لا وصفًا نظريًا.', payload: p }
    }
    case 'strategic': {
      const p = pick(STRATEGIC_PAYLOADS, index)
      return { ...base, competency: 'التفكير الاستراتيجي', seconds: 300, prompt: 'اختر المسار الأنسب وبرّر قرارك بإيجاز مقنع.', payload: p }
    }
    case 'angry_customer': {
      const p = pick(ANGRY_CUSTOMER_PAYLOADS, index)
      return { ...base, competency: 'الذكاء العاطفي', seconds: 240, prompt: p.constraint, payload: p }
    }
    case 'dirty_data': {
      const p = pick(DIRTY_DATA_PAYLOADS, index)
      return { ...base, competency: 'جودة البيانات', seconds: 300, prompt: 'حدّد مشاكل هذا الجدول ثم صف 3 خطوات تنظيف وأثرها على التحليل.', payload: p }
    }
    case 'reverse_plan': {
      const p = pick(REVERSE_PLAN_PAYLOADS, index)
      return { ...base, competency: 'التخطيط', seconds: 240, prompt: `الهدف: «${p.goal}» بموعد نهائي ${p.deadline}. رتّب المهام عكسيًا من التسليم إلى البداية.`, payload: p }
    }
    case 'project_dive':
      return { ...base, competency: 'العمق المهني', seconds: 300, prompt: 'شاركنا مشروعك، وسنغوص في قراراتك.', payload: PROJECT_DIVE }
    case 'pressure': {
      const p = pick(PRESSURE_PAYLOADS, index)
      return { ...base, competency: 'سرعة البديهة', seconds: 150, prompt: 'اقرأ السيناريو جيدًا — سيختفي بعد ثوانٍ، ثم أجب من ذاكرتك.', scenario: p.scenario, payload: p }
    }
  }
}

export const interviewEngine = {
  tracks: TRACK_META,

  sequenceLength(track: InterviewTrack): number {
    return TRACK_SEQUENCE[track].length
  },

  // Adaptive: which pattern comes next, with difficulty shaped by prior score
  nextQuestion(track: InterviewTrack, index: number, prevScore: number | null): AdaptiveQuestion | null {
    const seq = TRACK_SEQUENCE[track]
    if (index >= seq.length)
      return null
    let difficulty = DIFFICULTY_BY_INDEX[Math.min(index, DIFFICULTY_BY_INDEX.length - 1)]
    // shape difficulty by performance: strong answers escalate, weak ones ease off
    if (prevScore != null) {
      if (prevScore >= 80 && difficulty === 'basic')
        difficulty = 'intermediate'
      else if (prevScore < 45 && difficulty === 'advanced')
        difficulty = 'intermediate'
    }
    return buildQuestion(seq[index], index, difficulty)
  },

  // Evaluate an interactive answer; may return an adaptive follow-up nudge
  evaluate(question: AdaptiveQuestion, answer: string): InteractiveEvaluation {
    const text = (answer ?? '').trim()
    const len = text.length
    const words = new Set(text.split(/\s+/).filter(Boolean)).size
    const signals: string[] = []
    let score = Math.min(100, Math.round(len / 6 + words * 2))
    if (len === 0)
      return { score: 0, feedback: 'لم تُقدّم إجابة — الصمت يُحسب في التقييم.', signals: ['لا مدخلات'] }
    score = Math.max(score, 40)

    switch (question.pattern) {
      case 'live_case':
      case 'fill_code': {
        const hasErrorHandling = /try|catch|await|throw|return|if|len|== *0|length/i.test(text)
        const hasStructure = /function|def|=>|\{|\breturn\b/i.test(text)
        if (hasStructure)
          signals.push('بنية دالة سليمة')
        else signals.push('لم أرَ كودًا فعليًا — اكتب الدالة لا وصفها')
        if (hasErrorHandling)
          signals.push('عالجت الحالات الحدّية')
        else signals.push('لم تُعالَج الحالات الحدّية (خطأ/قيمة فارغة)')
        score = Math.min(100, (hasStructure ? 55 : 30) + (hasErrorHandling ? 30 : 0) + Math.min(15, words))
        return {
          score,
          feedback: score >= 70 ? 'حل عملي يعالج جوهر المشكلة.' : 'اتجاه صحيح — أكمل معالجة الأخطاء وأعد التحقق.',
          signals,
          followUp: score >= 60 ? 'أحسنت — الآن ظهر خطأ جديد: ماذا يحدث لو كان المُدخل غير معرّف (undefined)؟ عالجه في نفس الدالة.' : undefined,
        }
      }
      case 'strategic': {
        const hasChoice = /\(?[أابجabc]\)?|الخيار|المسار|أختار|اختار/i.test(text)
        const hasReason = words >= 12
        if (hasChoice) signals.push('قرار واضح')
        if (hasReason) signals.push('تبرير مدعوم')
        else signals.push('التبرير مختصر — اربطه بأثر ملموس')
        score = Math.min(100, (hasChoice ? 45 : 30) + (hasReason ? 40 : 15) + Math.min(15, Math.floor(words / 3)))
        return { score, feedback: score >= 70 ? 'قرار مبرّر يوازن المقايضات.' : 'وضّح لماذا هذا الخيار تحديدًا مقابل البدائل.', signals }
      }
      case 'angry_customer': {
        const empathy = /نعتذر|أفهم|أقدّر|نأسف|يهمّنا|شكرًا/i.test(text)
        const solution = /سنقوم|سأقوم|الحل|نعوّض|خلال|سنشحن|بديل/i.test(text)
        if (empathy) signals.push('نبرة متعاطفة')
        else signals.push('ابدأ بتقدير مشاعر العميل')
        if (solution) signals.push('قدّمت حلًّا ملموسًا')
        else signals.push('أضف حلًّا محدّدًا بإطار زمني')
        score = Math.min(100, (empathy ? 40 : 20) + (solution ? 40 : 15) + Math.min(20, words))
        return { score, feedback: score >= 70 ? 'رد احترافي يحتوي الغضب ويقدّم مخرجًا.' : 'وازن بين التعاطف والحل دون وعود مكلفة.', signals, followUp: score >= 55 ? 'العميل ازداد حدّة وهدّد بمراجعة سيئة — اكتب ردًّا جديدًا يحتوي الموقف.' : undefined }
      }
      case 'dirty_data': {
        const mentionsIssue = /سالب|مكرر|تكرار|ناقص|فارغ|تاريخ|توحيد|dropna|fillna|median|متوسط/i.test(text)
        if (mentionsIssue) signals.push('حدّدت مشاكل حقيقية')
        else signals.push('حدّد المشاكل بدقة (سالب/مكرر/ناقص)')
        score = Math.min(100, (mentionsIssue ? 55 : 30) + Math.min(45, words * 2))
        return { score, feedback: score >= 70 ? 'منهجية تنظيف واعية بأثرها على التحليل.' : 'اربط كل خطوة تنظيف بأثرها على النتيجة.', signals }
      }
      case 'reverse_plan': {
        signals.push('ترتيب عكسي مُسجّل')
        score = 70
        return { score, feedback: 'رتّبت المهام — الترتيب يعكس منطقك الزمني.', signals }
      }
      case 'pressure': {
        const recalled = words >= 8
        if (recalled) signals.push('استرجاع جيد تحت الضغط')
        else signals.push('إجابة مقتضبة — وسّع تحت الضغط')
        score = Math.min(100, 45 + Math.min(45, words * 3))
        return { score, feedback: score >= 70 ? 'ثبات وسرعة بديهة واضحة.' : 'حافظ على الهدوء ونظّم أفكارك في نقاط.', signals }
      }
      case 'project_dive': {
        score = Math.min(100, 45 + Math.min(45, words * 2))
        if (words >= 15) signals.push('تفاصيل قرارات ملموسة')
        else signals.push('عمّق: ما القرار الذي اتخذته ولماذا؟')
        return { score, feedback: score >= 70 ? 'عمق حقيقي يصعب انتحاله.' : 'اذكر قرارًا محددًا وأثره القابل للقياس.', signals, followUp: score >= 55 ? 'لو تغيّر اللون الأساسي/المتطلّب في منتصف المشروع، كيف كنت ستتعامل مع بقية العناصر؟' : undefined }
      }
    }
  },
}
