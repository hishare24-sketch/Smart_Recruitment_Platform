<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { UserRole } from '@/interfaces/Auth'
import { useAuthStore } from '@/stores/AuthStore'
import { usePersonaStore } from '@/stores/PersonaStore'
import { landingFor } from '@/services/roles'
import { SECTORS, visibleSectors } from '@/services/sectors'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'

const router = useRouter()
const authStore = useAuthStore()
const personaStore = usePersonaStore()

// النيّة = صفات فوريّة تُفعَّل (كل الأدوار SWITCHABLE فوريّة). حساب واحد يحمل أكثر من نيّة.
const intents: { role: UserRole, icon: string, label: string, desc: string }[] = [
  { role: 'seeker', icon: 'mdi-account-search-outline', label: 'أبحث عن فرص', desc: 'وظائف ومشاريع تناسب مهاراتي' },
  { role: 'company', icon: 'mdi-office-building-outline', label: 'أنشر فرصًا وأوظّف', desc: 'أعلن عن فرص وأستقطب المواهب' },
  { role: 'interviewer', icon: 'mdi-star-check-outline', label: 'أقدّم خبرة وأقيّم', desc: 'مقابلات وتقييم وإرشاد واستشارة' },
  { role: 'endorser', icon: 'mdi-hand-heart-outline', label: 'أزكّي زملائي', desc: 'أوصي بمن عملت معهم وأدعم مصداقيتهم' },
]
const selectedIntents = ref<UserRole[]>([])

// القطاع = المحور المشترك (يختاره الجميع بنفس المفاتيح)
const showAllSectors = ref(false)
const sectorList = computed(() => (showAllSectors.value ? SECTORS : visibleSectors()))
const selectedSectors = ref<string[]>([...personaStore.state.interestedSectors])

const step = ref(0)
const totalSteps = 2
const isLast = computed(() => step.value === totalSteps - 1)

function toggle<T>(arr: T[], v: T): T[] {
  return arr.includes(v) ? arr.filter(x => x !== v) : [...arr, v]
}
function toggleIntent(r: UserRole) {
  selectedIntents.value = toggle(selectedIntents.value, r)
}
function toggleSector(id: string) {
  selectedSectors.value = toggle(selectedSectors.value, id)
}

function next() {
  if (isLast.value)
    finish()
  else step.value++
}
function back() {
  if (step.value > 0)
    step.value--
}

function finish() {
  // فعِّل الأدوار المختارة فورًا (النموذج: النيّة صفة فوريّة لا بوّابة)
  selectedIntents.value.forEach(r => authStore.requestRole(r))
  if (selectedIntents.value[0])
    authStore.switchRole(selectedIntents.value[0])
  // احفظ القطاعات المهتمّ بها (تُزامَن عبر PersonaStore)
  if (selectedSectors.value.length)
    personaStore.setInterestedSectors(selectedSectors.value)
  localStorage.setItem('onboardingDone', '1')
  // الوجهة: المركز الموحّد لمالكي دورين نشطين+، وإلا لوحة الدور
  router.push({ name: landingFor(authStore.role, authStore.activeRoles.length) })
}
function skip() {
  localStorage.setItem('onboardingDone', '1')
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-bg p-4">
    <BaseCard class="w-full max-w-2xl text-center">
      <!-- progress dots -->
      <div class="mb-6 flex justify-center gap-1">
        <div
          v-for="i in totalSteps"
          :key="i"
          class="h-1.5 rounded-full transition-all"
          :style="{ width: (i - 1) === step ? '28px' : '10px', background: (i - 1) <= step ? 'rgb(var(--v-theme-accent))' : 'rgb(var(--v-theme-surface-variant))' }"
        />
      </div>

      <!-- الخطوة 0: النيّة -->
      <template v-if="step === 0">
        <h1 class="mb-1 text-2xl font-bold">ماذا تريد أن تفعل على المنصّة؟</h1>
        <p class="mb-6 text-muted">اختر ما يناسبك (يمكنك اختيار أكثر من واحد أو تخطّي الخطوة — وتفعيل المزيد لاحقًا من حسابك).</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <button
            v-for="it in intents"
            :key="it.role"
            type="button"
            class="rounded-ui flex items-start gap-3 border p-4 text-start transition"
            :class="selectedIntents.includes(it.role) ? 'border-transparent' : 'border-ui hover:bg-surfalt'"
            :style="selectedIntents.includes(it.role) ? { background: 'rgba(var(--v-theme-primary), 0.12)' } : {}"
            @click="toggleIntent(it.role)"
          >
            <BaseIcon :name="it.icon" :size="26" :style="{ color: selectedIntents.includes(it.role) ? 'rgb(var(--v-theme-primary))' : 'rgba(var(--v-theme-on-surface), 0.7)' }" />
            <span>
              <span class="block text-sm font-bold">{{ it.label }}</span>
              <span class="block text-xs text-muted">{{ it.desc }}</span>
            </span>
          </button>
        </div>
      </template>

      <!-- الخطوة 1: القطاع -->
      <template v-else>
        <h1 class="mb-1 text-2xl font-bold">في أي قطاع أو مجال أنت؟</h1>
        <p class="mb-6 text-muted">القطاع محور مشترك يخصّص لك الفرص والمطابقة (اختياريّ — اختر ما يناسبك).</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
          <button
            v-for="s in sectorList"
            :key="s.id"
            type="button"
            class="rounded-ui flex flex-col items-center gap-2 border p-3 text-center transition"
            :class="selectedSectors.includes(s.id) ? 'border-transparent' : 'border-ui hover:bg-surfalt'"
            :style="selectedSectors.includes(s.id) ? { background: 'rgba(var(--v-theme-primary), 0.12)' } : {}"
            @click="toggleSector(s.id)"
          >
            <BaseIcon :name="s.icon" :size="24" :style="{ color: selectedSectors.includes(s.id) ? 'rgb(var(--v-theme-primary))' : 'rgba(var(--v-theme-on-surface), 0.7)' }" />
            <span class="text-xs font-bold">{{ s.label }}</span>
          </button>
        </div>
        <button
          v-if="!showAllSectors"
          type="button"
          class="mt-3 text-sm font-bold"
          style="color: rgb(var(--v-theme-primary))"
          @click="showAllSectors = true"
        >
          عرض كل القطاعات
        </button>
      </template>

      <div class="mt-7 flex items-center gap-2">
        <BaseButton v-if="step > 0" variant="ghost" @click="back">
          <BaseIcon name="mdi-arrow-right" :size="18" /> السابق
        </BaseButton>
        <BaseButton variant="accent" size="lg" block @click="next">
          {{ isLast ? 'ابدأ الآن' : 'التالي' }} <BaseIcon :name="isLast ? 'mdi-check' : 'mdi-arrow-left'" :size="18" />
        </BaseButton>
      </div>
      <BaseButton variant="ghost" class="mt-2" @click="skip">تخطّي الإعداد</BaseButton>

      <div class="mt-4 text-xs text-muted">الخطوة {{ step + 1 }} من {{ totalSteps }}</div>
    </BaseCard>
  </div>
</template>
