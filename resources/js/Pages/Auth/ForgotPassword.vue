<template>
  <AppLayout>
    <Head title="Forgot Password" />

    <section class="flex-1 flex items-center py-8 lg:py-12">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex rounded-3xl overflow-hidden border border-border shadow-sm bg-white lg:min-h-[600px]">

      <!-- ── Left brand panel (lg+) ── -->
      <div class="hidden lg:flex w-5/12 xl:w-[45%] relative overflow-hidden bg-linear-to-br from-purple-900 via-purple-800 to-purple-600 flex-col items-center justify-center p-14 text-center select-none">

        <!-- Dot grid overlay -->
        <div class="absolute inset-0 opacity-[0.04]"
          style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 28px 28px;"></div>

        <!-- Decorative blobs -->
        <div class="absolute -top-32 -right-32 w-80 h-80 rounded-full bg-white/8 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-purple-400/20 blur-3xl pointer-events-none"></div>

        <!-- Content -->
        <div class="relative z-10">
          <img :src="darkLogo" alt="Kizora"
            class="h-12 w-auto object-contain mx-auto mb-8"
            :style="darkInvert ? 'filter: brightness(0) invert(1);' : ''">

          <h2 class="font-display text-4xl font-bold text-white mb-4 leading-snug">
            Recover Your<br>Account
          </h2>
          <p class="text-white/60 text-sm leading-relaxed max-w-xs mx-auto">
            No worries — it happens to everyone. Enter your email and we'll send you a secure reset link.
          </p>

          <div class="mt-10 w-16 h-16 rounded-2xl bg-white/12 flex items-center justify-center mx-auto">
            <LockClosedIcon class="w-8 h-8 text-white" />
          </div>
        </div>
      </div>

      <!-- ── Right form panel ── -->
      <div class="flex-1 bg-white flex items-center justify-center px-6 py-12 lg:px-14">
        <div class="w-full max-w-md">

          <!-- Mobile logo (hidden on lg+) -->
          <div class="lg:hidden flex items-center gap-3 mb-10">
            <img :src="lightLogo" alt="Kizora" class="h-8 w-auto object-contain">
          </div>

          <h1 class="font-display text-3xl font-bold text-charcoal mb-1.5">Forgot Password?</h1>
          <p class="text-muted text-sm mb-8">Enter your email and we'll send you a reset link.</p>

          <div v-if="sent" class="rounded-2xl bg-green-50 border border-green-100 p-5 text-center">
            <CheckCircleIcon class="w-10 h-10 text-success mx-auto mb-2" />
            <p class="text-charcoal font-medium">Check your inbox</p>
            <p class="text-muted text-sm mt-1">If that email is registered, a password reset link is on its way. The link expires in 60 minutes.</p>
          </div>

          <form v-else class="space-y-5" @submit.prevent="submit">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address</label>
              <input v-model="form.email" type="email" class="input-field" :class="{ 'border-error': errors.email }" placeholder="your@email.com" required>
              <p v-if="errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
            </div>
            <RecaptchaCheckbox form="forgot_password" />
            <button type="submit" :disabled="processing" class="btn-primary w-full py-3.5 text-base disabled:opacity-60">
              {{ processing ? 'Sending…' : 'Send Reset Link' }}
            </button>
          </form>

          <div class="mt-6 pt-6 border-t border-purple-100">
            <p class="text-center text-sm text-muted">
              Remember your password?
              <Link :href="route('login')" class="text-primary font-semibold hover:underline">Sign in</Link>
            </p>
          </div>
        </div>
      </div>

        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { LockClosedIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import { useBrandLogo } from '@/composables/useBrandLogo'
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'

const { light: lightLogo, dark: darkLogo, darkInvert } = useBrandLogo()
const { getToken } = useRecaptcha()

const form = reactive({ email: '' })
const errors = ref({})
const processing = ref(false)
const sent = ref(false)

const submit = async () => {
  processing.value = true
  errors.value = {}
  let token = null
  try { token = await getToken('forgot_password') } catch { /* recaptcha optional */ }
  router.post(route('password.email'), { ...form, recaptcha_token: token }, {
    preserveScroll: true,
    onSuccess: () => { sent.value = !usePage().props.errors?.email; },
    onError: (e) => { errors.value = e },
    onFinish: () => { processing.value = false },
  })
}
</script>
