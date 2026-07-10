<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHeader from '@/components/shared/PageHeader.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'
import BaseTooltip from '@/components/ui/BaseTooltip.vue'
import ResourceScaffold from '@/modules/admin/components/ResourceScaffold.vue'
import type { TableColumn } from '@/components/ui/BaseTable.vue'
import { useAdminResource } from '@/modules/admin/composables/useAdminResource'
import { type AdminPlan, api } from '@/services/api'

const { t } = useI18n()
const r = useAdminResource<AdminPlan>({ fetcher: params => api.admin.plans(params), initialSort: 'sort' })
const { items, meta, loading, sortKey, search } = r

const columns: TableColumn[] = [
  { key: 'name', label: t('admin.plans.colName'), sortable: true },
  { key: 'key', label: t('admin.plans.colKey'), sortable: true },
  { key: 'price', label: t('admin.plans.colPrice'), sortable: true, align: 'center' },
  { key: 'survey_limit', label: t('admin.plans.colSurveyLimit'), align: 'center' },
  { key: 'features', label: t('admin.plans.colFeatures'), align: 'center' },
  { key: 'subscribers', label: t('admin.plans.colSubscribers'), align: 'center' },
  { key: 'active', label: t('admin.plans.colActive'), align: 'center' },
]

const snack = ref({ show: false, text: '', color: 'success' })
function toast(text: string, color = 'success') { snack.value = { show: true, text, color } }
function fail(e: unknown) { toast((e as { message?: string })?.message ?? t('admin.toast.failed'), 'error') }

// ——— تحرير الباقة ———
const editOpen = ref(false)
const target = ref<AdminPlan | null>(null)
const form = ref({ name: '', price: 0 as number, surveyLimit: null as number | null, unlimited: false, featuresText: '', active: true })
const saving = ref(false)

function openEdit(p: AdminPlan) {
  target.value = p
  form.value = {
    name: p.name,
    price: p.price,
    surveyLimit: p.survey_limit,
    unlimited: p.survey_limit === null,
    featuresText: p.features.join('\n'),
    active: p.active,
  }
  editOpen.value = true
}
async function save() {
  if (!target.value)
    return
  saving.value = true
  try {
    await api.admin.updatePlan(target.value.id, {
      name: form.value.name.trim(),
      price: Number(form.value.price) || 0,
      survey_limit: form.value.unlimited ? null : (Number(form.value.surveyLimit) || 0),
      features: form.value.featuresText.split('\n').map(s => s.trim()).filter(Boolean),
      active: form.value.active,
    })
    toast(t('admin.toast.updated'))
    editOpen.value = false
    r.refresh()
  }
  catch (e) { fail(e) }
  finally { saving.value = false }
}
</script>

<template>
  <div>
    <PageHeader :title="t('admin.plans.title')" :subtitle="t('admin.plans.subtitle')" icon="mdi-tag-multiple-outline" />

    <ResourceScaffold
      :columns="columns"
      :items="items"
      :loading="loading"
      :meta="meta"
      :sort-key="sortKey"
      :search="search"
      :search-placeholder="t('admin.plans.searchPlaceholder')"
      export-name="plans"
      @update:sort-key="r.setSort"
      @update:search="r.setSearch"
      @update:page="r.setPage"
      @update:per-page="r.setPerPage"
    >
      <template #cell-price="{ row }">
        <span class="font-bold text-content">{{ row.price.toLocaleString() }} <span class="text-xs text-muted">ر.س</span></span>
      </template>
      <template #cell-survey_limit="{ row }">
        <span class="text-content">{{ row.survey_limit === null ? t('admin.plans.unlimited') : row.survey_limit }}</span>
      </template>
      <template #cell-features="{ row }">
        <span class="text-muted">{{ row.features.length }}</span>
      </template>
      <template #cell-subscribers="{ row }">
        <span class="text-content">{{ row.subscribers.toLocaleString() }}</span>
      </template>
      <template #cell-active="{ row }">
        <BaseChip :color="row.active ? 'success' : 'neutral'">{{ row.active ? t('admin.plans.statusActive') : t('admin.plans.statusInactive') }}</BaseChip>
      </template>

      <template #actions="{ row }">
        <BaseTooltip :text="t('admin.plans.edit')">
          <button class="row-act text-brand" :aria-label="t('admin.plans.edit')" @click="openEdit(row)">
            <BaseIcon name="mdi-pencil-outline" :size="18" />
          </button>
        </BaseTooltip>
      </template>
    </ResourceScaffold>

    <!-- تحرير الباقة -->
    <BaseModal v-model="editOpen" :title="target ? t('admin.plans.editTitle', { name: target.name }) : ''" :max-width="520">
      <div v-if="target" class="space-y-3">
        <BaseInput v-model="form.name" :label="t('admin.plans.fieldName')" />
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model.number="form.price" :label="t('admin.plans.fieldPrice')" type="number" />
          <BaseInput v-model.number="form.surveyLimit" :label="t('admin.plans.fieldSurveyLimit')" type="number" :disabled="form.unlimited" />
        </div>
        <label class="flex items-center gap-2 text-sm text-content">
          <input v-model="form.unlimited" type="checkbox" class="accent-current">
          {{ t('admin.plans.unlimitedSurveys') }}
        </label>

        <div>
          <label class="mb-1 block text-sm text-muted">{{ t('admin.plans.fieldFeatures') }}</label>
          <textarea
            v-model="form.featuresText"
            rows="5"
            class="w-full rounded-ui border-ui bg-surface px-3 py-2 text-sm text-content"
            :placeholder="t('admin.plans.featuresHint')"
          />
        </div>

        <label class="flex items-center gap-2 text-sm text-content">
          <input v-model="form.active" type="checkbox" class="accent-current">
          {{ t('admin.plans.fieldActive') }}
        </label>
      </div>

      <template #actions>
        <BaseButton variant="ghost" @click="editOpen = false">{{ t('admin.users.cancel') }}</BaseButton>
        <BaseButton variant="brand" :disabled="saving || !form.name.trim()" @click="save">
          <BaseIcon name="mdi-check" :size="18" />{{ t('admin.plans.save') }}
        </BaseButton>
      </template>
    </BaseModal>

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
.row-act:hover {
  background: rgba(var(--v-theme-on-surface), 0.08);
}
</style>
