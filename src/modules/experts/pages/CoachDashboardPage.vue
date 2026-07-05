<script setup lang="ts">
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import ExpertBrandPanel from '../components/ExpertBrandPanel.vue'
import { useExpertRolesStore } from '@/stores/ExpertRolesStore'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSnackbar from '@/components/ui/BaseSnackbar.vue'

// لوحة المرشد المهني — علاقات طويلة الأمد لا جلسات مفردة
const store = useExpertRolesStore()
const snackbar = ref('')

const programDialog = ref(false)
const newProgram = ref({ name: '', duration: 'شهري', price: 400, seats: 8 })
function saveProgram() {
  if (!newProgram.value.name.trim())
    return
  store.addProgram({ ...newProgram.value, name: newProgram.value.name.trim() })
  programDialog.value = false
  snackbar.value = 'أُنشئ البرنامج وأصبح متاحًا للاشتراك'
}

function logSession(clientId: number, name: string) {
  store.bumpClientProgress(clientId)
  snackbar.value = `سُجّلت جلسة إرشاد مع ${name} (+10% تقدم)`
}
</script>

<template>
  <div>
    <PageHeader title="لوحة المرشد المهني" subtitle="رافق عملاءك في رحلات مهنية طويلة الأمد" icon="mdi-compass-outline" />

    <div class="mb-4 grid grid-cols-2 gap-4 md:grid-cols-3">
      <StatCard title="عملاء نشطون" :value="store.coachStats.clients" icon="mdi-account-heart-outline" color="primary" />
      <StatCard title="دخل شهري متكرر" :value="`${store.coachStats.monthlyRecurring} ر.س`" icon="mdi-cash-sync" color="success" />
      <StatCard title="متوسط تقدم العملاء" :value="`${store.coachStats.avgProgress}%`" icon="mdi-trending-up" color="secondary" class="col-span-2 md:col-span-1" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- عملائي -->
      <div class="lg:col-span-2">
        <BaseCard>
          <h2 class="mb-3 font-bold">رحلات عملائي</h2>
          <div v-for="c in store.state.coachClients" :key="c.id" class="mb-4">
            <div class="mb-1 flex items-center gap-3">
              <BaseAvatar color="brand" tonal>{{ c.initial }}</BaseAvatar>
              <div class="flex-1">
                <div class="text-sm font-bold">{{ c.name }}</div>
                <div class="text-xs text-muted">{{ c.goal }}</div>
              </div>
              <BaseButton variant="tonal-emerald" size="sm" @click="logSession(c.id, c.name)">
                <BaseIcon name="mdi-video-outline" :size="16" /> جلسة الآن
              </BaseButton>
            </div>
            <div class="flex items-center gap-2">
              <BaseProgressBar :value="c.progress" color="primary" :height="8" class="flex-1" />
              <span class="text-xs font-bold">{{ c.progress }}%</span>
            </div>
            <div class="mt-1 flex items-center gap-1 text-xs text-muted">
              <BaseIcon name="mdi-calendar-clock-outline" :size="12" /> الجلسة القادمة: {{ c.nextSession }} · {{ c.program }}
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- برامجي + الملف والنمو -->
      <div class="space-y-4">
        <BaseCard>
          <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">برامج الاشتراك</h2>
            <BaseButton variant="tonal-accent" size="sm" @click="programDialog = true"><BaseIcon name="mdi-plus" :size="16" /> برنامج</BaseButton>
          </div>
          <div v-for="p in store.state.coachPrograms" :key="p.id" class="mb-2 rounded-ui border-ui p-3">
            <div class="text-sm font-bold">{{ p.name }}</div>
            <div class="mb-1 text-xs text-muted">{{ p.duration }} · {{ p.price }} ر.س/شهر</div>
            <div class="flex items-center gap-2">
              <BaseProgressBar :value="(p.enrolled / p.seats) * 100" color="secondary" :height="6" class="flex-1" />
              <span class="text-xs">{{ p.enrolled }}/{{ p.seats }}</span>
            </div>
          </div>
          <p class="mt-2 text-xs text-muted">نموذج العمل: اشتراك شهري يخلق علاقة وولاءً طويل الأمد.</p>
        </BaseCard>

        <ExpertBrandPanel />
      </div>
    </div>

    <BaseModal v-model="programDialog" title="برنامج إرشادي جديد" :max-width="420">
      <div class="space-y-3">
        <BaseInput v-model="newProgram.name" label="اسم البرنامج" />
        <BaseSelect v-model="newProgram.duration" :items="['شهري', 'ربع سنوي', 'نصف سنوي'].map(v => ({ value: v, title: v }))" />
        <BaseInput v-model.number="newProgram.price" type="number" label="السعر الشهري (ر.س)" />
        <BaseInput v-model.number="newProgram.seats" type="number" label="عدد المقاعد" />
      </div>
      <template #actions>
        <BaseButton variant="ghost" @click="programDialog = false">إلغاء</BaseButton>
        <BaseButton variant="accent" :disabled="!newProgram.name.trim()" @click="saveProgram">إنشاء</BaseButton>
      </template>
    </BaseModal>

    <BaseSnackbar :model-value="!!snackbar" color="success" :timeout="2500" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </BaseSnackbar>
  </div>
</template>
