import type { Candidate } from '../interfaces/Candidate'

export const mockCandidates: Candidate[] = [
  {
    id: 1,
    name: 'أحمد المنصور',
    title: 'مطوّر واجهات أمامية',
    location: 'الرياض',
    matchRate: 94,
    appliedAt: 'قبل يومين',
    status: 'new',
    skills: ['Vue.js', 'TypeScript', 'Vuetify', 'REST API'],
    experienceYears: 5,
    level: 'خبير',
    appliedFor: 'مطوّر واجهات أمامية (Vue.js)',
    summary: 'مطوّر شغوف ببناء تجارب مستخدم سلسة وأنظمة قابلة للتوسّع.',
  },
  {
    id: 2,
    name: 'سارة العتيبي',
    title: 'مهندسة برمجيات',
    location: 'جدة',
    matchRate: 88,
    appliedAt: 'قبل 3 أيام',
    status: 'reviewing',
    skills: ['Vue.js', 'Node.js', 'MongoDB'],
    experienceYears: 4,
    level: 'متوسط',
    appliedFor: 'مطوّر واجهات أمامية (Vue.js)',
    summary: 'مهندسة برمجيات متكاملة بخبرة في بناء تطبيقات الويب.',
  },
  {
    id: 3,
    name: 'خالد الحربي',
    title: 'مطوّر ويب',
    location: 'عن بُعد',
    matchRate: 79,
    appliedAt: 'قبل 4 أيام',
    status: 'interview',
    skills: ['JavaScript', 'React', 'CSS'],
    experienceYears: 3,
    level: 'متوسط',
    appliedFor: 'مطوّر واجهات أمامية (Vue.js)',
    summary: 'مطوّر ويب يركّز على الأداء وقابلية الصيانة.',
  },
  {
    id: 4,
    name: 'نورة القحطاني',
    title: 'مطوّرة junior',
    location: 'الدمام',
    matchRate: 68,
    appliedAt: 'قبل أسبوع',
    status: 'reviewing',
    skills: ['HTML', 'CSS', 'Vue.js'],
    experienceYears: 1,
    level: 'مبتدئ',
    appliedFor: 'مطوّر واجهات أمامية (Vue.js)',
    summary: 'خريجة حاسب طموحة في بداية مسيرتها المهنية.',
  },
]

export function getCandidateById(id: number): Candidate | undefined {
  return mockCandidates.find(c => c.id === id)
}
