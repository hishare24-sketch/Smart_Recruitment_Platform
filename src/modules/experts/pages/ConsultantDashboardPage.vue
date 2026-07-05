<script setup lang="ts">
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import ExpertBrandPanel from '../components/ExpertBrandPanel.vue'
import { useExpertRolesStore } from '@/stores/ExpertRolesStore'
import type { ConsultingRequest } from '@/stores/ExpertRolesStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'

// لوحة المستشار المهني — خدمات B2B للشركات
const store = useExpertRolesStore()
const snackbar = ref('')

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
const STATUS: Record<ConsultingRequest['status'], { label: string, color: BaseColor }> = {
  new: { label: 'جديد', color: 'accent' },
  accepted: { label: 'مقبول', color: 'info' },
  in_progress: { label: 'قيد التنفيذ', color: 'brand' },
  done: { label: 'منجز', color: 'success' },
  declined: { label: 'معتذَر عنه', color: 'error' },
}

function accept(r: ConsultingRequest) {
  store.respondConsulting(r.id, true)
  snackbar.value = `قبلت استشارة ${r.company}`
}
function decline(r: ConsultingRequest) {
  store.respondConsulting(r.id, false)
  snackbar.value = `اعتذرت عن استشارة ${r.company}`
}

const completeDialog = ref(false)
const completing = ref<ConsultingRequest | null>(null)
const fee = ref(5000)
function openComplete(r: ConsultingRequest) {
  completing.value = r
  fee.value = 5000
  completeDialog.value = true
}
function doComplete() {
  if (completing.value) {
    store.completeConsulting(completing.value.id, fee.value)
    snackbar.value = 'أُنجزت الاستشارة — الأتعاب أُودعت محفظتك (معلقة حتى التسوية)'
  }
  completeDialog.value = false
}
</script>

<template>
  <div>
    <PageHeader title="لوحة المستشار المهني" subtitle="استشارات استراتيجية للشركات حول السوق والفرق" icon="mdi-lightbulb-on-outline" />

    <div class="mb-4 grid grid-cols-3 gap-4">
      <StatCard title="طلبات جديدة" :value="store.consultantStats.newRequests" icon="mdi-inbox-arrow-down-outline" color="accent" />
      <StatCard title="استشارات نشطة" :value="store.consultantStats.active" icon="mdi-progress-clock" color="primary" />
      <StatCard title="منجزة" :value="store.consultantStats.done" icon="mdi-check-decagram-outline" color="success" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- طلبات الشركات -->
      <div class="lg:col-span-2">
        <BaseCard>
          <h2 class="mb-1 font-bold">طلبات الشركات</h2>
          <p class="mb-3 text-xs text-muted">قناة B2B: الشركات تطلب استشاراتك مباشرة عبر المنصة (بالساعة أو بالمشروع).</p>
          <div v-for="r in store.state.consulting" :key="r.id" class="mb-2 rounded-ui border-ui p-4">
            <div class="flex flex-wrap items-center gap-3">
              <BaseAvatar color="info" tonal square><BaseIcon name="mdi-office-building-outline" :size="20" /></BaseAvatar>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-bold">{{ r.topic }}</div>
                <div class="text-xs text-muted">{{ r.company }} · {{ r.scope }} · {{ r.budget }} · {{ r.date }}</div>
              </div>
              <BaseChip :color="STATUS[r.status].color">{{ STATUS[r.status].label }}</BaseChip>
              <div v-if="r.status === 'new'" class="flex gap-1">
                <BaseButton variant="emerald" size="sm" @click="accept(r)"><BaseIcon name="mdi-check" :size="16" /> قبول</BaseButton>
                <button class="flex h-8 w-8 items-center justify-center rounded-full border-ui transition hover:bg-surfalt" style="color: rgb(var(--v-theme-error))" aria-label="اعتذار" @click="decline(r)">
                  <BaseIcon name="mdi-close" :size="18" />
                </button>
              </div>
              <BaseButton v-else-if="r.status === 'accepted' || r.status === 'in_progress'" variant="tonal-brand" size="sm" @click="openComplete(r)">
                <BaseIcon name="mdi-flag-checkered" :size="16" /> إنجاز وتحصيل
              </BaseButton>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- الملف والنمو -->
      <div>
        <ExpertBrandPanel />
      </div>
    </div>

    <BaseModal v-model="completeDialog" title="إنجاز الاستشارة" :max-width="400">
      <p class="mb-3 text-sm text-muted">{{ completing?.company }} — {{ completing?.topic }}</p>
      <BaseInput v-model.number="fee" type="number" label="الأتعاب (ر.س)" prefix-icon="mdi-cash-multiple" />
      <template #actions>
        <BaseButton variant="ghost" @click="completeDialog = false">إلغاء</BaseButton>
        <BaseButton variant="brand" :disabled="fee <= 0" @click="doComplete">تأكيد الإنجاز</BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar :model-value="!!snackbar" color="success" :timeout="3000" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </BaseSnackbar>
  </div>
</template>
