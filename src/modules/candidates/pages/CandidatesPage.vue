<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import { mockCandidates } from '../services/mockCandidates'
import { CANDIDATE_STATUS_META } from '../interfaces/Candidate'
import type { CandidateStatus } from '../interfaces/Candidate'

const router = useRouter()
const search = ref('')
const statusFilter = ref<CandidateStatus | null>(null)

const statusOptions = (Object.keys(CANDIDATE_STATUS_META) as CandidateStatus[]).map(value => ({
  value,
  title: CANDIDATE_STATUS_META[value].label,
}))

const filtered = computed(() => mockCandidates.filter((c) => {
  const matchesSearch = !search.value || c.name.includes(search.value) || c.title.includes(search.value)
  const matchesStatus = !statusFilter.value || c.status === statusFilter.value
  return matchesSearch && matchesStatus
}))

function matchColor(rate: number) {
  if (rate >= 85)
    return 'success'
  if (rate >= 70)
    return 'secondary'
  return 'warning'
}

function openProfile(id: number) {
  router.push({ name: 'candidate-profile', params: { id } })
}
</script>

<template>
  <div>
    <PageHeader
      title="الترشيحات"
      subtitle="المرشحون المتقدمون لفرصك مع نسب التطابق الذكي"
      icon="mdi-account-group-outline"
    />

    <VCard class="pa-4 mb-5">
      <VRow dense align="center">
        <VCol cols="12" md="7">
          <VTextField
            v-model="search"
            placeholder="ابحث باسم المرشح أو المسمى..."
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
          />
        </VCol>
        <VCol cols="12" md="5">
          <VSelect
            v-model="statusFilter"
            :items="statusOptions"
            placeholder="حالة الترشيح"
            prepend-inner-icon="mdi-filter-variant"
            hide-details
            clearable
          />
        </VCol>
      </VRow>
    </VCard>

    <VRow>
      <VCol v-for="c in filtered" :key="c.id" cols="12" md="6">
        <VCard class="pa-4" height="100%">
          <div class="d-flex align-start justify-space-between">
            <div class="d-flex align-center ga-3">
              <VAvatar color="secondary" size="52">
                <span class="text-h6 text-white font-weight-bold">{{ c.name.charAt(0) }}</span>
              </VAvatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold">{{ c.name }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ c.title }} · {{ c.location }}</div>
              </div>
            </div>
            <VChip :color="CANDIDATE_STATUS_META[c.status].color" size="small" label>
              {{ CANDIDATE_STATUS_META[c.status].label }}
            </VChip>
          </div>

          <div class="d-flex flex-wrap ga-1 my-3">
            <VChip v-for="s in c.skills.slice(0, 4)" :key="s" size="x-small" variant="tonal" color="primary">
              {{ s }}
            </VChip>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-space-between text-caption mb-1">
              <span class="text-medium-emphasis">نسبة التطابق</span>
              <span class="font-weight-bold" :class="`text-${matchColor(c.matchRate)}`">{{ c.matchRate }}%</span>
            </div>
            <VProgressLinear :model-value="c.matchRate" :color="matchColor(c.matchRate)" height="6" rounded />
          </div>

          <VDivider class="mb-3" />
          <div class="d-flex align-center justify-space-between">
            <span class="text-caption text-medium-emphasis">تقدّم {{ c.appliedAt }}</span>
            <VBtn color="primary" size="small" variant="tonal" @click="openProfile(c.id)">
              عرض الملف
            </VBtn>
          </div>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
