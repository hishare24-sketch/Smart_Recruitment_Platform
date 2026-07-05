import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { syncPrivateDoc } from '@/services/cloudSync'
import { EXPERT_TIER_META, expertTier, useExpertRolesStore } from '@/stores/ExpertRolesStore'
import { useGamificationStore } from '@/stores/GamificationStore'
import { useNotificationsStore } from '@/stores/NotificationsStore'

// ===== العلامة الشخصية للخبير (مرشد/مدرّب/مستشار) — نظير InterviewerBrandStore =====
// الملف العام التسويقي وأدوات النمو، مشتركة عبر أدوار الخبراء الثلاثة للمستخدم الحالي.

export interface ExpertPromo {
  id: number
  title: string
  kind: 'discount' | 'free_intro'
  pct?: number
  active: boolean
}
export interface ExpertBrandArticle {
  id: number
  title: string
  body: string
  status: 'review' | 'published'
  date: string
}
export interface ExpertPeerEndorsement {
  id: number
  peerName: string
  peerTitle: string
  peerInitial: string
  text: string
  date: string
  status: 'pending' | 'received'
  reciprocated: boolean
}
export interface ExpertBrandStory {
  id: number
  clientName: string
  clientInitial: string
  headline: string
  story: string
  status: 'awaiting_consent' | 'approved' | 'declined'
  date: string
}

interface ExpertBrandState {
  slug: string
  headline: string
  views: number
  shares: number
  favorites: number
  referrals: number
  referralCode: string
  /** سجل تاريخي قبل المنصة الحالية (لعرض الدرجة والإنجازات) */
  historicalEngagements: number
  promos: ExpertPromo[]
  articles: ExpertBrandArticle[]
  peerEndorsements: ExpertPeerEndorsement[]
  successStories: ExpertBrandStory[]
}

const STORAGE_KEY = 'expertBrand'

const seed: ExpertBrandState = {
  slug: 'my-expert-profile',
  headline: 'مرشد وخبير نمو مهني',
  views: 268,
  shares: 14,
  favorites: 22,
  referrals: 4,
  referralCode: 'EXPERT-REF',
  historicalEngagements: 34,
  promos: [
    { id: 1, title: 'جلسة تعارف مجانية (20 دقيقة) لتحديد الهدف', kind: 'free_intro', active: true },
    { id: 2, title: 'خصم 15% لأول برنامج إرشادي', kind: 'discount', pct: 15, active: true },
  ],
  articles: [
    { id: 1, title: 'خطة 90 يومًا لتحقيق قفزة مهنية', body: 'النمو المهني لا يحدث بالصدفة بل بخطة قابلة للقياس: حدّد الوجهة، فكّكها لمهام أسبوعية، وقِس التقدّم بمخرجات حقيقية لا بالساعات...', status: 'published', date: '2026-06-20' },
  ],
  peerEndorsements: [
    { id: 1, peerName: 'م. نوف الشهري', peerTitle: 'مدربة TypeScript معتمدة', peerInitial: 'ن', text: 'من أفضل من يحوّل الخبرة إلى خطة عملية — عملاؤه يخرجون بأثر ملموس لا بنصائح عامة.', date: '2026-06-21', status: 'received', reciprocated: true },
    { id: 2, peerName: 'د. ريم القحطاني', peerTitle: 'مستشارة قيادة وموارد بشرية', peerInitial: 'ر', text: 'إرشاد مبني على بيانات ونتائج، أرشّحه بثقة لكل من يخطّط لانتقال مهني جادّ.', date: '2026-06-12', status: 'received', reciprocated: false },
  ],
  successStories: [
    { id: 1, clientName: 'نورة المطيري', clientInitial: 'ن', headline: 'من الدعم الفني إلى تحليل البيانات', story: 'بعد برنامج تحوّل من ثلاث مراحل، بنت نورة محفظة مشاريع واجتازت مقابلتها لتحصل على دور محلّلة بيانات براتب أعلى.', status: 'approved', date: '2026-06-16' },
  ],
}

function load(): ExpertBrandState {
  try {
    return { ...structuredClone(seed), ...JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}') }
  }
  catch {
    return structuredClone(seed)
  }
}

let nextId = 600

export const useExpertBrandStore = defineStore('expertBrand', () => {
  const state = ref<ExpertBrandState>(load())
  watch(state, v => localStorage.setItem(STORAGE_KEY, JSON.stringify(v)), { deep: true })

  // مزامنة سحابية خاصة — بجلسة حقيقية فقط (DOC/CLOUD_SYNC.md)
  const { status: syncStatus } = syncPrivateDoc({
    store: 'expertBrand',
    snapshot: () => state.value,
    apply: (incoming) => {
      if (incoming && typeof incoming === 'object')
        state.value = { ...state.value, ...(incoming as Partial<ExpertBrandState>) }
    },
    source: state,
  })

  const expertRoles = useExpertRolesStore()

  // ===== إجمالي العملاء المخدومين (يحدّد الدرجة) =====
  const engagements = computed(() => {
    const s = state.value
    const live
      = expertRoles.state.coachClients.length
      + expertRoles.state.courses.reduce((sum, c) => sum + c.enrolled, 0)
      + expertRoles.state.consulting.filter(c => c.status === 'done' || c.status === 'in_progress').length
    return s.historicalEngagements + live
  })
  const tier = computed(() => EXPERT_TIER_META[expertTier(engagements.value)])

  // ===== مؤشرات لوحة التسويق =====
  const marketingStats = computed(() => ({
    views: state.value.views,
    shares: state.value.shares,
    favorites: state.value.favorites,
    referrals: state.value.referrals,
  }))
  function recordView() {
    state.value.views++
  }
  function recordShare() {
    state.value.shares++
  }

  /** إنجازات مشتقة من الأثر الحقيقي — شارات قابلة للمشاركة */
  const achievements = computed(() => {
    const n = engagements.value
    const list: { id: string, label: string, icon: string, earned: boolean }[] = [
      { id: 'e10', label: 'أول 10 عملاء', icon: 'mdi-flag-checkered', earned: n >= 10 },
      { id: 'e40', label: 'خبير راسخ — 40 عميلًا', icon: 'mdi-star-outline', earned: n >= 40 },
      { id: 'e90', label: 'خبير معتمد — 90 عميلًا', icon: 'mdi-shield-star-outline', earned: n >= 90 },
      { id: 'active_offer', label: 'عرض ترويجي نشط', icon: 'mdi-tag-outline', earned: state.value.promos.some(p => p.active) },
    ]
    return list
  })
  /** سفير المنصة: خبراء بأثر واسع */
  const isAmbassador = computed(() => engagements.value >= EXPERT_TIER_META.certified.min)

  // ===== العروض الترويجية =====
  function addPromo(p: Omit<ExpertPromo, 'id' | 'active'>) {
    state.value.promos.push({ ...p, id: nextId++, active: true })
  }
  function togglePromo(id: number) {
    const p = state.value.promos.find(x => x.id === id)
    if (p)
      p.active = !p.active
  }
  function removePromo(id: number) {
    state.value.promos = state.value.promos.filter(p => p.id !== id)
  }
  const activePromos = computed(() => state.value.promos.filter(p => p.active))

  // ===== المقالات (بمراجعة المنصة ثم النشر) =====
  function submitArticle(title: string, body: string) {
    const a: ExpertBrandArticle = { id: nextId++, title, body, status: 'review', date: new Date().toISOString().slice(0, 10) }
    state.value.articles.unshift(a)
    setTimeout(() => {
      const art = state.value.articles.find(x => x.id === a.id)
      if (art && art.status === 'review') {
        art.status = 'published'
        useNotificationsStore().push({ icon: 'mdi-post-outline', color: 'success', title: 'نُشر مقالك بعد المراجعة', body: `«${art.title}» أصبح ظاهرًا في ملفك العام`, category: 'system' })
      }
    }, 8000)
    return a
  }
  const publishedArticles = computed(() => state.value.articles.filter(a => a.status === 'published'))

  // ===== برنامج الدعوة =====
  const referralLink = computed(() =>
    `${window.location.origin}${import.meta.env.BASE_URL}register?ref=${state.value.referralCode}`,
  )
  function creditReferral() {
    state.value.referrals++
    useGamificationStore().award(50, 'انضم عميل جديد عبر رابط دعوتك')
    useNotificationsStore().push({ icon: 'mdi-account-plus-outline', color: 'success', title: 'إحالة ناجحة! +50 نقطة', body: 'انضم عميل جديد عبر رابط دعوتك — شكرًا لكونك شريك نمو.', category: 'system' })
  }

  // ===== توصيات الزملاء المتبادلة =====
  const receivedPeerEndorsements = computed(() => state.value.peerEndorsements.filter(e => e.status === 'received'))
  function requestPeerEndorsement(peerName: string, peerTitle: string, peerInitial: string) {
    const e: ExpertPeerEndorsement = { id: nextId++, peerName, peerTitle, peerInitial, text: '', date: new Date().toISOString().slice(0, 10), status: 'pending', reciprocated: false }
    state.value.peerEndorsements.unshift(e)
    setTimeout(() => {
      const cur = state.value.peerEndorsements.find(x => x.id === e.id)
      if (!cur || cur.status !== 'pending')
        return
      cur.status = 'received'
      cur.text = 'خبرة عميقة وأثر ملموس على من يعمل معه — أوصي به بثقة.'
      useNotificationsStore().push({ icon: 'mdi-account-heart-outline', color: 'success', title: 'وصلتك توصية زميل', body: `${peerName} أضاف توصية لملفك العام — ردّ الجميل بتوصية متبادلة.`, category: 'endorsement' })
    }, 10000)
    return e
  }
  function reciprocatePeerEndorsement(id: number) {
    const e = state.value.peerEndorsements.find(x => x.id === id)
    if (!e || e.status !== 'received' || e.reciprocated)
      return
    e.reciprocated = true
    useGamificationStore().award(20, `أوصيت بزميلك ${e.peerName} — توصية متبادلة`)
  }

  // ===== قصص النجاح (بموافقة صاحبها) =====
  const approvedStories = computed(() => state.value.successStories.filter(s => s.status === 'approved'))
  function addSuccessStory(clientName: string, headline: string, story: string) {
    const s: ExpertBrandStory = { id: nextId++, clientName, clientInitial: clientName.trim().charAt(0), headline, story, status: 'awaiting_consent', date: new Date().toISOString().slice(0, 10) }
    state.value.successStories.unshift(s)
    setTimeout(() => {
      const cur = state.value.successStories.find(x => x.id === s.id)
      if (!cur || cur.status !== 'awaiting_consent')
        return
      cur.status = 'approved'
      useNotificationsStore().push({ icon: 'mdi-check-decagram-outline', color: 'success', title: 'وافق العميل على نشر قصته', body: `«${s.headline}» أصبحت ظاهرة في ملفك العام.`, category: 'system' })
    }, 10000)
    return s
  }
  function removeSuccessStory(id: number) {
    state.value.successStories = state.value.successStories.filter(s => s.id !== id)
  }

  const publicUrl = computed(() => `${window.location.origin}${import.meta.env.BASE_URL}experts/${state.value.slug}`)
  function shareOnLinkedIn() {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(publicUrl.value)}`, '_blank', 'noopener')
    recordShare()
  }

  return {
    state, syncStatus,
    engagements, tier,
    marketingStats, recordView, recordShare,
    achievements, isAmbassador,
    addPromo, togglePromo, removePromo, activePromos,
    submitArticle, publishedArticles,
    referralLink, creditReferral,
    receivedPeerEndorsements, requestPeerEndorsement, reciprocatePeerEndorsement,
    approvedStories, addSuccessStory, removeSuccessStory,
    publicUrl, shareOnLinkedIn,
  }
})
