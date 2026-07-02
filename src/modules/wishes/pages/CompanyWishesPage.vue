<script setup lang="ts">
import { computed, ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import { OFFER_STATUS_META, SENT_STATUS_META, useWishesStore } from '@/stores/WishesStore'
import type { ReceivedOffer, SentWish } from '@/stores/WishesStore'

const store = useWishesStore()
const tab = ref('sent')
const snackbar = ref('')

const stats = computed(() => [
  { title: 'رغبات مرسلة', value: store.sentActive.length, icon: 'mdi-send-outline', color: 'primary' },
  { title: 'مقبولة', value: store.sentAccepted, icon: 'mdi-check-circle-outline', color: 'success' },
  { title: 'معلّقة', value: store.sentPending, icon: 'mdi-clock-outline', color: 'warning' },
  { title: 'نسبة القبول', value: `${store.acceptanceRate}%`, icon: 'mdi-percent-outline', color: 'secondary' },
])

// Edit dialog
const editDialog = ref(false)
const editing = ref<SentWish | null>(null)
const editForm = ref({ role: '', amount: '', duration: '', reason: '' })
function openEdit(w: SentWish) {
  editing.value = w
  editForm.value = { role: w.role, amount: w.amount, duration: w.duration, reason: w.reason }
  editDialog.value = true
}
function saveEdit() {
  if (editing.value) {
    store.updateWish(editing.value.id, { ...editForm.value })
    snackbar.value = 'تم تحديث الرغبة'
  }
  editDialog.value = false
}

// Withdraw confirm
const withdrawDialog = ref(false)
const withdrawing = ref<SentWish | null>(null)
function confirmWithdraw(w: SentWish) {
  withdrawing.value = w
  withdrawDialog.value = true
}
function doWithdraw() {
  if (withdrawing.value) {
    store.withdrawWish(withdrawing.value.id)
    snackbar.value = 'سُحبت الرغبة'
  }
  withdrawDialog.value = false
}

function resend(w: SentWish) {
  store.resendWish(w.id)
  snackbar.value = `أُعيد إرسال الرغبة إلى ${w.candidateName} — ستصلك استجابته قريبًا`
}

// Received offers
function accept(o: ReceivedOffer) {
  store.respondOffer(o.id, 'accepted')
  snackbar.value = `قبلت عرض ${o.candidateName}`
}
function decline(o: ReceivedOffer) {
  store.respondOffer(o.id, 'declined')
  snackbar.value = `اعتذرت عن عرض ${o.candidateName}`
}
const negotiateDialog = ref(false)
const negotiating = ref<ReceivedOffer | null>(null)
const counterAmount = ref('')
function openNegotiate(o: ReceivedOffer) {
  negotiating.value = o
  counterAmount.value = o.amount
  negotiateDialog.value = true
}
function sendCounter() {
  if (negotiating.value && counterAmount.value.trim()) {
    store.negotiateOffer(negotiating.value.id, counterAmount.value.trim())
    snackbar.value = 'أُرسل عرضك المضاد — بانتظار رد المرشح'
  }
  negotiateDialog.value = false
}
</script>

<template>
  <div>
    <PageHeader
      title="إدارة الرغبات"
      subtitle="تابع الرغبات المرسلة للمرشحين والواردة منهم"
      icon="mdi-hand-heart-outline"
    />

    <VRow class="mb-2">
      <VCol v-for="s in stats" :key="s.title" cols="6" md="3">
        <StatCard v-bind="s" />
      </VCol>
    </VRow>

    <VTabs v-model="tab" color="primary" class="mb-4">
      <VTab value="sent" prepend-icon="mdi-send-outline">الرغبات المرسلة ({{ store.sentActive.length }})</VTab>
      <VTab value="received" prepend-icon="mdi-inbox-arrow-down-outline">
        الرغبات المستلمة
        <VChip v-if="store.newOffersCount" size="x-small" color="accent" label class="ms-1">{{ store.newOffersCount }}</VChip>
      </VTab>
    </VTabs>

    <VWindow v-model="tab">
      <VWindowItem value="sent">
        <VCard v-if="store.sent.length">
          <VTable>
            <thead>
              <tr>
                <th class="text-start">المرشح</th>
                <th class="text-start">الدور</th>
                <th class="text-start">المقابل</th>
                <th class="text-start">الحالة</th>
                <th class="text-start">التاريخ</th>
                <th class="text-start">إجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="w in store.sent" :key="w.id" :class="{ 'wish-withdrawn': w.status === 'withdrawn' }">
                <td class="font-weight-bold">{{ w.candidateName }}</td>
                <td>{{ w.role }}</td>
                <td>{{ w.amount }}</td>
                <td><VChip :color="SENT_STATUS_META[w.status].color" size="small" label>{{ SENT_STATUS_META[w.status].label }}</VChip></td>
                <td class="text-medium-emphasis">{{ w.date }}</td>
                <td class="text-no-wrap">
                  <VTooltip text="تعديل" location="top">
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon="mdi-pencil" variant="text" size="small" :disabled="w.status !== 'pending'" @click="openEdit(w)" />
                    </template>
                  </VTooltip>
                  <VTooltip v-if="w.status === 'pending'" text="سحب الرغبة" location="top">
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon="mdi-close" variant="text" size="small" color="error" @click="confirmWithdraw(w)" />
                    </template>
                  </VTooltip>
                  <VTooltip v-if="w.status === 'withdrawn' || w.status === 'rejected'" text="إعادة إرسال" location="top">
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon="mdi-send-clock-outline" variant="text" size="small" color="secondary" @click="resend(w)" />
                    </template>
                  </VTooltip>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
        <EmptyState
          v-else
          icon="mdi-hand-heart-outline"
          title="لا رغبات مرسلة"
          description="أرسل رغبة لمرشح مميز من صفحة الترشيحات"
        />
      </VWindowItem>

      <VWindowItem value="received">
        <VRow v-if="store.received.length">
          <VCol v-for="o in store.received" :key="o.id" cols="12" md="6">
            <VCard class="pa-4">
              <div class="d-flex align-center ga-3 mb-2">
                <VAvatar color="secondary" variant="tonal"><span class="font-weight-bold">{{ o.candidateInitial }}</span></VAvatar>
                <div class="flex-grow-1">
                  <div class="text-subtitle-1 font-weight-bold">{{ o.candidateName }}</div>
                  <div class="text-caption text-medium-emphasis">{{ o.service }} · {{ o.date }}</div>
                </div>
                <VChip :color="OFFER_STATUS_META[o.status].color" size="small" label>{{ OFFER_STATUS_META[o.status].label }}</VChip>
              </div>
              <p class="text-caption text-medium-emphasis mb-2">{{ o.note }}</p>
              <div class="text-body-2 mb-3">المقابل: <span class="font-weight-bold">{{ o.amount }}</span></div>
              <div v-if="o.status === 'new' || o.status === 'negotiating'" class="d-flex ga-2">
                <VBtn color="success" size="small" class="flex-grow-1" prepend-icon="mdi-check" @click="accept(o)">قبول</VBtn>
                <VBtn color="warning" variant="outlined" size="small" prepend-icon="mdi-swap-horizontal" :disabled="o.status === 'negotiating'" @click="openNegotiate(o)">
                  {{ o.status === 'negotiating' ? 'بانتظار الرد' : 'تفاوض' }}
                </VBtn>
                <VBtn color="error" variant="outlined" size="small" icon="mdi-close" @click="decline(o)" />
              </div>
            </VCard>
          </VCol>
        </VRow>
        <EmptyState
          v-else
          icon="mdi-inbox-arrow-down-outline"
          title="لا عروض واردة"
          description="ستظهر هنا عروض المرشحين ذوي العرض الذاتي المفعّل"
        />
      </VWindowItem>
    </VWindow>

    <!-- Edit wish -->
    <VDialog v-model="editDialog" max-width="480">
      <VCard class="pa-2">
        <VCardTitle>تعديل الرغبة — {{ editing?.candidateName }}</VCardTitle>
        <VCardText>
          <VTextField v-model="editForm.role" label="الدور" class="mb-3" />
          <VTextField v-model="editForm.amount" label="المقابل" class="mb-3" />
          <VTextField v-model="editForm.duration" label="المدة" class="mb-3" />
          <VTextarea v-model="editForm.reason" label="سبب الرغبة" rows="2" auto-grow />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="editDialog = false">إلغاء</VBtn>
          <VBtn color="primary" variant="flat" @click="saveEdit">حفظ</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Withdraw confirm -->
    <VDialog v-model="withdrawDialog" max-width="400">
      <VCard class="pa-2">
        <VCardTitle>سحب الرغبة</VCardTitle>
        <VCardText>هل تريد سحب رغبتك المرسلة إلى «{{ withdrawing?.candidateName }}»؟ يمكنك إعادة إرسالها لاحقًا.</VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="withdrawDialog = false">تراجع</VBtn>
          <VBtn color="error" variant="flat" @click="doWithdraw">سحب</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Negotiate offer -->
    <VDialog v-model="negotiateDialog" max-width="420">
      <VCard class="pa-2">
        <VCardTitle>تفاوض — {{ negotiating?.candidateName }}</VCardTitle>
        <VCardText>
          <p class="text-body-2 text-medium-emphasis mb-3">اقترح مقابلًا مضادًا وسيصلك رد المرشح كإشعار.</p>
          <VTextField v-model="counterAmount" label="المقابل المقترح" prepend-inner-icon="mdi-cash-multiple" />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="negotiateDialog = false">إلغاء</VBtn>
          <VBtn color="warning" variant="flat" :disabled="!counterAmount.trim()" @click="sendCounter">إرسال العرض</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar :model-value="!!snackbar" color="primary" location="top" timeout="3000" @update:model-value="snackbar = ''">
      {{ snackbar }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.wish-withdrawn {
  opacity: 0.55;
}
</style>
