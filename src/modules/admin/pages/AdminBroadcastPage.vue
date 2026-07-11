<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import ResourceScaffold from '@/modules/admin/components/ResourceScaffold.vue'
import type { FilterDef } from '@/modules/admin/components/ResourceScaffold.vue'
import type { TableColumn } from '@/components/ui/BaseTable.vue'
import { useAdminResource } from '@/modules/admin/composables/useAdminResource'
import { type AdminBroadcast, type AdminBroadcastStats, api } from '@/services/api'
import { useAuthStore } from '@/stores/AuthStore'

const { t } = useI18n()
const auth = useAuthStore()
const canSend = computed(() => auth.hasPermission('create_broadcast'))
const r = useAdminResource<AdminBroadcast>({ fetcher: params => api.admin.broadcasts(params), initialSort: '-id' })
const { items, meta, loading, sortKey, search, filters } = r

const stats = ref<AdminBroadcastStats | null>(null)
async function loadStats() { try { stats.value = await api.admin.broadcastsStats() } catch { /* تجاهل */ } }
onMounted(loadStats)
function refreshAll() { r.refresh(); loadStats() }

const CHANNELS = ['notification', 'banner', 'email']
const AUDIENCES = ['all', 'role', 'tier']
const ROLES = ['seeker', 'company', 'interviewer', 'coach', 'trainer', 'consultant', 'endorser']
const TIERS = ['free', 'pro', 'elite']
const channelLabel = (x: string) => t(`admin.broadcast.ch_${x}`)
const channelColor: Record<string, 'brand' | 'info' | 'accent'> = { notification: 'brand', banner: 'info', email: 'accent' }
const audienceLabel = (a: string, v?: string | null) => a === 'all' ? t('admin.broadcast.aud_all') : `${t(`admin.broadcast.aud_${a}`)}: ${v ?? '—'}`

const columns: TableColumn[] = [
  { key: 'title', label: t('admin.broadcast.colTitle'), sortable: true },
  { key: 'channel', label: t('admin.broadcast.colChannel'), align: 'center' },
  { key: 'audience', label: t('admin.broadcast.colAudience') },
  { key: 'recipients', label: t('admin.broadcast.colRecipients'), sortable: true, align: 'center' },
  { key: 'sender', label: t('admin.broadcast.colSender') },
  { key: 'created_at', label: t('admin.broadcast.colDate'), sortable: true },
]
const filterDefs: FilterDef[] = [
  { key: 'channel', label: t('admin.broadcast.colChannel'), options: CHANNELS.map(c => ({ value: c, label: channelLabel(c) })) },
]

const snack = ref({ show: false, text: '', color: 'success' })
function toast(text: string, color = 'success') { snack.value = { show: true, text, color } }
function fail(e: unknown) { toast((e as { message?: string })?.message ?? t('admin.toast.failed'), 'error') }
function fmtDate(iso?: string) { return iso ? new Date(iso).toLocaleDateString() : '—' }

// ——— المُلحّن ———
const composeOpen = ref(false)
const sending = ref(false)
const estimate = ref<number | null>(null)
const form = ref({ title: '', body: '', channel: 'notification', audience: 'all', audience_value: '' })
function openCompose() { form.value = { title: '', body: '', channel: 'notification', audience: 'all', audience_value: '' }; estimate.value = null; composeOpen.value = true }
const audienceOptions = computed(() => form.value.audience === 'role' ? ROLES : form.value.audience === 'tier' ? TIERS : [])

async function estimateAudience() {
  try {
    const res = await api.admin.broadcastAudience(form.value.audience, form.value.audience === 'all' ? undefined : form.value.audience_value)
    estimate.value = res.count
  }
  catch { estimate.value = null }
}
watch(() => [form.value.audience, form.value.audience_value], () => {
  if (composeOpen.value && (form.value.audience === 'all' || form.value.audience_value))
    estimateAudience()
  else estimate.value = null
})

const canSubmit = computed(() => form.value.title.trim() && form.value.body.trim() && (form.value.audience === 'all' || form.value.audience_value))
async function send() {
  if (!canSubmit.value)
    return
  sending.value = true
  try {
    const payload = { ...form.value, audience_value: form.value.audience === 'all' ? undefined : form.value.audience_value }
    const res = await api.admin.sendBroadcast(payload)
    toast(t('admin.broadcast.sent', { n: res.recipients }))
    composeOpen.value = false
    refreshAll()
  }
  catch (e) { fail(e) }
  finally { sending.value = false }
}
</script>

<template>
  <div>
    <PageHeader :title="t('admin.broadcast.title')" :subtitle="t('admin.broadcast.subtitle')" icon="mdi-bullhorn-outline">
      <template #actions>
        <BaseButton v-if="canSend" variant="brand" size="sm" @click="openCompose">
          <BaseIcon name="mdi-send-outline" :size="18" />{{ t('admin.broadcast.newBroadcast') }}
        </BaseButton>
      </template>
    </PageHeader>

    <!-- شريط الإحصاءات -->
    <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
      <div class="grid grid-cols-3 gap-3 lg:col-span-2">
        <StatCard icon="mdi-bullhorn-outline" :value="stats?.total ?? 0" :title="t('admin.broadcast.statTotal')" color="primary" />
        <StatCard icon="mdi-account-group-outline" :value="(stats?.reach ?? 0).toLocaleString()" :title="t('admin.broadcast.statReach')" color="success" />
        <StatCard icon="mdi-earth" :value="stats?.audienceSize ?? 0" :title="t('admin.broadcast.statAudience')" color="info" />
      </div>
      <BaseCard>
        <div class="mb-2 flex items-center gap-2">
          <BaseIcon name="mdi-chart-donut" :size="18" class="text-brand" />
          <h2 class="text-sm font-bold text-content">{{ t('admin.broadcast.byChannel') }}</h2>
        </div>
        <DonutChart v-if="stats?.byChannel?.length" :data="stats.byChannel.map(x => ({ label: channelLabel(x.label), value: x.value }))" :size="150" :center-label="t('admin.broadcast.statTotal')" />
        <p v-else class="py-6 text-center text-xs text-muted">{{ t('admin.broadcast.empty') }}</p>
      </BaseCard>
    </div>

    <ResourceScaffold
      :columns="columns"
      :items="items"
      :loading="loading"
      :meta="meta"
      :sort-key="sortKey"
      :search="search"
      :filters="filterDefs"
      :active-filters="filters"
      :search-placeholder="t('admin.broadcast.searchPlaceholder')"
      export-name="broadcasts"
      @update:sort-key="r.setSort"
      @update:search="r.setSearch"
      @filter="r.setFilter"
      @update:page="r.setPage"
      @update:per-page="r.setPerPage"
    >
      <template #cell-channel="{ row }">
        <BaseChip :color="channelColor[row.channel] || 'neutral'">{{ channelLabel(row.channel) }}</BaseChip>
      </template>
      <template #cell-audience="{ row }">
        <span class="text-content">{{ audienceLabel(row.audience, row.audience_value) }}</span>
      </template>
      <template #cell-recipients="{ row }">
        <span class="font-bold text-content">{{ row.recipients.toLocaleString() }}</span>
      </template>
      <template #cell-created_at="{ row }">
        <span class="text-muted">{{ fmtDate(row.createdAt) }}</span>
      </template>
    </ResourceScaffold>

    <!-- مُلحّن البثّ -->
    <BaseModal v-model="composeOpen" :title="t('admin.broadcast.newBroadcast')" :max-width="560">
      <div class="space-y-3">
        <BaseInput v-model="form.title" :label="t('admin.broadcast.fieldTitle')" />
        <div>
          <label class="mb-1 block text-sm text-muted">{{ t('admin.broadcast.fieldBody') }}</label>
          <textarea v-model="form.body" rows="4" class="w-full rounded-ui border-ui bg-surface px-3 py-2 text-sm text-content" />
        </div>
        <div class="grid grid-cols-3 gap-3">
          <BaseSelect v-model="form.channel" :label="t('admin.broadcast.fieldChannel')" :items="CHANNELS.map(c => ({ value: c, title: channelLabel(c) }))" />
          <BaseSelect v-model="form.audience" :label="t('admin.broadcast.fieldAudience')" :items="AUDIENCES.map(a => ({ value: a, title: t(`admin.broadcast.aud_${a}`) }))" @update:model-value="form.audience_value = ''" />
          <BaseSelect
            v-if="form.audience !== 'all'"
            v-model="form.audience_value"
            :label="t('admin.broadcast.fieldTarget')"
            :items="audienceOptions.map(o => ({ value: o, title: o }))"
          />
        </div>
        <div class="flex items-center gap-2 rounded-ui bg-brand/[0.08] px-3 py-2 text-sm text-content">
          <BaseIcon name="mdi-account-group-outline" :size="18" class="text-brand" />
          {{ t('admin.broadcast.estimate') }}:
          <span class="font-bold">{{ estimate === null ? '—' : t('admin.broadcast.recipients', { n: estimate }) }}</span>
        </div>
      </div>
      <template #actions>
        <BaseButton variant="ghost" @click="composeOpen = false">{{ t('admin.users.cancel') }}</BaseButton>
        <BaseButton variant="brand" :disabled="sending || !canSubmit" @click="send">
          <BaseIcon name="mdi-send" :size="18" />{{ t('admin.broadcast.send') }}
        </BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar v-model="snack.show" :color="snack.color">{{ snack.text }}</BaseSnackbar>
  </div>
</template>
