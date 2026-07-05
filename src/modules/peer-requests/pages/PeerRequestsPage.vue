<script setup lang="ts">
import { computed, ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import { ai } from '@/services/ai'
import { ALL_SKILLS } from '@/services/taxonomy'
import {
  PEER_DIRECTORY,
  PEER_STATUS_META,
  PEER_TYPE_META,
  usePeerRequestsStore,
} from '@/stores/PeerRequestsStore'
import type { PeerRequestType } from '@/stores/PeerRequestsStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseMultiSelect from '@/components/ui/BaseMultiSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'

const store = usePeerRequestsStore()
const tab = ref<'incoming' | 'outgoing'>('incoming')

const types = Object.keys(PEER_TYPE_META) as PeerRequestType[]

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function mapColor(c?: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald', 'medium-emphasis': 'neutral', 'surface-variant': 'neutral', grey: 'neutral', orange: 'warning', amber: 'warning' } as Record<string, BaseColor>)[c ?? ''] ?? c ?? 'brand') as BaseColor
}
// رمز ثيم صالح للنغمة الصلبة (يجب أن يوجد on-${color}) — تطبيع orange/surface-variant/grey
function normColor(c?: string) {
  return ({ orange: 'warning', 'surface-variant': 'primary', grey: 'primary', 'medium-emphasis': 'primary' } as Record<string, string>)[c ?? ''] ?? c ?? 'primary'
}
function toggleStyle(active: boolean, color = 'primary') {
  if (active)
    return { background: `rgb(var(--v-theme-${color}))`, color: `rgb(var(--v-theme-on-${color}))`, borderColor: 'transparent' }
  return { background: 'transparent', color: 'rgba(var(--v-theme-on-surface), 0.75)', borderColor: 'rgba(var(--v-theme-on-surface), 0.2)' }
}

const PERSON_OPTIONS = PEER_DIRECTORY.map(p => ({ value: p.name, title: `${p.name} — ${p.role}` }))

// — New request dialog —
const dialog = ref(false)
const form = ref<{ person: string, type: PeerRequestType, reason: string, skills: string[], attachments: string[] }>({
  person: '',
  type: 'recommendation',
  reason: '',
  skills: [],
  attachments: [],
})
const aiTip = computed(() => ai.peerRequestTip(form.value.type))
const canSend = computed(() => !!form.value.person && form.value.reason.trim().length > 3)
const snackbar = ref('')

function openNew(type?: PeerRequestType) {
  form.value = { person: '', type: type ?? 'recommendation', reason: '', skills: [], attachments: [] }
  dialog.value = true
}
function addMockAttachment() {
  form.value.attachments.push(`مستند_داعم_${form.value.attachments.length + 1}.pdf`)
}
function send() {
  const person = PEER_DIRECTORY.find(p => p.name === form.value.person)
  store.create({
    type: form.value.type,
    personName: form.value.person,
    personRole: person?.role ?? 'مستخدم',
    reason: form.value.reason.trim(),
    skills: form.value.skills,
    attachments: form.value.attachments,
  })
  dialog.value = false
  tab.value = 'outgoing'
  snackbar.value = 'تم إرسال الطلب — ستُشعر عند الرد عليه.'
}
</script>

<template>
  <div>
    <PageHeader
      title="الطلبات المتبادلة"
      subtitle="اطلب توصية أو تزكية أو تقييمًا أو استشارة من أي مستخدم — وأدر ما يردك في مكان واحد"
      icon="mdi-swap-horizontal-circle-outline"
    >
      <template #actions>
        <BaseButton variant="accent" @click="openNew()"><BaseIcon name="mdi-plus" :size="16" />طلب جديد</BaseButton>
      </template>
    </PageHeader>

    <!-- Quick-start type chips -->
    <div class="mb-4 flex flex-wrap items-center gap-2">
      <span class="text-xs text-muted">أنشئ بسرعة:</span>
      <button
        v-for="t in types"
        :key="t"
        type="button"
        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-sm font-medium transition"
        :style="toggleStyle(false)"
        @click="openNew(t)"
      >
        <BaseIcon :name="PEER_TYPE_META[t].icon" :size="14" />{{ PEER_TYPE_META[t].label }}
      </button>
    </div>

    <div class="mb-4 flex gap-1">
      <button type="button" class="nav-tab flex-none" :class="{ 'is-active': tab === 'incoming' }" @click="tab = 'incoming'">
        الطلبات الواردة
        <BaseChip v-if="store.incoming.length" color="neutral" class="ms-1">{{ store.incoming.length }}</BaseChip>
      </button>
      <button type="button" class="nav-tab flex-none" :class="{ 'is-active': tab === 'outgoing' }" @click="tab = 'outgoing'">
        الطلبات الصادرة
        <BaseChip v-if="store.outgoing.length" color="neutral" class="ms-1">{{ store.outgoing.length }}</BaseChip>
      </button>
    </div>

    <!-- Incoming -->
    <div v-if="tab === 'incoming'">
      <div v-if="store.incoming.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div v-for="r in store.incoming" :key="r.id" class="flex h-full flex-col rounded-ui-lg border-ui p-4">
          <div class="mb-2 flex items-center gap-2">
            <BaseAvatar :color="mapColor(PEER_TYPE_META[r.type].color)" tonal square>
              <BaseIcon :name="PEER_TYPE_META[r.type].icon" :size="20" />
            </BaseAvatar>
            <div class="flex-1">
              <div class="text-sm font-bold text-content">{{ PEER_TYPE_META[r.type].label }} من {{ r.personName }}</div>
              <div class="text-xs text-muted">{{ r.personRole }} · {{ r.date }}</div>
            </div>
            <BaseChip :color="mapColor(PEER_STATUS_META[r.status].color)">{{ PEER_STATUS_META[r.status].label }}</BaseChip>
          </div>
          <p class="mb-2 text-sm text-muted">{{ r.reason }}</p>
          <div v-if="r.skills.length" class="mb-3 flex flex-wrap gap-1">
            <BaseChip v-for="s in r.skills" :key="s" color="brand">{{ s }}</BaseChip>
          </div>
          <div class="mt-auto flex items-center gap-2">
            <template v-if="r.status === 'pending'">
              <BaseButton size="sm" variant="tonal-emerald" @click="store.accept(r.id)"><BaseIcon name="mdi-check" :size="16" />قبول</BaseButton>
              <BaseButton size="sm" variant="ghost" @click="store.reject(r.id)"><BaseIcon name="mdi-close" :size="16" :style="{ color: 'rgb(var(--v-theme-error))' }" /><span :style="{ color: 'rgb(var(--v-theme-error))' }">رفض</span></BaseButton>
            </template>
            <template v-else-if="r.status === 'accepted'">
              <BaseButton size="sm" variant="tonal-brand" @click="store.startWork(r.id)"><BaseIcon name="mdi-play" :size="16" />بدء التنفيذ</BaseButton>
            </template>
            <template v-else-if="r.status === 'in_progress'">
              <BaseButton size="sm" variant="tonal-emerald" @click="store.complete(r.id)"><BaseIcon name="mdi-check-all" :size="16" />إكمال</BaseButton>
            </template>
            <span v-else class="flex items-center gap-1 self-center text-xs text-muted">
              <BaseIcon name="mdi-check-decagram" :size="14" :style="{ color: 'rgb(var(--v-theme-success))' }" /> تمت المعالجة
            </span>
          </div>
        </div>
      </div>
      <BaseCard v-else :padded="false">
        <EmptyState icon="mdi-inbox-outline" title="لا طلبات واردة" description="ستظهر هنا الطلبات التي يرسلها إليك الآخرون." />
      </BaseCard>
    </div>

    <!-- Outgoing -->
    <div v-else>
      <div v-if="store.outgoing.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div v-for="r in store.outgoing" :key="r.id" class="flex h-full flex-col rounded-ui-lg border-ui p-4">
          <div class="mb-2 flex items-center gap-2">
            <BaseAvatar :color="mapColor(PEER_TYPE_META[r.type].color)" tonal square>
              <BaseIcon :name="PEER_TYPE_META[r.type].icon" :size="20" />
            </BaseAvatar>
            <div class="flex-1">
              <div class="text-sm font-bold text-content">{{ PEER_TYPE_META[r.type].label }} إلى {{ r.personName }}</div>
              <div class="text-xs text-muted">{{ r.personRole }} · {{ r.date }}</div>
            </div>
            <BaseChip :color="mapColor(PEER_STATUS_META[r.status].color)">{{ PEER_STATUS_META[r.status].label }}</BaseChip>
          </div>
          <p class="mb-2 text-sm text-muted">{{ r.reason }}</p>
          <div v-if="r.skills.length" class="mb-3 flex flex-wrap gap-1">
            <BaseChip v-for="s in r.skills" :key="s" color="brand">{{ s }}</BaseChip>
          </div>
          <BaseButton v-if="r.status === 'pending'" size="sm" variant="ghost" class="mt-auto" @click="store.cancelOutgoing(r.id)"><BaseIcon name="mdi-cancel" :size="16" :style="{ color: 'rgb(var(--v-theme-error))' }" /><span :style="{ color: 'rgb(var(--v-theme-error))' }">إلغاء الطلب</span></BaseButton>
          <span v-else class="mt-auto flex items-center gap-1 text-xs text-muted"><BaseIcon :name="PEER_STATUS_META[r.status].color === 'success' ? 'mdi-check-decagram' : 'mdi-clock-outline'" :size="14" /> {{ PEER_STATUS_META[r.status].label }}</span>
        </div>
      </div>
      <BaseCard v-else :padded="false">
        <EmptyState icon="mdi-send-outline" title="لا طلبات صادرة" description="أرسل أول طلب عبر زر «طلب جديد»." />
      </BaseCard>
    </div>

    <!-- New request dialog -->
    <BaseModal v-model="dialog" title="طلب جديد" :max-width="560">
      <div class="mb-2 text-xs font-bold text-content">نوع الطلب</div>
      <div class="mb-4 flex flex-wrap gap-2">
        <button
          v-for="t in types"
          :key="t"
          type="button"
          class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-sm font-medium transition"
          :style="toggleStyle(form.type === t, normColor(PEER_TYPE_META[t].color))"
          @click="form.type = t"
        >
          <BaseIcon :name="PEER_TYPE_META[t].icon" :size="14" />{{ PEER_TYPE_META[t].label }}
        </button>
      </div>
      <div class="mb-3 flex items-center gap-1 text-xs text-muted">
        <BaseIcon :name="PEER_TYPE_META[form.type].icon" :size="14" /> {{ PEER_TYPE_META[form.type].desc }}
      </div>

      <label class="mb-1 block text-sm font-medium text-muted">إلى</label>
      <BaseSelect :model-value="form.person" :items="PERSON_OPTIONS" prefix-icon="mdi-account-outline" class="mb-3" placeholder="اختر شخصًا" @update:model-value="v => form.person = v ?? ''" />
      <BaseTextarea v-model="form.reason" label="سبب الطلب" :rows="2" class="mb-3" placeholder="مثال: أرغب في توصية تؤكد مهاراتي في إدارة المشاريع" />
      <label class="mb-1 block text-sm font-medium text-muted">المهارات المراد تأكيدها (اختياري)</label>
      <BaseMultiSelect v-model="form.skills" :options="ALL_SKILLS" placeholder="ابحث عن مهارة…" class="mb-2" />
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <BaseButton size="sm" variant="tonal-brand" @click="addMockAttachment"><BaseIcon name="mdi-paperclip" :size="16" />إرفاق مستند</BaseButton>
        <BaseChip v-for="a in form.attachments" :key="a" color="neutral"><BaseIcon name="mdi-file-outline" :size="12" />{{ a }}</BaseChip>
      </div>

      <div class="flex items-start gap-2 rounded-ui border-s-4 p-2" style="background: rgba(var(--v-theme-secondary), 0.12); border-color: rgb(var(--v-theme-secondary))">
        <BaseIcon name="mdi-robot-happy-outline" :size="20" :style="{ color: 'rgb(var(--v-theme-secondary))' }" />
        <span class="text-xs text-content">{{ aiTip }}</span>
      </div>
      <template #actions>
        <BaseButton variant="ghost" @click="dialog = false">إلغاء</BaseButton>
        <BaseButton variant="accent" :disabled="!canSend" @click="send"><BaseIcon name="mdi-send" :size="16" />إرسال الطلب</BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar :model-value="!!snackbar" color="success" :timeout="3500" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </BaseSnackbar>
  </div>
</template>
