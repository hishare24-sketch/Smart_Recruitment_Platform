import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export interface Skill { id: number, name: string, level: number }
export interface Experience { id: number, title: string, company: string, period: string, desc: string }
export interface Certificate { id: number, name: string, issuer: string, date: string }

interface ProfileState {
  headline: string
  summary: string
  skills: Skill[]
  experiences: Experience[]
  certificates: Certificate[]
}

const STORAGE_KEY = 'profileData'

const seed: ProfileState = {
  headline: 'مطوّر واجهات أمامية · الرياض',
  summary: 'مطوّر شغوف ببناء تجارب مستخدم سلسة وأنظمة قابلة للتوسّع. خبرة 5 سنوات في تطوير الواجهات الأمامية الحديثة.',
  skills: [
    { id: 1, name: 'Vue.js', level: 5 },
    { id: 2, name: 'TypeScript', level: 4 },
    { id: 3, name: 'UI/UX', level: 4 },
    { id: 4, name: 'Node.js', level: 3 },
  ],
  experiences: [
    { id: 1, title: 'مطوّر واجهات أمامية أول', company: 'شركة تقنية المستقبل', period: '2022 - الآن', desc: 'قيادة تطوير منصات الويب باستخدام Vue 3.' },
    { id: 2, title: 'مطوّر ويب', company: 'استوديو رؤية', period: '2019 - 2022', desc: 'بناء واجهات تفاعلية وتحسين الأداء.' },
  ],
  certificates: [
    { id: 1, name: 'Vue.js Professional', issuer: 'Vue School', date: '2023' },
    { id: 2, name: 'TypeScript Deep Dive', issuer: 'Frontend Masters', date: '2022' },
  ],
}

function load(): ProfileState {
  const raw = localStorage.getItem(STORAGE_KEY)
  if (!raw)
    return structuredClone(seed)
  try {
    return { ...structuredClone(seed), ...JSON.parse(raw) } as ProfileState
  }
  catch {
    return structuredClone(seed)
  }
}

let nextId = 1000

export const useProfileStore = defineStore('profile', () => {
  const state = load()
  const headline = ref(state.headline)
  const summary = ref(state.summary)
  const skills = ref<Skill[]>(state.skills)
  const experiences = ref<Experience[]>(state.experiences)
  const certificates = ref<Certificate[]>(state.certificates)

  function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      headline: headline.value,
      summary: summary.value,
      skills: skills.value,
      experiences: experiences.value,
      certificates: certificates.value,
    }))
  }

  watch([headline, summary, skills, experiences, certificates], persist, { deep: true })

  function addSkill(name: string, level: number) {
    if (name.trim())
      skills.value.push({ id: nextId++, name: name.trim(), level })
  }
  function removeSkill(id: number) {
    skills.value = skills.value.filter(s => s.id !== id)
  }
  function addExperience(exp: Omit<Experience, 'id'>) {
    experiences.value.unshift({ id: nextId++, ...exp })
  }
  function removeExperience(id: number) {
    experiences.value = experiences.value.filter(e => e.id !== id)
  }
  function addCertificate(cert: Omit<Certificate, 'id'>) {
    certificates.value.push({ id: nextId++, ...cert })
  }
  function removeCertificate(id: number) {
    certificates.value = certificates.value.filter(c => c.id !== id)
  }

  return {
    headline, summary, skills, experiences, certificates,
    addSkill, removeSkill, addExperience, removeExperience, addCertificate, removeCertificate,
  }
})
