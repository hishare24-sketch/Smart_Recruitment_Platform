<script setup lang="ts">
import { useExpertBrandStore } from '@/stores/ExpertBrandStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseChip from '@/components/ui/BaseChip.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'

// لوحة العلامة والنمو للخبير — مشتركة عبر لوحات المرشد/المدرّب/المستشار.
const brand = useExpertBrandStore()

type BaseColor = 'brand' | 'emerald' | 'accent' | 'success' | 'info' | 'warning' | 'error' | 'neutral'
function tierColor(c: string): BaseColor {
  return (({ primary: 'brand', secondary: 'emerald' } as Record<string, BaseColor>)[c] ?? c) as BaseColor
}

const STAT_META = [
  { key: 'views', label: 'مشاهدة', icon: 'mdi-eye-outline' },
  { key: 'favorites', label: 'حفظ', icon: 'mdi-bookmark-outline' },
  { key: 'shares', label: 'مشاركة', icon: 'mdi-share-variant-outline' },
  { key: 'referrals', label: 'إحالة', icon: 'mdi-account-plus-outline' },
] as const

function copyReferral() {
  navigator.clipboard?.writeText(brand.referralLink)
  brand.recordShare()
}
</script>

<template>
  <div class="space-y-4">
    <!-- الدرجة + الملف العام -->
    <BaseCard>
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h3 class="flex items-center gap-2 font-bold">
          <BaseIcon name="mdi-bullhorn-outline" :size="20" style="color: rgb(var(--v-theme-secondary))" /> الملف والنمو
        </h3>
        <div class="flex items-center gap-2">
          <BaseChip v-if="brand.isAmbassador" color="accent"><BaseIcon name="mdi-shield-crown-outline" :size="14" /> سفير المنصة</BaseChip>
          <BaseChip :color="tierColor(brand.tier.color)"><BaseIcon :name="brand.tier.icon" :size="14" /> {{ brand.tier.label }}</BaseChip>
        </div>
      </div>

      <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
        <div v-for="s in STAT_META" :key="s.key" class="rounded-ui bg-surfalt p-3 text-center">
          <BaseIcon :name="s.icon" :size="18" class="text-muted" />
          <div class="text-lg font-bold">{{ brand.marketingStats[s.key] }}</div>
          <div class="text-xs text-muted">{{ s.label }}</div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <BaseButton variant="tonal-brand" size="sm" @click="brand.shareOnLinkedIn()">
          <BaseIcon name="mdi-linkedin" :size="18" /> شارك على LinkedIn
        </BaseButton>
        <BaseButton variant="outline" size="sm" @click="copyReferral">
          <BaseIcon name="mdi-link-variant" :size="18" /> انسخ رابط الدعوة
        </BaseButton>
      </div>
    </BaseCard>

    <!-- الإنجازات -->
    <BaseCard>
      <h3 class="mb-3 flex items-center gap-2 font-bold"><BaseIcon name="mdi-medal-outline" :size="20" style="color: rgb(var(--v-theme-warning))" /> إنجازاتك</h3>
      <div class="flex flex-wrap gap-2">
        <BaseChip
          v-for="a in brand.achievements"
          :key="a.id"
          :color="a.earned ? 'warning' : 'neutral'"
        >
          <span :class="{ 'opacity-45': !a.earned }"><BaseIcon :name="a.earned ? a.icon : 'mdi-lock-outline'" :size="14" /> {{ a.label }}</span>
        </BaseChip>
      </div>
    </BaseCard>

    <!-- العروض النشطة -->
    <BaseCard v-if="brand.activePromos.length">
      <h3 class="mb-2 flex items-center gap-2 font-bold"><BaseIcon name="mdi-tag-outline" :size="20" style="color: rgb(var(--v-theme-accent))" /> عروضك النشطة</h3>
      <div
        v-for="p in brand.activePromos"
        :key="p.id"
        class="mb-2 flex items-center justify-between gap-2 rounded-ui border-s-4 p-3"
        style="border-color: rgb(var(--v-theme-accent)); background: rgba(var(--v-theme-accent), 0.1)"
      >
        <span class="text-sm">{{ p.title }}</span>
        <BaseChip color="success">نشط</BaseChip>
      </div>
    </BaseCard>

    <!-- توصيات الزملاء -->
    <BaseCard v-if="brand.receivedPeerEndorsements.length">
      <h3 class="mb-3 flex items-center gap-2 font-bold"><BaseIcon name="mdi-account-heart-outline" :size="20" style="color: rgb(var(--v-theme-secondary))" /> توصيات الزملاء</h3>
      <div class="space-y-3">
        <div v-for="e in brand.receivedPeerEndorsements" :key="e.id" class="flex items-start gap-2">
          <BaseAvatar color="emerald" :size="34" tonal>{{ e.peerInitial }}</BaseAvatar>
          <div class="flex-1">
            <div class="flex items-center gap-1 text-sm font-bold">
              {{ e.peerName }}
              <BaseChip v-if="e.reciprocated" color="brand">متبادلة</BaseChip>
            </div>
            <div class="text-xs text-muted">{{ e.peerTitle }}</div>
            <p class="mt-1 text-sm text-muted">«{{ e.text }}»</p>
            <button
              v-if="!e.reciprocated"
              class="mt-1 text-xs font-semibold"
              style="color: rgb(var(--v-theme-primary))"
              @click="brand.reciprocatePeerEndorsement(e.id)"
            >
              ردّ الجميل بتوصية متبادلة
            </button>
          </div>
        </div>
      </div>
    </BaseCard>

    <!-- قصص النجاح -->
    <BaseCard v-if="brand.approvedStories.length">
      <h3 class="mb-3 flex items-center gap-2 font-bold"><BaseIcon name="mdi-trophy-outline" :size="20" style="color: rgb(var(--v-theme-warning))" /> قصص نجاح عملائك</h3>
      <div class="space-y-2">
        <div
          v-for="s in brand.approvedStories"
          :key="s.id"
          class="rounded-ui border-s-4 p-3"
          style="border-color: rgb(var(--v-theme-success)); background: rgba(var(--v-theme-success), 0.08)"
        >
          <div class="text-sm font-bold">{{ s.headline }}</div>
          <p class="mt-1 text-sm text-muted">{{ s.story }}</p>
          <div class="mt-1 text-xs text-muted">— {{ s.clientName }}</div>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
