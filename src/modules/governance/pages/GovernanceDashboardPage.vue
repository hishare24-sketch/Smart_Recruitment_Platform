<script setup lang="ts">
import { computed, ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import { GOVERNANCE_META } from '@/services/personas'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'

// مركز الحوكمة — مراجعة المحتوى المهني + إدارة المجتمع (مجموعة قرب المدير).
const snackbar = ref('')

type ContentType = 'article' | 'course' | 'guide'
const CONTENT_META: Record<ContentType, { label: string, icon: string }> = {
  article: { label: 'مقال', icon: 'mdi-post-outline' },
  course: { label: 'مادة تدريبية', icon: 'mdi-school-outline' },
  guide: { label: 'دليل مهني', icon: 'mdi-book-open-variant' },
}

interface ReviewItem {
  id: number
  title: string
  type: ContentType
  author: string
  initial: string
  submitted: string
  status: 'pending' | 'approved' | 'rejected'
}
const queue = ref<ReviewItem[]>([
  { id: 1, title: 'خطة 90 يومًا للانتقال إلى التقنية', type: 'article', author: 'أ. هند الزهراني', initial: 'ه', submitted: 'قبل ساعتين', status: 'pending' },
  { id: 2, title: 'أساسيات اختبار الواجهات بـ Vitest', type: 'course', author: 'م. سلطان العمري', initial: 'س', submitted: 'قبل 5 ساعات', status: 'pending' },
  { id: 3, title: 'دليل بناء سلّم رواتب عادل', type: 'guide', author: 'د. ريم القحطاني', initial: 'ر', submitted: 'أمس', status: 'pending' },
  { id: 4, title: '5 أنماط Composition API', type: 'article', author: 'م. نوف الشهري', initial: 'ن', submitted: 'قبل يومين', status: 'pending' },
])

interface CommunityItem {
  id: number
  title: string
  kind: 'discussion' | 'event'
  flags: number
  resolved: boolean
}
const community = ref<CommunityItem[]>([
  { id: 1, title: 'نقاش: أفضل مسار لتعلّم الواجهات 2027', kind: 'discussion', flags: 2, resolved: false },
  { id: 2, title: 'فعالية: جلسة أسئلة مفتوحة مع مقيّمين معتمدين', kind: 'event', flags: 0, resolved: false },
  { id: 3, title: 'نقاش (مُبلَّغ عنه): محتوى ترويجي مخالف', kind: 'discussion', flags: 5, resolved: false },
])

const pendingCount = computed(() => queue.value.filter(q => q.status === 'pending').length)
const approvedCount = computed(() => queue.value.filter(q => q.status === 'approved').length)
const openCommunity = computed(() => community.value.filter(c => !c.resolved).length)

function review(item: ReviewItem, approve: boolean) {
  item.status = approve ? 'approved' : 'rejected'
  snackbar.value = approve ? `نُشر «${item.title}» بعد المراجعة` : `رُفض «${item.title}»`
}
function resolveCommunity(item: CommunityItem) {
  item.resolved = true
  snackbar.value = `عولج: «${item.title}»`
}
</script>

<template>
  <div>
    <PageHeader title="مركز الحوكمة" subtitle="مراجعة المحتوى المهني وإدارة المجتمع — جودة المنصة ونزاهتها" icon="mdi-shield-check-outline" />

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-3">
      <StatCard title="بانتظار المراجعة" :value="pendingCount" icon="mdi-file-clock-outline" color="warning" />
      <StatCard title="محتوى منشور" :value="approvedCount" icon="mdi-file-check-outline" color="success" />
      <StatCard title="بلاغات مجتمع مفتوحة" :value="openCommunity" icon="mdi-flag-outline" color="accent" class="col-span-2 md:col-span-1" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- طابور مراجعة المحتوى -->
      <div class="lg:col-span-2">
        <BaseCard>
          <h2 class="mb-1 flex items-center gap-2 font-bold"><BaseIcon name="mdi-file-check-outline" :size="20" style="color: rgb(var(--v-theme-secondary))" /> {{ GOVERNANCE_META.content_reviewer.label }}</h2>
          <p class="mb-3 text-xs text-muted">{{ GOVERNANCE_META.content_reviewer.desc }}</p>
          <template v-if="queue.filter(q => q.status === 'pending').length">
            <div v-for="item in queue.filter(q => q.status === 'pending')" :key="item.id" class="mb-2 rounded-ui border-ui p-3">
              <div class="mb-2 flex items-center gap-2">
                <BaseAvatar color="emerald" :size="34" tonal>{{ item.initial }}</BaseAvatar>
                <div class="min-w-0 flex-1">
                  <div class="truncate text-sm font-bold">{{ item.title }}</div>
                  <div class="text-xs text-muted">{{ item.author }} · {{ item.submitted }}</div>
                </div>
                <BaseChip color="neutral"><BaseIcon :name="CONTENT_META[item.type].icon" :size="12" /> {{ CONTENT_META[item.type].label }}</BaseChip>
              </div>
              <div class="flex gap-2">
                <BaseButton variant="emerald" size="sm" class="flex-1" @click="review(item, true)"><BaseIcon name="mdi-check" :size="16" /> اعتماد ونشر</BaseButton>
                <BaseButton variant="outline" size="sm" @click="review(item, false)"><BaseIcon name="mdi-close" :size="16" /> رفض</BaseButton>
              </div>
            </div>
          </template>
          <EmptyState v-else icon="mdi-check-all" title="لا محتوى بانتظار المراجعة" description="أنجزت الطابور — عمل رائع!" />
        </BaseCard>
      </div>

      <!-- إدارة المجتمع -->
      <div>
        <BaseCard>
          <h2 class="mb-1 flex items-center gap-2 font-bold"><BaseIcon name="mdi-account-group-outline" :size="20" style="color: rgb(var(--v-theme-accent))" /> {{ GOVERNANCE_META.community_guide.label }}</h2>
          <p class="mb-3 text-xs text-muted">{{ GOVERNANCE_META.community_guide.desc }}</p>
          <div v-for="item in community.filter(c => !c.resolved)" :key="item.id" class="mb-2 rounded-ui border-ui p-3">
            <div class="flex items-start gap-2">
              <BaseIcon :name="item.kind === 'event' ? 'mdi-calendar-star' : 'mdi-forum-outline'" :size="18" class="mt-0.5 text-muted" />
              <div class="flex-1">
                <div class="text-sm font-bold">{{ item.title }}</div>
                <BaseChip v-if="item.flags" color="error" class="mt-1"><BaseIcon name="mdi-flag" :size="12" /> {{ item.flags }} بلاغ</BaseChip>
                <BaseChip v-else color="success" class="mt-1">سليم</BaseChip>
              </div>
            </div>
            <BaseButton variant="tonal-brand" size="sm" block class="mt-2" @click="resolveCommunity(item)"><BaseIcon name="mdi-check-circle-outline" :size="16" /> معالجة</BaseButton>
          </div>
          <EmptyState v-if="!openCommunity" icon="mdi-shield-check-outline" title="المجتمع سليم" description="لا بلاغات مفتوحة حاليًا" />
        </BaseCard>
      </div>
    </div>

    <BaseSnackbar :model-value="!!snackbar" color="success" :timeout="2500" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </BaseSnackbar>
  </div>
</template>
