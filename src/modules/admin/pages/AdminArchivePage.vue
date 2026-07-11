<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'
import BaseTooltip from '@/components/ui/BaseTooltip.vue'
import { confirm } from '@/components/ui/confirm'
import { type ArchiveItem, type ArchiveStats, type PageMeta, api } from '@/services/api'
import { useAuthStore } from '@/stores/AuthStore'

const { t } = useI18n()
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('manage_archive'))

const snack = ref({ show: false, text: '', color: 'success' })
function toast(text: string, color = 'success') { snack.value = { show: true, text, color } }
function fail(e: unknown) { toast((e as { message?: string })?.message ?? t('admin.toast.failed'), 'error') }

const TYPES = ['opportunities', 'requests', 'surveys']
const typeLabel = (k: string) => t(`admin.archive.type_${k}`)
type ChipColor = 'brand' | 'info' | 'accent' | 'warning' | 'neutral'
const TYPE_COLOR: Record<string, ChipColor> = { opportunities: 'info', requests: 'accent', surveys: 'warning' }

const stats = ref<ArchiveStats | null>(null)
const items = ref<ArchiveItem[]>([])
const meta = ref<PageMeta | null>(null)
const page = ref(1)
const typeFilter = ref<string>('')
const loading = ref(false)

const typeItems = computed(() => [{ value: '', title: t('admin.archive.allTypes') }, ...TYPES.map(k => ({ value: k, title: typeLabel(k) }))])

async function loadStats() { try { stats.value = await api.admin.archiveStats() } catch { /* تجاهل */ } }
async function loadItems() {
  loading.value = true
  try {
    const r = await api.admin.archive({ type: typeFilter.value || undefined, page: page.value })
    items.value = r.items
    meta.value = r.meta
  }
  catch (e) { fail(e) }
  finally { loading.value = false }
}
function refresh() { loadStats(); loadItems() }
function onType(v: string | null) { typeFilter.value = v || ''; page.value = 1; loadItems() }
function goPage(p: number) { page.value = p; loadItems() }

async function restore(item: ArchiveItem) {
  try {
    await api.admin.restoreArchive(item.type, item.id)
    toast(t('admin.archive.restored', { title: item.title }))
    refresh()
  }
  catch (e) { fail(e) }
}
async function purge(item: ArchiveItem) {
  const ok = await confirm({
    title: t('admin.archive.purgeTitle'),
    message: t('admin.archive.purgeMsg', { title: item.title }),
    confirmText: t('admin.archive.purge'),
    tone: 'danger',
    icon: 'mdi-delete-forever-outline',
  })
  if (!ok)
    return
  try {
    await api.admin.purgeArchive(item.type, item.id)
    toast(t('admin.archive.purged'))
    refresh()
  }
  catch (e) { fail(e) }
}
function fmtDate(s: string | null) { return s ? new Date(s).toLocaleString() : '—' }

onMounted(refresh)
</script>

<template>
  <div>
    <PageHeader :title="t('admin.archive.title')" :subtitle="t('admin.archive.subtitle')" icon="mdi-archive-outline">
      <template #actions>
        <div class="w-52"><BaseSelect :model-value="typeFilter" :items="typeItems" @update:model-value="v => onType(v as string)" /></div>
      </template>
    </PageHeader>

    <!-- إحصاءات -->
    <div v-if="stats" class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
      <StatCard icon="mdi-archive-outline" :value="stats.total" :title="t('admin.archive.total')" color="primary" />
      <StatCard v-for="bt in stats.byType" :key="bt.label" :icon="bt.label === 'opportunities' ? 'mdi-briefcase-outline' : (bt.label === 'requests' ? 'mdi-file-document-outline' : 'mdi-clipboard-text-outline')" :value="bt.value" :title="typeLabel(bt.label)" :color="TYPE_COLOR[bt.label] || 'neutral'" />
    </div>

    <BaseCard>
      <div class="mb-2 flex items-center gap-2">
        <BaseIcon name="mdi-history" :size="18" class="text-brand" />
        <h2 class="text-sm font-bold text-content">{{ t('admin.archive.items') }}</h2>
        <span class="text-xs text-muted">{{ t('admin.archive.recoverNote') }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-ui text-muted">
              <th class="p-2 text-start font-medium">{{ t('admin.archive.colType') }}</th>
              <th class="p-2 text-start font-medium">{{ t('admin.archive.colTitle') }}</th>
              <th class="p-2 text-start font-medium">{{ t('admin.archive.colDeletedAt') }}</th>
              <th class="p-2 text-end font-medium">{{ t('admin.archive.colActions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="`${item.type}-${item.id}`" class="border-b border-ui">
              <td class="p-2"><BaseChip :color="TYPE_COLOR[item.type] || 'neutral'">{{ typeLabel(item.type) }}</BaseChip></td>
              <td class="p-2 font-medium text-content">{{ item.title }}</td>
              <td class="whitespace-nowrap p-2 text-xs text-muted">{{ fmtDate(item.deletedAt) }}</td>
              <td class="p-2">
                <div class="flex items-center justify-end gap-1">
                  <BaseTooltip :text="t('admin.archive.restore')">
                    <button class="row-act text-success" :disabled="!canManage" :aria-label="t('admin.archive.restore')" @click="restore(item)"><BaseIcon name="mdi-restore" :size="18" /></button>
                  </BaseTooltip>
                  <BaseTooltip :text="t('admin.archive.purge')">
                    <button class="row-act" style="color: rgb(var(--v-theme-error))" :disabled="!canManage" :aria-label="t('admin.archive.purge')" @click="purge(item)"><BaseIcon name="mdi-delete-forever-outline" :size="18" /></button>
                  </BaseTooltip>
                </div>
              </td>
            </tr>
            <tr v-if="!items.length && !loading"><td colspan="4" class="p-10 text-center text-sm text-muted"><BaseIcon name="mdi-archive-check-outline" :size="32" class="mb-2 opacity-40" /><div>{{ t('admin.archive.empty') }}</div></td></tr>
          </tbody>
        </table>
      </div>
      <BasePagination v-if="meta && meta.last_page > 1" class="mt-3" :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" :per-page="meta.itemPerPage" @update:page="goPage" />
    </BaseCard>

    <BaseSnackbar v-model="snack.show" :color="snack.color">{{ snack.text }}</BaseSnackbar>
  </div>
</template>

<style scoped>
.row-act {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  transition: background-color 0.15s ease;
}
.row-act:hover:not(:disabled) { background: rgba(var(--v-theme-on-surface), 0.08); }
.row-act:disabled { opacity: 0.4; }
</style>
