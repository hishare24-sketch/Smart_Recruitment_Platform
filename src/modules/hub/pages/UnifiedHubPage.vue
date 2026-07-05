<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import type { UserRole } from '@/interfaces/Auth'
import { ROLE_META } from '@/services/roles'
import { useAuthStore } from '@/stores/AuthStore'
import type { HubGroupKey, HubSortKey, WorkItem, WorkItemUrgency } from '@/stores/UnifiedHubStore'
import { KIND_META, URGENCY_META, filterItems, groupItems, sortItems, useUnifiedHubStore } from '@/stores/UnifiedHubStore'
import { useNotificationsStore } from '@/stores/NotificationsStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseSwitch from '@/components/ui/BaseSwitch.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseDropdown from '@/components/ui/BaseDropdown.vue'

// ===== المركز الموحّد — كل أدوارك في شاشة واحدة: قرارات، مواعيد، ومؤشرات =====
const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const hub = useUnifiedHubStore()
const notifications = useNotificationsStore()

const roleLabel = (r: UserRole) => t(`roles.${r}`)

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', orange: 'warning', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}
function toggleStyle(active: boolean, color = 'primary') {
  if (active)
    return { background: `rgb(var(--v-theme-${color}))`, color: `rgb(var(--v-theme-on-${color}))`, borderColor: 'transparent' }
  return { background: 'transparent', color: 'rgba(var(--v-theme-on-surface), 0.75)', borderColor: 'rgba(var(--v-theme-on-surface), 0.2)' }
}

// —— حالة التحكم (تقسيم/فلترة/فرز/كثافة) ——
const groupBy = ref<HubGroupKey>('urgency')
const sortBy = ref<HubSortKey>('priority')
const roleFilter = ref<UserRole[]>([])
const urgencyFilter = ref<WorkItemUrgency[]>([])
const query = ref('')
const density = ref<'comfortable' | 'compact'>('comfortable')

const GROUP_OPTIONS: { value: HubGroupKey, label: string, icon: string }[] = [
  { value: 'urgency', label: 'حسب الاستعجال', icon: 'mdi-fire' },
  { value: 'role', label: 'حسب الدور', icon: 'mdi-account-convert-outline' },
  { value: 'kind', label: 'حسب النوع', icon: 'mdi-shape-outline' },
  { value: 'none', label: 'بلا تجميع', icon: 'mdi-view-list-outline' },
]
const SORT_OPTIONS: { value: HubSortKey, label: string }[] = [
  { value: 'priority', label: 'الأولوية' },
  { value: 'amount', label: 'القيمة المالية' },
  { value: 'recent', label: 'الأحدث' },
]
const SORT_ITEMS = SORT_OPTIONS.map(o => ({ value: o.value, title: o.label }))
const URGENCY_ENTRIES = Object.entries(URGENCY_META) as [WorkItemUrgency, typeof URGENCY_META[WorkItemUrgency]][]

const ownedRoles = computed(() => hub.roleSummaries.map(r => r.role))

function toggleRole(r: UserRole) {
  roleFilter.value = roleFilter.value.includes(r) ? roleFilter.value.filter(x => x !== r) : [...roleFilter.value, r]
  activeViewId.value = null
}
function toggleUrgency(u: WorkItemUrgency) {
  urgencyFilter.value = urgencyFilter.value.includes(u) ? urgencyFilter.value.filter(x => x !== u) : [...urgencyFilter.value, u]
  activeViewId.value = null
}

const visibleItems = computed(() => {
  const filtered = filterItems(hub.allItems, {
    roles: roleFilter.value,
    urgencies: urgencyFilter.value,
    query: query.value,
  })
  return sortItems(filtered, sortBy.value)
})
const groups = computed(() => groupItems(visibleItems.value, groupBy.value))

function groupLabel(key: string): string {
  if (groupBy.value === 'role')
    return roleLabel(key as UserRole)
  if (groupBy.value === 'urgency')
    return URGENCY_META[key as WorkItemUrgency].label
  if (groupBy.value === 'kind')
    return KIND_META[key as keyof typeof KIND_META].label
  return 'كل العناصر'
}

// —— طرق العرض المحفوظة ——
interface SavedView {
  id: number
  name: string
  groupBy: HubGroupKey
  sortBy: HubSortKey
  roles: UserRole[]
  urgencies: WorkItemUrgency[]
  density: 'comfortable' | 'compact'
}
const VIEWS_KEY = 'hubViews'
function loadViews(): SavedView[] {
  try {
    return JSON.parse(localStorage.getItem(VIEWS_KEY) ?? '[]')
  }
  catch {
    return []
  }
}
const savedViews = ref<SavedView[]>(loadViews())
watch(savedViews, v => localStorage.setItem(VIEWS_KEY, JSON.stringify(v)), { deep: true })

const viewDialog = ref(false)
const newViewName = ref('')
const activeViewId = ref<number | null>(null)
function saveCurrentView() {
  if (!newViewName.value.trim())
    return
  const v: SavedView = {
    id: Date.now(),
    name: newViewName.value.trim(),
    groupBy: groupBy.value,
    sortBy: sortBy.value,
    roles: [...roleFilter.value],
    urgencies: [...urgencyFilter.value],
    density: density.value,
  }
  savedViews.value.push(v)
  activeViewId.value = v.id
  viewDialog.value = false
  newViewName.value = ''
}
function applyView(v: SavedView) {
  groupBy.value = v.groupBy
  sortBy.value = v.sortBy
  roleFilter.value = [...v.roles]
  urgencyFilter.value = [...v.urgencies]
  density.value = v.density
  activeViewId.value = v.id
}
function removeView(id: number) {
  savedViews.value = savedViews.value.filter(v => v.id !== id)
  if (activeViewId.value === id)
    activeViewId.value = null
}
function resetControls() {
  groupBy.value = 'urgency'
  sortBy.value = 'priority'
  roleFilter.value = []
  urgencyFilter.value = []
  query.value = ''
  activeViewId.value = null
}

// —— تخصيص الأقسام (إظهار/إخفاء) ——
const SECTIONS_KEY = 'hubSections'
interface SectionPrefs { kpis: boolean, inbox: boolean, roleCards: boolean }
function loadSections(): SectionPrefs {
  try {
    return { kpis: true, inbox: true, roleCards: true, ...JSON.parse(localStorage.getItem(SECTIONS_KEY) ?? '{}') }
  }
  catch {
    return { kpis: true, inbox: true, roleCards: true }
  }
}
const sections = ref<SectionPrefs>(loadSections())
watch(sections, v => localStorage.setItem(SECTIONS_KEY, JSON.stringify(v)), { deep: true })

// —— الإجراء المباشر من الصندوق ——
function resolve(item: WorkItem, accept: boolean) {
  const handled = hub.resolveItem(item, accept)
  if (!handled) {
    router.push(item.actionTo)
    return
  }
  notifications.push({
    icon: item.icon,
    color: accept ? 'success' : 'error',
    title: accept ? `قبلت: ${KIND_META[item.kind].label}` : `اعتذرت: ${KIND_META[item.kind].label}`,
    body: `${item.party} — ${item.title}`,
    category: 'system',
    actionTo: item.actionTo,
    actionLabel: 'فتح التفاصيل',
  })
}
function open(item: WorkItem) {
  router.push(item.actionTo)
}
/** الأنواع التي تقبل قرارًا فوريًا من الصندوق */
const DECIDABLE: WorkItem['kind'][] = ['interview_request', 'peer_request', 'consulting_request', 'wish_incoming', 'offer_incoming', 'role_approval']
function isDecidable(item: WorkItem) {
  return DECIDABLE.includes(item.kind) && item.urgency === 'action'
}

const kpiCards = computed(() => [
  { label: 'يحتاج قرارك', value: hub.kpis.actionCount, icon: 'mdi-gesture-tap-button', color: 'error' },
  { label: 'مواعيد قادمة', value: hub.kpis.upcomingCount, icon: 'mdi-calendar-clock-outline', color: 'info' },
  { label: 'أموال بانتظار قرارك', value: `${hub.kpis.pendingMoney} ﷼`, icon: 'mdi-cash-clock', color: 'warning' },
  { label: 'أرباحك عبر الأدوار', value: `${hub.kpis.earnings} ﷼`, icon: 'mdi-cash-multiple', color: 'success' },
])

// —— وضوح أول زيارة: تحية بلغة الفائدة + بطاقة تعريف تُغلق مرة واحدة ——
const greeting = computed(() => {
  const parts: string[] = []
  if (hub.kpis.actionCount)
    parts.push(`${hub.kpis.actionCount} قرارات تنتظرك`)
  if (hub.kpis.upcomingCount)
    parts.push(`${hub.kpis.upcomingCount} مواعيد قادمة`)
  const scope = hub.kpis.activeRoles > 1 ? ` عبر ${hub.kpis.activeRoles} أدوار` : ''
  return parts.length ? `${parts.join(' و')}${scope}` : 'لا شيء يحتاج انتباهك الآن — كل شيء تحت السيطرة'
})

const INTRO_KEY = 'hubIntroSeen'
const showIntro = ref(localStorage.getItem(INTRO_KEY) !== '1')
function dismissIntro() {
  showIntro.value = false
  localStorage.setItem(INTRO_KEY, '1')
}

// —— كشف تدريجي: أدوات التصفية تُطوى حتى تكبر القائمة أو يطلبها المستخدم ——
const controlsOpen = ref(hub.allItems.length > 8)
const activeFiltersCount = computed(() =>
  (roleFilter.value.length ? 1 : 0) + (urgencyFilter.value.length ? 1 : 0) + (query.value.trim() ? 1 : 0),
)
</script>

<template>
  <div>
    <PageHeader
      title="مركزك"
      :subtitle="greeting"
      icon="mdi-view-dashboard-variant-outline"
    >
      <template #actions>
        <BaseDropdown align="end" :close-on-content="false">
          <template #trigger="{ toggle }">
            <button class="icon-btn" aria-label="الإعدادات" @click="toggle"><BaseIcon name="mdi-cog-outline" :size="20" /></button>
          </template>
          <div class="min-w-[240px] p-3">
            <div class="mb-2 text-sm font-bold text-content">أقسام المركز</div>
            <BaseSwitch v-model="sections.kpis" label="المؤشرات" />
            <BaseSwitch v-model="sections.inbox" label="صندوق العمل" />
            <BaseSwitch v-model="sections.roleCards" label="بطاقات الأدوار" />
          </div>
        </BaseDropdown>
      </template>
    </PageHeader>

    <!-- بطاقة تعريف أول زيارة — تُغلق وتُحفظ -->
    <div v-if="showIntro" class="mb-4 rounded-ui-lg p-4" style="background: rgba(var(--v-theme-primary), 0.12); color: rgb(var(--v-theme-on-surface))">
      <div class="flex items-start gap-3">
        <BaseIcon name="mdi-lightbulb-on-outline" :size="22" class="mt-1" :style="{ color: 'rgb(var(--v-theme-primary))' }" />
        <div class="flex-1">
          <div class="mb-2 text-sm font-bold text-content">هذه صفحتك الأولى كل يوم — ثلاث حقائق تكفيك:</div>
          <div class="flex flex-col gap-1 text-sm text-content">
            <span><BaseIcon name="mdi-inbox-arrow-down-outline" :size="16" class="me-1" />كل طلب أو موعد من <b>أي دور من أدوارك</b> يصل هنا تلقائيًا — لا حاجة للتنقل بين اللوحات.</span>
            <span><BaseIcon name="mdi-gesture-tap-button" :size="16" class="me-1" />زرا <b>قبول/رفض</b> ينفّذان القرار فورًا في مكانه الصحيح، والسهم يفتح التفاصيل.</span>
            <span><BaseIcon name="mdi-open-in-app" :size="16" class="me-1" />للعمل المتعمق افتح <b>اللوحة الكاملة</b> من بطاقة الدور بالأسفل.</span>
          </div>
        </div>
        <button class="icon-btn h-8 w-8" aria-label="إغلاق" @click="dismissIntro"><BaseIcon name="mdi-close" :size="18" /></button>
      </div>
    </div>

    <!-- KPIs عابرة للأدوار -->
    <div v-if="sections.kpis" class="mb-1 grid grid-cols-2 gap-4 lg:grid-cols-4">
      <BaseCard v-for="k in kpiCards" :key="k.label" class="flex items-center gap-3">
        <BaseAvatar :color="mapColor(k.color)" tonal square :size="44">
          <BaseIcon :name="k.icon" :size="22" />
        </BaseAvatar>
        <div>
          <div class="text-lg font-bold text-content">{{ k.value }}</div>
          <div class="text-xs text-muted">{{ k.label }}</div>
        </div>
      </BaseCard>
    </div>

    <!-- بطاقات الأدوار النشطة -->
    <div v-if="sections.roleCards" class="mb-1 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <BaseCard v-for="r in hub.roleSummaries" :key="r.role" class="flex h-full flex-col">
        <div class="mb-2 flex items-center gap-2">
          <BaseAvatar color="brand" tonal :size="36">
            <BaseIcon :name="ROLE_META[r.role].icon" :size="18" />
          </BaseAvatar>
          <span class="flex-1 font-bold text-content">{{ roleLabel(r.role) }}</span>
          <BaseChip v-if="auth.role === r.role" color="success">النشط</BaseChip>
        </div>
        <div class="mb-3 flex flex-wrap gap-1">
          <BaseChip v-for="f in r.facts" :key="f" color="emerald">{{ f }}</BaseChip>
        </div>
        <BaseButton size="sm" variant="tonal-brand" block class="mt-auto" :to="{ name: r.home }">
          <BaseIcon name="mdi-open-in-app" :size="16" />افتح اللوحة الكاملة
        </BaseButton>
      </BaseCard>
    </div>

    <!-- صندوق العمل الموحّد + التحكم المتقدم -->
    <BaseCard v-if="sections.inbox">
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <BaseIcon name="mdi-inbox-full-outline" :size="22" :style="{ color: 'rgb(var(--v-theme-primary))' }" />
        <h2 class="text-base font-bold text-content">صندوق العمل ({{ visibleItems.length }})</h2>
        <span class="flex-1" />
        <BaseButton
          size="sm"
          :variant="controlsOpen || activeFiltersCount ? 'tonal-brand' : 'outline'"
          @click="controlsOpen = !controlsOpen"
        >
          <BaseIcon :name="controlsOpen ? 'mdi-chevron-up' : 'mdi-tune-variant'" :size="16" />
          تصفية وتنظيم
          <span v-if="activeFiltersCount" class="ms-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold" style="background: rgb(var(--v-theme-primary)); color: rgb(var(--v-theme-on-primary))">{{ activeFiltersCount }}</span>
        </BaseButton>
        <div class="seg">
          <button type="button" class="seg-btn" :class="{ 'is-active': density === 'comfortable' }" aria-label="مريح" @click="density = 'comfortable'"><BaseIcon name="mdi-view-agenda-outline" :size="16" /></button>
          <button type="button" class="seg-btn" :class="{ 'is-active': density === 'compact' }" aria-label="مضغوط" @click="density = 'compact'"><BaseIcon name="mdi-view-headline" :size="16" /></button>
        </div>
      </div>

      <div v-if="controlsOpen">
        <!-- شريط الفلاتر -->
        <div class="mb-2 grid grid-cols-1 gap-2 md:grid-cols-2">
          <BaseInput v-model="query" placeholder="بحث بالاسم أو الموضوع..." prefix-icon="mdi-magnify" />
          <div>
            <label class="mb-1 block text-xs font-medium text-muted">فرز</label>
            <BaseSelect :model-value="sortBy" :items="SORT_ITEMS" @update:model-value="v => v && (sortBy = v)" />
          </div>
        </div>
        <div class="mb-2 flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-muted">الأدوار:</span>
          <button
            v-for="r in ownedRoles"
            :key="r"
            type="button"
            class="rounded-full border px-2.5 py-1 text-xs font-medium transition"
            :style="toggleStyle(roleFilter.includes(r))"
            @click="toggleRole(r)"
          >{{ roleLabel(r) }}</button>
        </div>
        <div class="mb-3 flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-muted">الحالة:</span>
          <button
            v-for="[u, m] in URGENCY_ENTRIES"
            :key="u"
            type="button"
            class="rounded-full border px-2.5 py-1 text-xs font-medium transition"
            :style="toggleStyle(urgencyFilter.includes(u))"
            @click="toggleUrgency(u)"
          >{{ m.label }}</button>
        </div>

        <!-- التجميع + طرق العرض المحفوظة -->
        <div class="mb-3 flex flex-wrap items-center gap-2">
          <div class="seg flex-wrap">
            <button v-for="g in GROUP_OPTIONS" :key="g.value" type="button" class="seg-btn flex items-center gap-1" :class="{ 'is-active': groupBy === g.value }" @click="groupBy = g.value"><BaseIcon :name="g.icon" :size="16" />{{ g.label }}</button>
          </div>
          <span class="flex-1" />
          <span
            v-for="v in savedViews"
            :key="v.id"
            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium"
            :style="activeViewId === v.id ? { background: 'rgb(var(--v-theme-primary))', color: 'rgb(var(--v-theme-on-primary))' } : { background: 'rgba(var(--v-theme-secondary), 0.16)', color: 'rgb(var(--v-theme-secondary))' }"
          >
            <BaseIcon name="mdi-bookmark-outline" :size="14" />
            <button type="button" @click="applyView(v)">{{ v.name }}</button>
            <button type="button" class="leading-none" aria-label="حذف" @click="removeView(v.id)"><BaseIcon name="mdi-close" :size="13" /></button>
          </span>
          <BaseButton size="sm" variant="tonal-emerald" @click="viewDialog = true"><BaseIcon name="mdi-bookmark-plus-outline" :size="14" />حفظ العرض</BaseButton>
          <BaseButton size="sm" variant="ghost" @click="resetControls"><BaseIcon name="mdi-backup-restore" :size="14" />إعادة الضبط</BaseButton>
        </div>
      </div>

      <!-- المجموعات -->
      <template v-for="g in groups" :key="g.key">
        <div class="mb-2 mt-4 flex items-center gap-2">
          <BaseChip color="brand">{{ groupLabel(g.key) }}</BaseChip>
          <span class="text-xs text-muted">{{ g.items.length }} عنصرًا</span>
          <hr class="flex-1 border-ui">
        </div>
        <div class="flex flex-col" :class="density === 'compact' ? 'gap-1' : 'gap-2'">
          <div
            v-for="item in g.items"
            :key="item.id"
            class="work-item rounded-ui-lg border"
            :class="density === 'compact' ? 'p-2' : 'p-3'"
          >
            <div class="flex flex-wrap items-center gap-3">
              <BaseAvatar :color="mapColor(item.color)" tonal :size="density === 'compact' ? 32 : 42">
                <BaseIcon :name="item.icon" :size="density === 'compact' ? 16 : 20" />
              </BaseAvatar>
              <div class="flex-1" style="min-width: 200px">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-sm font-bold text-content">{{ item.party }}</span>
                  <BaseChip :color="mapColor(item.color)">{{ KIND_META[item.kind].label }}</BaseChip>
                  <BaseChip color="emerald">{{ roleLabel(item.role) }}</BaseChip>
                </div>
                <div class="truncate text-xs text-muted" style="max-width: 520px">{{ item.title }}</div>
                <div v-if="density === 'comfortable'" class="text-xs text-muted">
                  {{ item.dateLabel }}<template v-if="item.amountLabel"> · <b>{{ item.amountLabel }}</b></template>
                </div>
              </div>
              <BaseChip :color="mapColor(item.statusColor)">{{ item.status }}</BaseChip>
              <div class="flex items-center gap-1">
                <template v-if="isDecidable(item)">
                  <BaseButton size="sm" variant="tonal-emerald" @click="resolve(item, true)"><BaseIcon name="mdi-check" :size="16" />قبول</BaseButton>
                  <button class="icon-btn h-9 w-9" style="color: rgb(var(--v-theme-error))" aria-label="رفض" @click="resolve(item, false)"><BaseIcon name="mdi-close" :size="18" /></button>
                  <button class="icon-btn h-9 w-9" style="color: rgb(var(--v-theme-primary))" aria-label="فتح التفاصيل" title="فتح التفاصيل" @click="open(item)"><BaseIcon name="mdi-arrow-left-circle-outline" :size="18" /></button>
                </template>
                <BaseButton
                  v-else-if="item.urgency === 'action'"
                  size="sm"
                  variant="tonal-brand"
                  @click="open(item)"
                >
                  <BaseIcon name="mdi-open-in-app" :size="16" />إتمام في صفحته
                </BaseButton>
                <button v-else class="icon-btn h-9 w-9" style="color: rgb(var(--v-theme-primary))" aria-label="فتح التفاصيل" title="فتح التفاصيل" @click="open(item)"><BaseIcon name="mdi-arrow-left-circle-outline" :size="18" /></button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="!visibleItems.length" class="py-10 text-center text-muted">
        <BaseIcon name="mdi-inbox-remove-outline" :size="48" />
        <div class="mt-2 text-sm">لا عناصر مطابقة — عدّل الفلاتر أو أعد الضبط.</div>
      </div>
    </BaseCard>

    <!-- حفظ طريقة عرض -->
    <BaseModal v-model="viewDialog" title="حفظ طريقة العرض الحالية" :max-width="400">
      <BaseInput v-model="newViewName" label="اسم طريقة العرض" placeholder="قرارات اليوم / أموالي المعلّقة..." @keyup.enter="saveCurrentView" />
      <p class="mb-0 mt-2 text-xs text-muted">تُحفظ الفلاتر والفرز والتجميع والكثافة الحالية وتظهر كاختصار دائم.</p>
      <template #actions>
        <BaseButton variant="ghost" @click="viewDialog = false">إلغاء</BaseButton>
        <BaseButton variant="brand" :disabled="!newViewName.trim()" @click="saveCurrentView"><BaseIcon name="mdi-bookmark-plus-outline" :size="16" />حفظ</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.work-item {
  border-color: rgba(140, 163, 150, 0.25);
  transition: border-color 0.15s ease;
}
.work-item:hover {
  border-color: rgb(var(--v-theme-primary));
}
</style>
