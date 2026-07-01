// Three-level skills taxonomy: main category → subcategories → sample skills.
// Used for classifying skills, grouping profile skills, and filtering interviewers.

export interface TaxonomyCategory {
  id: string
  label: string
  icon: string
  color: string
  subcategories: string[]
  skills: string[]
}

export const TAXONOMY: TaxonomyCategory[] = [
  {
    id: 'technology',
    label: 'التقنية',
    icon: 'mdi-code-tags',
    color: 'primary',
    subcategories: ['البرمجة', 'الشبكات', 'الأمن السيبراني', 'الذكاء الاصطناعي', 'قواعد البيانات', 'السحابة', 'DevOps'],
    skills: ['PHP', 'Python', 'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Node.js', 'AWS', 'SQL', 'Laravel', 'Flutter', 'Docker', 'Kubernetes'],
  },
  {
    id: 'management',
    label: 'الإدارة والأعمال',
    icon: 'mdi-briefcase-outline',
    color: 'accent',
    subcategories: ['القيادة', 'الموارد البشرية', 'التسويق', 'المبيعات', 'المالية', 'الاستشارات', 'ريادة الأعمال'],
    skills: ['PMP', 'Agile', 'التسويق الرقمي', 'تحليل البيانات', 'إدارة المشاريع', 'التفاوض', 'القيادة'],
  },
  {
    id: 'design',
    label: 'التصميم والإبداع',
    icon: 'mdi-palette-outline',
    color: 'secondary',
    subcategories: ['التصميم الجرافيكي', 'UX/UI', 'التصوير', 'الكتابة', 'الإعلام', 'الإخراج'],
    skills: ['Figma', 'Adobe Suite', 'Sketch', 'UI/UX', 'كتابة المحتوى', 'التصوير الفوتوغرافي', 'المونتاج'],
  },
  {
    id: 'engineering',
    label: 'الهندسة',
    icon: 'mdi-cog-outline',
    color: 'info',
    subcategories: ['المدنية', 'المعمارية', 'الميكانيكية', 'الكهربائية', 'الصناعية', 'البيئية'],
    skills: ['AutoCAD', 'Revit', 'SolidWorks', 'MATLAB', 'ANSYS', 'Civil 3D'],
  },
  {
    id: 'health',
    label: 'الصحة والعلوم',
    icon: 'mdi-heart-pulse',
    color: 'error',
    subcategories: ['الطب', 'التمريض', 'الصيدلة', 'البحث العلمي', 'المختبرات', 'التغذية'],
    skills: ['التمريض السريري', 'التحاليل الطبية', 'الأبحاث السريرية', 'التغذية العلاجية'],
  },
  {
    id: 'legal',
    label: 'الخدمات القانونية',
    icon: 'mdi-scale-balance',
    color: 'warning',
    subcategories: ['القانون', 'التحكيم', 'الاستشارات القانونية', 'الملكية الفكرية'],
    skills: ['العقود التجارية', 'القضايا التجارية', 'التحكيم الدولي', 'الملكية الفكرية'],
  },
  {
    id: 'education',
    label: 'التعليم والتدريب',
    icon: 'mdi-school-outline',
    color: 'success',
    subcategories: ['التدريس', 'التدريب', 'التطوير المهني', 'التعليم الإلكتروني'],
    skills: ['تصميم مناهج', 'تدريب قيادي', 'التعليم الإلكتروني', 'تدريب المدربين'],
  },
  {
    id: 'logistics',
    label: 'الخدمات اللوجستية',
    icon: 'mdi-truck-outline',
    color: 'primary',
    subcategories: ['سلسلة التوريد', 'النقل', 'التخزين', 'التوزيع'],
    skills: ['إدارة المخزون', 'التخطيط اللوجستي', 'تحسين سلاسل التوريد'],
  },
]

export function getCategory(id: string | undefined): TaxonomyCategory | undefined {
  return TAXONOMY.find(c => c.id === id)
}

// Best-effort classification of a free-text skill name into a category id.
export function categorizeSkill(name: string): string | undefined {
  const n = name.trim().toLowerCase()
  for (const cat of TAXONOMY) {
    if (cat.skills.some(s => s.toLowerCase() === n))
      return cat.id
  }
  // loose contains match as a fallback
  for (const cat of TAXONOMY) {
    if (cat.skills.some(s => n.includes(s.toLowerCase()) || s.toLowerCase().includes(n)))
      return cat.id
  }
  return undefined
}

// All known skills across the taxonomy (for autocomplete / interviewer filter)
export const ALL_SKILLS: string[] = [...new Set(TAXONOMY.flatMap(c => c.skills))].sort()
