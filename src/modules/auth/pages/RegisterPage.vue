<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import type { AccountKind } from '@/interfaces/Auth'
import { useAuthStore } from '@/stores/AuthStore'
import { useInterviewerBrandStore } from '@/stores/InterviewerBrandStore'
import { authService } from '../services/AuthService'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// نوع الحساب — سمة عرض غير مقيّدة (لا تحجب أي دور: يمكن للفرد أن يوظّف وللمنشأة أن تبحث)
const kindOptions: { value: AccountKind, icon: string }[] = [
  { value: 'individual', icon: 'mdi-account-outline' },
  { value: 'organization', icon: 'mdi-domain' },
]
const kind = ref<AccountKind>('individual')

const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')
const passwordConfirm = ref('')
const showPassword = ref(false)
const isLoading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  if (!name.value || !email.value || !password.value) {
    error.value = 'يرجى تعبئة الحقول المطلوبة'
    return
  }
  if (password.value !== passwordConfirm.value) {
    error.value = 'كلمتا المرور غير متطابقتين'
    return
  }
  isLoading.value = true
  try {
    // تسجيل محايد — بلا دور؛ كل الأدوار صفات فوريّة تُفعَّل لاحقًا (فلسفة الحساب الموحّد)
    const user = await authService.register({
      name: name.value,
      email: email.value,
      phone: phone.value,
      password: password.value,
      password_confirmation: passwordConfirm.value,
      kind: kind.value,
    })
    authStore.setAuthUser(user)
    // برنامج الإحالة: الانضمام عبر رابط دعوة مقيّم يُحسب للمُحيل
    const brand = useInterviewerBrandStore()
    if (route.query.ref === brand.state.referralCode)
      brand.creditReferral()
    // onboarding اختياريّ يعرّف بالمنصّة والقطاعات والنيّة
    router.push({ name: 'onboarding' })
  }
  catch (e) {
    error.value = (e as Error).message || 'تعذّر إنشاء الحساب. حاول مرة أخرى.'
  }
  finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <h2 class="mb-1 text-3xl font-bold">
      {{ t('auth.register') }}
    </h2>
    <p class="mb-5 text-muted">
      {{ t('auth.registerSubtitle') }}
    </p>

    <div
      v-if="error"
      class="rounded-ui mb-4 border-s-4 p-3 text-sm"
      style="border-color: rgb(var(--v-theme-error)); background: rgba(var(--v-theme-error), 0.1); color: rgb(var(--v-theme-error))"
    >
      {{ error }}
    </div>

    <!-- نوع الحساب (فرد/منشأة) — غير مقيّد للأدوار -->
    <div class="mb-2 text-sm font-bold">
      {{ t('auth.accountKind') }}
    </div>
    <div class="mb-1 grid grid-cols-2 gap-2">
      <button
        v-for="opt in kindOptions"
        :key="opt.value"
        type="button"
        class="rounded-ui inline-flex items-center justify-center gap-2 border p-3 text-center transition"
        :class="kind === opt.value ? 'bg-brand text-on-brand border-transparent' : 'border-ui hover:bg-surfalt'"
        @click="kind = opt.value"
      >
        <BaseIcon :name="opt.icon" :size="22" :style="kind === opt.value ? {} : { color: 'rgb(var(--v-theme-primary))' }" />
        <span class="text-sm">{{ t(`auth.${opt.value}`) }}</span>
      </button>
    </div>
    <p class="mb-4 text-xs text-muted">
      {{ t('auth.accountKindHint') }}
    </p>

    <form class="space-y-3" @submit.prevent="submit">
      <BaseInput v-model="name" :label="t('auth.name')" prefix-icon="mdi-account-outline" autocomplete="name" />
      <BaseInput v-model="email" :label="t('auth.email')" type="email" prefix-icon="mdi-email-outline" autocomplete="email" />
      <BaseInput v-model="phone" :label="t('auth.phone')" prefix-icon="mdi-phone-outline" autocomplete="tel" />
      <BaseInput
        v-model="password"
        :label="t('auth.password')"
        :type="showPassword ? 'text' : 'password'"
        prefix-icon="mdi-lock-outline"
        autocomplete="new-password"
      >
        <template #suffix>
          <button type="button" class="text-muted" :aria-label="showPassword ? 'إخفاء' : 'إظهار'" @click="showPassword = !showPassword">
            <BaseIcon :name="showPassword ? 'mdi-eye-off' : 'mdi-eye'" :size="20" />
          </button>
        </template>
      </BaseInput>
      <BaseInput
        v-model="passwordConfirm"
        :label="t('auth.confirmPassword')"
        :type="showPassword ? 'text' : 'password'"
        prefix-icon="mdi-lock-check-outline"
        autocomplete="new-password"
      />

      <BaseButton type="submit" variant="accent" size="lg" block :loading="isLoading" class="mt-4">
        {{ t('auth.register') }}
      </BaseButton>
    </form>

    <div class="mt-5 text-center text-sm">
      {{ t('auth.haveAccount') }}
      <RouterLink :to="{ name: 'login' }" class="font-bold" style="color: rgb(var(--v-theme-secondary))">
        {{ t('auth.login') }}
      </RouterLink>
    </div>
  </div>
</template>
