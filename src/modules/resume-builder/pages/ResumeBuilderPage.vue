<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import { useResumesStore } from '@/stores/ResumesStore'
import { useProfileStore } from '@/stores/ProfileStore'
import { useAuthStore } from '@/stores/AuthStore'
import { ai } from '@/services/ai'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseSwitch from '@/components/ui/BaseSwitch.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'
import BaseDropdown from '@/components/ui/BaseDropdown.vue'

const router = useRouter()
const resumesStore = useResumesStore()
const profile = useProfileStore()
const auth = useAuthStore()

function colorVar(c: string) {
  return `rgb(var(--v-theme-${c === 'amber' ? 'warning' : c}))`
}

const step = ref(1)
const totalSteps = 7

const templates = [
  { id: 'classic', name: 'كلاسيكي', desc: 'بسيط واحترافي', color: '#2D3748', icon: 'mdi-file-document-outline' },
  { id: 'modern', name: 'حديث', desc: 'عصري بألوان جريئة', color: '#319795', icon: 'mdi-file-star-outline' },
  { id: 'creative', name: 'إبداعي', desc: 'حر وغير تقليدي', color: '#ED8936', icon: 'mdi-palette-outline' },
  { id: 'academic', name: 'أكاديمي', desc: 'للأبحاث والمنشورات', color: '#1A365D', icon: 'mdi-school-outline' },
  { id: 'executive', name: 'تنفيذي', desc: 'فاخر للقياديين', color: '#553C9A', icon: 'mdi-crown-outline' },
]
const selectedTemplate = ref('modern')

const sections = ref([
  { key: 'personal', name: 'المعلومات الشخصية', enabled: true },
  { key: 'summary', name: 'الملخص المهني', enabled: true },
  { key: 'skills', name: 'المهارات', enabled: true },
  { key: 'experience', name: 'الخبرات', enabled: true },
  { key: 'education', name: 'التعليم', enabled: true },
  { key: 'projects', name: 'المشاريع', enabled: true },
  { key: 'endorsements', name: 'التوصيات', enabled: false },
  { key: 'certificates', name: 'الشهادات', enabled: true },
  { key: 'languages', name: 'اللغات', enabled: true },
  { key: 'hobbies', name: 'الهوايات', enabled: false },
])

const language = ref('ar')
const colorScheme = ref('blue')
const fontSize = ref('medium')
const withPhoto = ref(true)
const resumeName = ref('')

const LANGUAGE_OPTIONS = [{ value: 'ar', title: 'العربية' }, { value: 'en', title: 'الإنجليزية' }, { value: 'bi', title: 'ثنائي اللغة' }]
const SCHEME_OPTIONS = [{ value: 'blue', title: 'أزرق' }, { value: 'green', title: 'أخضر' }, { value: 'orange', title: 'برتقالي' }, { value: 'dark', title: 'غامق' }]
const FONTSIZE_OPTIONS = [{ value: 'small', title: 'صغير' }, { value: 'medium', title: 'متوسط' }, { value: 'large', title: 'كبير' }]
const SUMMARY_STYLES = [{ value: 'professional', label: 'احترافي' }, { value: 'creative', label: 'إبداعي' }, { value: 'brief', label: 'موجز' }, { value: 'detailed', label: 'مفصّل' }]

function toggleStyle(active: boolean, color = 'primary') {
  if (active)
    return { background: `rgb(var(--v-theme-${color}))`, color: `rgb(var(--v-theme-on-${color}))`, borderColor: 'transparent' }
  return { background: 'transparent', color: 'rgba(var(--v-theme-on-surface), 0.75)', borderColor: 'rgba(var(--v-theme-on-surface), 0.2)' }
}

const templateName = computed(() => templates.find(t => t.id === selectedTemplate.value)?.name ?? 'حديث')
const languageLabel = computed(() => (language.value === 'ar' ? 'عربي' : language.value === 'en' ? 'English' : 'ثنائي'))

function saveResume() {
  const name = resumeName.value.trim() || `سيرة ${templateName.value}`
  resumesStore.add(name, templateName.value, languageLabel.value)
  router.push({ name: 'profile' })
}

const toastMsg = ref('')
function currentName() {
  return resumeName.value.trim() || `سيرة ${templateName.value}`
}

// — Real client-side export —
const PNG_PROPS = ['color', 'background-color', 'background-image', 'font-size', 'font-weight', 'font-family', 'font-style', 'line-height', 'letter-spacing', 'text-align', 'text-transform', 'text-decoration', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 'border', 'border-bottom', 'border-radius', 'display', 'flex-wrap', 'flex-direction', 'align-items', 'justify-content', 'gap', 'width', 'height', 'opacity', 'box-sizing', 'direction']
function inlineStyles(src: Element, dest: HTMLElement) {
  const cs = getComputedStyle(src)
  for (const p of PNG_PROPS)
    dest.style.setProperty(p, cs.getPropertyValue(p))
  const s = src.children
  const d = dest.children
  for (let i = 0; i < s.length; i++)
    inlineStyles(s[i], d[i] as HTMLElement)
}
function printResume() {
  toastMsg.value = 'يفتح مربع الطباعة — اختر «حفظ كـ PDF».'
  setTimeout(() => window.print(), 300)
}
async function exportPng() {
  const node = document.querySelector('.resume-print-target') as HTMLElement | null
  if (!node)
    return
  try {
    const w = node.offsetWidth
    const h = node.scrollHeight
    const clone = node.cloneNode(true) as HTMLElement
    inlineStyles(node, clone)
    clone.style.maxHeight = 'none'
    clone.style.margin = '0'
    const xml = new XMLSerializer().serializeToString(clone)
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}"><foreignObject x="0" y="0" width="100%" height="100%">${xml}</foreignObject></svg>`
    const img = new Image()
    await new Promise((res, rej) => {
      img.onload = res
      img.onerror = rej
      img.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`
    })
    const scale = 2
    const canvas = document.createElement('canvas')
    canvas.width = w * scale
    canvas.height = h * scale
    const ctx = canvas.getContext('2d')!
    ctx.scale(scale, scale)
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, w, h)
    ctx.drawImage(img, 0, 0)
    canvas.toBlob((blob) => {
      if (!blob) {
        toastMsg.value = 'تعذّر توليد الصورة — جرّب تصدير PDF.'
        return
      }
      const a = document.createElement('a')
      a.href = URL.createObjectURL(blob)
      a.download = `${currentName()}.png`
      a.click()
      URL.revokeObjectURL(a.href)
      toastMsg.value = 'تم تنزيل صورة السيرة (PNG).'
    })
  }
  catch {
    toastMsg.value = 'تعذّر توليد الصورة — جرّب تصدير PDF.'
  }
}
// Real Word export: inline the styles into a clone, wrap in Word-namespaced
// HTML, and download as .doc (opens in Word / Google Docs — no dependency).
function exportDoc() {
  const node = document.querySelector('.resume-print-target') as HTMLElement | null
  if (!node)
    return
  const clone = node.cloneNode(true) as HTMLElement
  inlineStyles(node, clone)
  clone.style.maxHeight = 'none'
  clone.style.boxShadow = 'none'
  const html = `<!DOCTYPE html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><title>${currentName()}</title></head><body dir="${isRtl.value ? 'rtl' : 'ltr'}">${clone.outerHTML}</body></html>`
  const blob = new Blob(['﻿', html], { type: 'application/msword' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `${currentName()}.doc`
  a.click()
  URL.revokeObjectURL(a.href)
  toastMsg.value = 'تم تنزيل نسخة Word من السيرة.'
}
function exportResume(format: string) {
  if (format.includes('PDF'))
    printResume()
  else if (format === 'PNG')
    exportPng()
  else if (format === 'DOCX')
    exportDoc()
  else
    toastMsg.value = `تصدير ${format} سيتوفّر قريبًا.`
}

// — Sharing: public link, private (password) link, QR —
const resumeSlug = computed(() => encodeURIComponent(currentName()))
const publicLink = computed(() => `${window.location.origin}${import.meta.env.BASE_URL}resume/${resumeSlug.value}`)
const privateDialog = ref(false)
const privatePassword = ref('')
const qrDialog = ref(false)
const qrError = ref(false)
const qrUrl = computed(() => `https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=8&data=${encodeURIComponent(publicLink.value)}`)

function copyPublicLink() {
  navigator.clipboard?.writeText(publicLink.value)
  toastMsg.value = 'تم نسخ الرابط العام للسيرة.'
}
function genPassword() {
  const chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'
  privatePassword.value = Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}
function openPrivateLink() {
  genPassword()
  privateDialog.value = true
}
function openQr() {
  qrError.value = false
  qrDialog.value = true
}
function copyPrivate() {
  navigator.clipboard?.writeText(`${publicLink.value}?pw=${privatePassword.value}`)
  toastMsg.value = 'تم نسخ الرابط الخاص وكلمة المرور.'
}

const exportFormats = [
  { label: 'PDF احترافي', icon: 'mdi-file-pdf-box', color: 'error', fmt: 'PDF احترافي' },
  { label: 'PDF تفاعلي', icon: 'mdi-file-link-outline', color: 'error', fmt: 'PDF تفاعلي' },
  { label: 'DOCX', icon: 'mdi-file-word-box', color: 'primary', fmt: 'DOCX' },
  { label: 'صورة PNG', icon: 'mdi-file-image-outline', color: 'success', fmt: 'PNG' },
]

// — AI analysis —
const resumeSkills = ['Vue.js', 'TypeScript', 'UI/UX', 'Node.js']
const aiReview = computed(() => ai.resumeReview(summary.value, resumeSkills))
const opportunities = ['مطوّر واجهات أمامية أول', 'استشارة معمارية Frontend', 'بناء نظام تصميم موحّد']
const opportunityOptions = opportunities.map(o => ({ value: o, title: o }))
const selectedOpportunity = ref(opportunities[0])
const vsSuggestions = computed(() => ai.resumeVsOpportunity(summary.value, selectedOpportunity.value))
function translateSummary(to: 'ar' | 'en') {
  summary.value = ai.translateText(summary.value, to)
  toastMsg.value = to === 'en' ? 'تُرجم الملخص للإنجليزية.' : 'تُرجم الملخص للعربية.'
}

const summary = ref('مطوّر واجهات أمامية بخبرة 5 سنوات في بناء تطبيقات ويب حديثة وعالية الأداء باستخدام Vue.js و TypeScript. شغوف بتجربة المستخدم والحلول القابلة للتوسّع.')
const summaryStyle = ref('professional')

const stepTitles = [
  'اختيار القالب',
  'تخصيص المحتوى',
  'اللغة والتنسيق',
  'الملخص المهني',
  'مراجعة الإنجازات',
  'المعاينة النهائية',
  'التصدير والمشاركة',
]

function next() {
  if (step.value < totalSteps)
    step.value++
}
function prev() {
  if (step.value > 1)
    step.value--
}

function regenerateSummary() {
  const variants = [
    'مطوّر واجهات أمامية مبدع يجمع بين الإتقان التقني وحس التصميم، مع سجلّ حافل في تسليم منتجات رقمية أثّرت في تجربة آلاف المستخدمين.',
    'خبير واجهات أمامية متخصص في Vue.js، قاد فرقاً تقنية وحسّن أداء التطبيقات بنسبة تجاوزت 40%.',
    'مطوّر شغوف بالتقنيات الحديثة، يركّز على الجودة وقابلية الصيانة وسرعة الإنجاز.',
  ]
  summary.value = variants[Math.floor(Math.random() * variants.length)]
}

// ————— Live preview: template + colours + fonts + sections applied instantly —————
const SCHEME_COLORS: Record<string, string> = { blue: '#2563eb', green: '#16a34a', orange: '#ea580c', dark: '#334155' }
const accentColor = computed(() => SCHEME_COLORS[colorScheme.value] ?? SCHEME_COLORS.blue)
const fontPx = computed(() => ({ small: '13px', medium: '14.5px', large: '16px' }[fontSize.value] ?? '14.5px'))
const isRtl = computed(() => language.value !== 'en')

// Header layout variant per template
const HEADER_MOD: Record<string, string> = { classic: 'line', modern: 'band', creative: 'band creative', academic: 'center line', executive: 'dark' }
const headerMod = computed(() => (HEADER_MOD[selectedTemplate.value] ?? 'band').split(' ').map(m => `rp-header--${m}`).join(' '))

const enabled = computed(() => Object.fromEntries(sections.value.map(s => [s.key, s.enabled])) as Record<string, boolean>)

// Bilingual section labels — the language toggle updates the preview live
const AR_L = { summary: 'الملخص المهني', skills: 'المهارات', experience: 'الخبرات العملية', education: 'التعليم', projects: 'المشاريع', certificates: 'الشهادات', languages: 'اللغات', hobbies: 'الاهتمامات', title: 'مطوّر واجهات أمامية' }
const EN_L = { summary: 'Professional Summary', skills: 'Skills', experience: 'Experience', education: 'Education', projects: 'Projects', certificates: 'Certificates', languages: 'Languages', hobbies: 'Interests', title: 'Frontend Developer' }
const L = computed(() => (language.value === 'en' ? EN_L : AR_L))

// Real data from the candidate's profile (falls back to samples so the preview always looks complete)
const displayName = computed(() => auth.authUser?.name ?? 'أحمد محمد')
const headline = computed(() => profile.headline || L.value.title)
const previewSkills = computed(() => (profile.skills.length ? profile.skills.map(s => s.name) : ['Vue.js', 'TypeScript', 'UI/UX', 'Node.js']))
const previewExperiences = computed(() =>
  profile.experiences.length
    ? profile.experiences
    : [{ id: 0, title: 'مطوّر واجهات أمامية أول', company: 'شركة تقنية', period: '2021 - الآن', desc: 'قيادة تطوير واجهات منتجات SaaS.' }],
)
const previewCertificates = computed(() =>
  profile.certificates.length
    ? profile.certificates
    : [{ id: 0, name: 'Vue.js المحترف', issuer: 'منصّة تدريب', date: '2024' }],
)
const previewEducation = [{ degree: 'بكالوريوس علوم حاسب', school: 'جامعة الملك سعود', year: '2019' }]
const previewProjects = [{ name: 'نظام تصميم موحّد', desc: 'مكتبة مكوّنات Vue قابلة لإعادة الاستخدام عبر 6 منتجات.' }]
const previewLanguages = ['العربية — الأم', 'English — متقدّم']
const previewHobbies = ['القراءة التقنية', 'التصوير', 'الشطرنج']
</script>

<template>
  <div>
    <PageHeader
      title="منشئ السيرة الذاتية الذكي"
      subtitle="أنشئ سيرة احترافية من بيانات ملفك — بمعاينة حيّة تتحدّث فورًا"
      icon="mdi-file-account-outline"
    />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
      <!-- Left: wizard -->
      <div class="md:col-span-7">
        <!-- Stepper progress -->
        <BaseCard class="mb-4">
          <div class="mb-2 flex justify-between">
            <span class="text-sm font-bold text-content">{{ stepTitles[step - 1] }}</span>
            <span class="text-xs text-muted">الخطوة {{ step }} من {{ totalSteps }}</span>
          </div>
          <BaseProgressBar :value="(step / totalSteps) * 100" :height="8" color="accent" />
        </BaseCard>

        <BaseCard class="mb-4 min-h-[380px]">
          <!-- Step 1: Template -->
          <div v-if="step === 1">
            <h3 class="mb-4 text-base font-bold text-content">اختر قالباً</h3>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
              <button
                v-for="tpl in templates"
                :key="tpl.id"
                type="button"
                class="rounded-ui-lg border p-3 text-center transition"
                :style="selectedTemplate === tpl.id
                  ? { background: 'rgb(var(--v-theme-primary))', color: 'rgb(var(--v-theme-on-primary))', borderColor: 'transparent' }
                  : { borderColor: 'rgba(var(--v-theme-on-surface), 0.14)' }"
                @click="selectedTemplate = tpl.id"
              >
                <BaseIcon :name="tpl.icon" :size="40" :style="{ color: selectedTemplate === tpl.id ? 'rgb(var(--v-theme-on-primary))' : tpl.color }" />
                <div class="mt-2 text-sm font-bold" :class="selectedTemplate === tpl.id ? '' : 'text-content'">{{ tpl.name }}</div>
                <div class="text-xs" :style="selectedTemplate === tpl.id ? {} : { color: 'rgba(var(--v-theme-on-surface), 0.6)' }">{{ tpl.desc }}</div>
              </button>
            </div>
          </div>

          <!-- Step 2: Sections -->
          <div v-else-if="step === 2">
            <h3 class="mb-1 text-base font-bold text-content">خصّص الأقسام</h3>
            <p class="mb-4 text-xs text-muted">فعّل الأقسام التي تريد إدراجها في سيرتك</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div v-for="sec in sections" :key="sec.key" class="flex items-center justify-between rounded-ui-lg border-ui p-3">
                <span class="text-sm text-content">{{ sec.name }}</span>
                <BaseSwitch v-model="sec.enabled" />
              </div>
            </div>
          </div>

          <!-- Step 3: Language & format -->
          <div v-else-if="step === 3">
            <h3 class="mb-4 text-base font-bold text-content">اللغة والتنسيق</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-muted">اللغة</label>
                <BaseSelect :model-value="language" :items="LANGUAGE_OPTIONS" @update:model-value="v => v && (language = v)" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-muted">نظام الألوان</label>
                <BaseSelect :model-value="colorScheme" :items="SCHEME_OPTIONS" @update:model-value="v => v && (colorScheme = v)" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-muted">حجم الخط</label>
                <BaseSelect :model-value="fontSize" :items="FONTSIZE_OPTIONS" @update:model-value="v => v && (fontSize = v)" />
              </div>
              <div class="flex items-end">
                <BaseSwitch v-model="withPhoto" label="إضافة صورة شخصية" />
              </div>
            </div>
          </div>

          <!-- Step 4: Summary -->
          <div v-else-if="step === 4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
              <h3 class="text-base font-bold text-content">الملخص المهني</h3>
              <div class="flex gap-2">
                <BaseDropdown align="end">
                  <template #trigger="{ toggle }">
                    <BaseButton variant="tonal-emerald" size="sm" @click="toggle"><BaseIcon name="mdi-translate" :size="16" />ترجمة</BaseButton>
                  </template>
                  <div class="min-w-[180px] py-1">
                    <button class="menu-row" @click="translateSummary('en')"><BaseIcon name="mdi-alphabet-latin" :size="18" />إلى الإنجليزية</button>
                    <button class="menu-row" @click="translateSummary('ar')"><BaseIcon name="mdi-abjad-arabic" :size="18" />إلى العربية</button>
                  </div>
                </BaseDropdown>
                <BaseButton variant="tonal-emerald" size="sm" @click="regenerateSummary"><BaseIcon name="mdi-refresh" :size="16" />إعادة توليد ذكي</BaseButton>
              </div>
            </div>
            <div class="mb-2 flex flex-wrap gap-2">
              <button
                v-for="st in SUMMARY_STYLES"
                :key="st.value"
                type="button"
                class="rounded-full border px-3 py-1 text-sm font-medium transition"
                :style="toggleStyle(summaryStyle === st.value)"
                @click="summaryStyle = st.value"
              >{{ st.label }}</button>
            </div>
            <BaseTextarea v-model="summary" :rows="5" />
          </div>

          <!-- Step 5: Achievements -->
          <div v-else-if="step === 5">
            <h3 class="mb-1 text-base font-bold text-content">مراجعة الإنجازات</h3>
            <p class="mb-4 text-xs text-muted">أعد صياغة إنجازاتك بأسلوب احترافي بنقرة واحدة</p>
            <div class="mb-3 rounded-ui-lg border-ui p-3">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-bold text-content">قيادة تطوير منصة الويب</span>
                <BaseButton variant="tonal-emerald" size="sm" @click="() => {}"><BaseIcon name="mdi-auto-fix" :size="14" />إعادة صياغة</BaseButton>
              </div>
              <p class="mb-0 text-sm text-muted">
                قاد فريقاً من 4 مطوّرين لإطلاق منصة ويب جديدة، محقّقاً تحسيناً في الأداء بنسبة 40% وزيادة رضا المستخدمين.
              </p>
            </div>
            <div class="rounded-ui-lg border-ui p-3">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-bold text-content">تحسين أداء التطبيق</span>
                <BaseButton variant="tonal-emerald" size="sm" @click="() => {}"><BaseIcon name="mdi-auto-fix" :size="14" />إعادة صياغة</BaseButton>
              </div>
              <p class="mb-0 text-sm text-muted">
                صمّم وطبّق استراتيجية تحميل كسول قلّصت زمن التحميل الأولي من 4 ثوانٍ إلى 1.2 ثانية.
              </p>
            </div>
          </div>

          <!-- Step 6: AI review (the visual resume is live on the right) -->
          <div v-else-if="step === 6">
            <h3 class="mb-1 text-base font-bold text-content">تحليل ورؤى الـ AI</h3>
            <p class="mb-4 text-xs text-muted">راجع سيرتك الحيّة على اليمين، وحسّنها حسب توصيات الـ AI.</p>

            <!-- AI analysis -->
            <div class="rounded-ui-lg p-4" style="background: rgba(var(--v-theme-secondary), 0.12)">
              <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <BaseIcon name="mdi-robot-happy-outline" :size="20" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />
                  <span class="text-sm font-bold text-content">تحليل الـ AI للسيرة</span>
                </div>
                <BaseChip :color="aiReview.score >= 80 ? 'success' : aiReview.score >= 60 ? 'warning' : 'error'">قوة السيرة {{ aiReview.score }}%</BaseChip>
              </div>

              <div class="mb-3 flex flex-col gap-4 md:flex-row">
                <div class="flex-1">
                  <div class="mb-1 flex items-center gap-1 text-xs font-bold" :style="{ color: colorVar('success') }"><BaseIcon name="mdi-thumb-up-outline" :size="14" /> نقاط القوة</div>
                  <ul class="mb-0 list-disc ps-5 text-xs text-content">
                    <li v-for="(s, i) in aiReview.strengths" :key="i">{{ s }}</li>
                  </ul>
                </div>
                <div class="flex-1">
                  <div class="mb-1 flex items-center gap-1 text-xs font-bold" :style="{ color: colorVar('warning') }"><BaseIcon name="mdi-alert-outline" :size="14" /> فرص التحسين</div>
                  <ul class="mb-0 list-disc ps-5 text-xs text-content">
                    <li v-for="(w, i) in aiReview.weaknesses" :key="i">{{ w }}</li>
                  </ul>
                </div>
              </div>

              <div class="mb-1 flex items-center gap-1 text-xs font-bold text-content"><BaseIcon name="mdi-tag-multiple-outline" :size="14" /> كلمات مفتاحية (ATS)</div>
              <div class="mb-3 flex flex-wrap gap-1">
                <BaseChip v-for="k in aiReview.atsKeywords" :key="k" color="neutral">{{ k }}</BaseChip>
              </div>

              <hr class="mb-3 border-ui">
              <div class="mb-2 flex items-center gap-1 text-xs font-bold text-content"><BaseIcon name="mdi-target" :size="14" /> تحليل مقابل فرصة</div>
              <BaseSelect :model-value="selectedOpportunity" :items="opportunityOptions" class="mb-2" @update:model-value="v => v && (selectedOpportunity = v)" />
              <ul class="mb-0 list-disc ps-5 text-xs text-content">
                <li v-for="(s, i) in vsSuggestions" :key="i">{{ s }}</li>
              </ul>
            </div>
          </div>

          <!-- Step 7: Export -->
          <div v-else-if="step === 7" class="py-6 text-center">
            <BaseAvatar color="success" tonal :size="80" class="mb-3">
              <BaseIcon name="mdi-check-circle-outline" :size="48" />
            </BaseAvatar>
            <h3 class="mb-1 text-lg font-bold text-content">سيرتك جاهزة!</h3>
            <p class="mb-4 text-sm text-muted">سمِّ سيرتك ثم احفظها أو صدّرها</p>
            <div class="mx-auto mb-4 max-w-[360px]">
              <BaseInput v-model="resumeName" :placeholder="`سيرة ${templateName}`" label="اسم السيرة" prefix-icon="mdi-file-account-outline" />
            </div>
            <!-- File exports -->
            <div class="mb-2 text-xs font-bold text-muted">تصدير كملف</div>
            <div class="mx-auto mb-2 grid max-w-[560px] grid-cols-2 gap-3 sm:grid-cols-4">
              <button v-for="f in exportFormats" :key="f.fmt" type="button" class="rounded-ui-lg border-ui p-3 text-center transition hover:bg-surfalt" @click="exportResume(f.fmt)">
                <BaseIcon :name="f.icon" :size="30" :style="{ color: colorVar(f.color) }" />
                <div class="mt-1 text-xs font-bold text-content">{{ f.label }}</div>
              </button>
            </div>

            <!-- Share -->
            <div class="mb-2 mt-4 text-xs font-bold text-muted">مشاركة</div>
            <div class="flex flex-wrap justify-center gap-2">
              <BaseButton variant="tonal-emerald" size="sm" @click="copyPublicLink"><BaseIcon name="mdi-link-variant" :size="16" />رابط عام</BaseButton>
              <BaseButton variant="tonal-emerald" size="sm" @click="openPrivateLink"><BaseIcon name="mdi-lock-outline" :size="16" />رابط خاص</BaseButton>
              <BaseButton variant="tonal-emerald" size="sm" @click="openQr"><BaseIcon name="mdi-qrcode" :size="16" />رمز QR</BaseButton>
            </div>
          </div>
        </BaseCard>

        <!-- Navigation -->
        <div class="flex justify-between">
          <BaseButton variant="outline" :disabled="step === 1" @click="prev">
            <BaseIcon name="mdi-arrow-right" :size="16" />السابق
          </BaseButton>
          <BaseButton v-if="step < totalSteps" variant="accent" @click="next">
            التالي<BaseIcon name="mdi-arrow-left" :size="16" />
          </BaseButton>
          <BaseButton v-else variant="emerald" @click="saveResume">
            <BaseIcon name="mdi-content-save" :size="16" />حفظ في حسابي
          </BaseButton>
        </div>
      </div>

      <!-- Right: live preview -->
      <div class="md:col-span-5">
        <div class="preview-panel">
          <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <BaseIcon name="mdi-eye-outline" :size="18" :style="{ color: 'rgb(var(--v-theme-accent))' }" />
              <span class="text-sm font-bold text-content">معاينة حيّة</span>
              <BaseChip color="accent">{{ templateName }}</BaseChip>
            </div>
            <span class="text-xs text-muted">تتحدّث فورًا</span>
          </div>

          <div class="resume-preview resume-print-target" :style="{ '--rp-accent': accentColor, fontSize: fontPx }" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Header -->
            <div class="rp-header" :class="headerMod">
              <div class="d-flex align-center ga-3" :class="{ 'justify-center': selectedTemplate === 'academic' }">
                <div v-if="withPhoto" class="rp-photo"><BaseIcon name="mdi-account" :size="24" /></div>
                <div>
                  <div class="rp-name">{{ displayName }}</div>
                  <div class="rp-title">{{ headline }}</div>
                  <div class="rp-contact">الرياض · {{ auth.authUser?.email || 'name@email.com' }}</div>
                </div>
              </div>
            </div>

            <div class="rp-body">
              <template v-if="enabled.summary">
                <div class="rp-section-title">{{ L.summary }}</div>
                <p class="rp-text">{{ summary }}</p>
              </template>

              <template v-if="enabled.skills">
                <div class="rp-section-title">{{ L.skills }}</div>
                <div class="rp-chips">
                  <span v-for="s in previewSkills" :key="s" class="rp-chip">{{ s }}</span>
                </div>
              </template>

              <template v-if="enabled.experience">
                <div class="rp-section-title">{{ L.experience }}</div>
                <div v-for="e in previewExperiences" :key="e.id" class="rp-item">
                  <div class="rp-item-head">{{ e.title }} — {{ e.company }}</div>
                  <div class="rp-item-sub">{{ e.period }}</div>
                  <div v-if="e.desc" class="rp-text">{{ e.desc }}</div>
                </div>
              </template>

              <template v-if="enabled.education">
                <div class="rp-section-title">{{ L.education }}</div>
                <div v-for="ed in previewEducation" :key="ed.degree" class="rp-item">
                  <div class="rp-item-head">{{ ed.degree }}</div>
                  <div class="rp-item-sub">{{ ed.school }} · {{ ed.year }}</div>
                </div>
              </template>

              <template v-if="enabled.projects">
                <div class="rp-section-title">{{ L.projects }}</div>
                <div v-for="p in previewProjects" :key="p.name" class="rp-item">
                  <div class="rp-item-head">{{ p.name }}</div>
                  <div class="rp-text">{{ p.desc }}</div>
                </div>
              </template>

              <template v-if="enabled.certificates">
                <div class="rp-section-title">{{ L.certificates }}</div>
                <div v-for="c in previewCertificates" :key="c.id" class="rp-item">
                  <div class="rp-item-head">{{ c.name }}</div>
                  <div class="rp-item-sub">{{ c.issuer }} · {{ c.date }}</div>
                </div>
              </template>

              <template v-if="enabled.languages">
                <div class="rp-section-title">{{ L.languages }}</div>
                <div class="rp-chips">
                  <span v-for="lang in previewLanguages" :key="lang" class="rp-chip">{{ lang }}</span>
                </div>
              </template>

              <template v-if="enabled.hobbies">
                <div class="rp-section-title">{{ L.hobbies }}</div>
                <div class="rp-chips">
                  <span v-for="h in previewHobbies" :key="h" class="rp-chip">{{ h }}</span>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QR dialog -->
    <BaseModal v-model="qrDialog" title="رمز QR للسيرة" :max-width="320">
      <div class="text-center">
        <img v-if="!qrError" :src="qrUrl" width="200" height="200" class="mx-auto my-2 rounded-ui" alt="QR" @error="qrError = true">
        <div v-else class="my-2 p-4 text-xs text-muted">يتطلب اتصالاً بالإنترنت لعرض الرمز.</div>
        <div class="mb-1 text-xs text-muted">امسح الرمز لفتح السيرة على الجوال.</div>
      </div>
      <template #actions>
        <BaseButton variant="ghost" block @click="qrDialog = false">إغلاق</BaseButton>
      </template>
    </BaseModal>

    <!-- Private link dialog -->
    <BaseModal v-model="privateDialog" title="رابط خاص محمي بكلمة مرور" :max-width="440">
      <BaseInput :model-value="publicLink" label="الرابط" readonly class="mb-2" dir="ltr" />
      <BaseInput :model-value="privatePassword" label="كلمة المرور" readonly dir="ltr">
        <template #suffix>
          <button class="icon-btn h-8 w-8" aria-label="توليد" @click="genPassword"><BaseIcon name="mdi-refresh" :size="18" /></button>
        </template>
      </BaseInput>
      <div class="mt-2 flex items-start gap-2 rounded-ui border-s-4 bg-surfalt p-2 text-xs text-content" :style="{ borderColor: 'rgb(var(--v-theme-info))' }">
        <BaseIcon name="mdi-information-outline" :size="16" :style="{ color: 'rgb(var(--v-theme-info))' }" />
        شارك الرابط وكلمة المرور مع الجهات المحددة فقط.
      </div>
      <template #actions>
        <BaseButton variant="ghost" @click="privateDialog = false">إغلاق</BaseButton>
        <BaseButton variant="accent" @click="copyPrivate"><BaseIcon name="mdi-content-copy" :size="16" />نسخ الرابط وكلمة المرور</BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar :model-value="!!toastMsg" color="primary" :timeout="3500" @update:model-value="toastMsg = ''">
      {{ toastMsg }}
    </BaseSnackbar>
  </div>
</template>

<style scoped>
.preview-panel {
  position: sticky;
  top: 84px;
}
@media (max-width: 959px) {
  .preview-panel {
    position: static;
    margin-top: 8px;
  }
}

/* The resume is a white "paper" document — fixed light colours in both app themes */
.resume-preview {
  background: #ffffff;
  color: #1f2430;
  border-radius: 12px;
  overflow: hidden auto;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.16);
  line-height: 1.5;
  max-height: 78vh;
}
.rp-header {
  padding: 18px 20px;
}
.rp-header--band {
  background: var(--rp-accent);
  color: #fff;
}
.rp-header--creative {
  border-bottom-left-radius: 26px;
}
.rp-header--dark {
  background: #1f2937;
  color: #fff;
}
.rp-header--dark .rp-name {
  color: var(--rp-accent);
}
.rp-header--line {
  border-bottom: 3px solid var(--rp-accent);
}
.rp-header--center {
  text-align: center;
}
.rp-name {
  font-size: 1.4em;
  font-weight: 800;
}
.rp-title {
  font-size: 0.95em;
  opacity: 0.9;
}
.rp-header--line .rp-title,
.rp-header--center .rp-title {
  color: var(--rp-accent);
  opacity: 1;
}
.rp-contact {
  font-size: 0.72em;
  opacity: 0.8;
  margin-top: 2px;
}
.rp-photo {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: #eef1f6;
  color: var(--rp-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.rp-header--band .rp-photo,
.rp-header--dark .rp-photo {
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
}
.rp-body {
  padding: 6px 20px 20px;
}
.rp-section-title {
  color: var(--rp-accent);
  font-weight: 800;
  font-size: 0.72em;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  margin: 15px 0 6px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  padding-bottom: 3px;
}
.rp-text {
  font-size: 0.86em;
  color: #444b58;
  margin: 0 0 4px;
}
.rp-item {
  margin-bottom: 8px;
}
.rp-item-head {
  font-weight: 700;
  font-size: 0.9em;
}
.rp-item-sub {
  font-size: 0.76em;
  color: #6b7280;
}
.rp-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.rp-chip {
  font-size: 0.74em;
  border: 1px solid var(--rp-accent);
  color: var(--rp-accent);
  border-radius: 999px;
  padding: 1px 9px;
}
</style>
