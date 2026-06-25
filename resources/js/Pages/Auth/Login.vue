<template>
  <AppLayout>
    <Head title="Sign In" />

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
        <div class="absolute top-1/2 right-0 w-40 h-40 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>

        <!-- Content -->
        <div class="relative z-10">
          <img :src="darkLogo" alt="Kizora"
            class="h-12 w-auto object-contain mx-auto mb-8"
            :style="darkInvert ? 'filter: brightness(0) invert(1);' : ''">

          <h2 class="font-display text-3xl xl:text-4xl font-bold text-white mb-4 leading-snug whitespace-nowrap">
            Carry your world
          </h2>
          <p class="text-white/60 text-sm leading-relaxed max-w-sm mx-auto text-balance">
            Premium backpacks, bottles &amp; flasks designed to move with you — built to last, priced for everyone.
          </p>
        </div>
      </div>

      <!-- ── Right form panel ── -->
      <div class="flex-1 bg-white flex items-center justify-center px-6 py-12 lg:px-14 overflow-y-auto">
        <div class="w-full max-w-md">

          <h1 class="font-display text-3xl font-bold text-charcoal mb-1.5">Welcome Back</h1>
          <p class="text-muted text-sm mb-8">Sign in to your account to continue</p>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address</label>
              <input v-model="form.email" type="email" class="input-field" :class="{'border-error': errors.email}"
                placeholder="your@email.com" required>
              <p v-if="errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
            </div>

            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Password</label>
              <div class="relative">
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'"
                  class="input-field pr-12" :class="{'border-error': errors.password}"
                  placeholder="••••••••" required>
                <button type="button" @click="showPassword = !showPassword"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-muted hover:text-primary transition-colors">
                  <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                  <EyeSlashIcon v-else class="w-5 h-5" />
                </button>
              </div>
              <p v-if="errors.password" class="text-error text-xs mt-1">{{ errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-sm text-muted cursor-pointer select-none">
                <input v-model="form.remember" type="checkbox" class="rounded text-primary">
                Remember me
              </label>
              <Link :href="route('password.request')" class="text-sm text-primary font-medium hover:underline">
                Forgot password?
              </Link>
            </div>

            <RecaptchaCheckbox form="login" />

            <button type="submit" :disabled="processing" class="btn-primary w-full py-3.5 text-base">
              <span v-if="processing" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Signing in...
              </span>
              <span v-else>Sign In</span>
            </button>
          </form>

          <div class="mt-6 pt-6 border-t border-purple-100">
            <p class="text-center text-sm text-muted">
              Don't have an account?
              <Link :href="route('register')" class="text-primary font-semibold hover:underline">Create one</Link>
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
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useBrandLogo } from '@/composables/useBrandLogo'

const { dark: darkLogo, darkInvert } = useBrandLogo()
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const { getToken } = useRecaptcha()
const form = ref({ email: '', password: '', remember: false })
const errors = ref({})
const processing = ref(false)
const showPassword = ref(false)

const submit = async () => {
  processing.value = true
  errors.value = {}

  let token = null
  try {
    token = await getToken('login')
  } catch {
    errors.value = { email: 'Please complete the verification.' }
    processing.value = false
    return
  }

  router.post(route('login.store'), { ...form.value, recaptcha_token: token }, {
    onError: (errs) => {
      errors.value = errs
      processing.value = false
    },
  })
}
</script>
