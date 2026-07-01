<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { Opportunity } from '../interfaces/Opportunity'
import { EMPLOYMENT_TYPE_LABELS, formatSalary } from '../interfaces/Opportunity'
import { useSavedStore } from '@/stores/SavedStore'
import { useApplicationsStore } from '@/stores/ApplicationsStore'

const props = defineProps<{ opportunity: Opportunity }>()
const router = useRouter()
const savedStore = useSavedStore()
const applicationsStore = useApplicationsStore()

const isSaved = computed(() => savedStore.isSaved(props.opportunity.id))
const isApplied = computed(() => applicationsStore.hasApplied(props.opportunity.id))

function matchColor(rate: number) {
  if (rate >= 85)
    return 'success'
  if (rate >= 70)
    return 'secondary'
  return 'warning'
}

function openDetails() {
  router.push({ name: 'opportunity-details', params: { id: props.opportunity.id } })
}
</script>

<template>
  <VCard class="pa-4 d-flex flex-column" height="100%">
    <div class="d-flex align-start justify-space-between mb-2">
      <div class="d-flex align-center ga-3">
        <VAvatar color="primary" variant="tonal" rounded="lg" size="46">
          <span class="text-h6 font-weight-bold">{{ opportunity.companyInitial }}</span>
        </VAvatar>
        <div>
          <div class="text-subtitle-1 font-weight-bold">
            {{ opportunity.title }}
          </div>
          <div class="text-body-2 text-medium-emphasis">
            {{ opportunity.company }}
          </div>
        </div>
      </div>
      <div class="d-flex align-center">
        <VChip v-if="opportunity.isNew" color="accent" size="x-small" label class="me-1">
          جديد
        </VChip>
        <VBtn
          :icon="isSaved ? 'mdi-bookmark' : 'mdi-bookmark-outline'"
          :color="isSaved ? 'accent' : 'medium-emphasis'"
          variant="text"
          density="comfortable"
          size="small"
          @click.stop="savedStore.toggle(opportunity.id)"
        />
      </div>
    </div>

    <div class="d-flex flex-wrap ga-2 my-2">
      <VChip size="small" variant="tonal" prepend-icon="mdi-map-marker-outline">
        {{ opportunity.city }}
      </VChip>
      <VChip size="small" variant="tonal" prepend-icon="mdi-briefcase-outline">
        {{ EMPLOYMENT_TYPE_LABELS[opportunity.type] }}
      </VChip>
    </div>

    <div class="text-body-2 text-medium-emphasis mb-2">
      <VIcon icon="mdi-cash" size="16" /> {{ formatSalary(opportunity.salaryMin, opportunity.salaryMax) }}
    </div>

    <!-- Match rate -->
    <div class="mt-1">
      <div class="d-flex justify-space-between text-caption mb-1">
        <span class="text-medium-emphasis">نسبة التطابق الذكي</span>
        <span class="font-weight-bold" :class="`text-${matchColor(opportunity.matchRate)}`">
          {{ opportunity.matchRate }}%
        </span>
      </div>
      <VProgressLinear
        :model-value="opportunity.matchRate"
        :color="matchColor(opportunity.matchRate)"
        height="6"
        rounded
      />
    </div>

    <div class="d-flex align-center justify-space-between mt-4 pt-2">
      <span class="text-caption text-medium-emphasis">
        <VIcon icon="mdi-account-multiple-outline" size="16" /> {{ opportunity.applicants }} متقدم
      </span>
      <span class="text-caption text-medium-emphasis">{{ opportunity.postedAt }}</span>
    </div>

    <VDivider class="my-3" />

    <div class="d-flex ga-2 mt-auto">
      <VBtn v-if="isApplied" color="success" variant="tonal" size="small" class="flex-grow-1" prepend-icon="mdi-check" @click="openDetails">
        تم التقديم
      </VBtn>
      <VBtn v-else color="accent" size="small" class="flex-grow-1" @click="openDetails">
        تقدّم الآن
      </VBtn>
      <VBtn variant="outlined" color="primary" size="small" @click="openDetails">
        التفاصيل
      </VBtn>
    </div>
  </VCard>
</template>
