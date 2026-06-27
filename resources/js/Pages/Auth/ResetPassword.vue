<template>
  <AppLayout>
    <Head title="Reset Password" />

    <section class="flex-1 flex items-center py-8 lg:py-12">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex rounded-3xl overflow-hidden border border-border shadow-sm bg-white lg:min-h-[600px]">

          <!-- ── Left brand panel (lg+) ── -->
          <div class="hidden lg:flex w-5/12 xl:w-[45%] relative overflow-hidden bg-linear-to-br from-purple-900 via-purple-800 to-purple-600 flex-col items-center justify-center p-14 text-center select-none">
            <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 28px 28px;"></div>
            <div class="absolute -top-32 -right-32 w-80 h-80 rounded-full bg-white/8 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-purple-400/20 blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
              <img :src="darkLogo" alt="Kizora" class="h-12 w-auto object-contain mx-auto mb-8" :style="darkInvert ? 'filter: brightness(0) invert(1);' : ''">
              <h2 class="font-display text-4xl font-bold text-white mb-4 leading-snug">{{ c.reset_heading || 'Set a New Password' }}</h2>
              <p class="text-white/60 text-sm leading-relaxed max-w-xs mx-auto">{{ c.reset_subtext || "Choose a strong password you don't use anywhere else." }}</p>
              <div class="mt-10 w-16 h-16 rounded-2xl bg-white/12 flex items-center justify-center mx-auto">
                <LockClosedIcon class="w-8 h-8 text-white" />
              </div>
            </div>
          </div>

          <!-- ── Right form panel ── -->
          <div class="flex-1 bg-white flex items-center justify-center px-6 py-12 lg:px-14">
            <div class="w-full max-w-md">
              <div class="lg:hidden flex items-center gap-3 mb-10">
                <img :src="lightLogo" alt="Kizora" class="h-8 w-auto object-contain">
              </div>

              <h1 class="font-display text-3xl font-bold text-charcoal mb-1.5">{{ c.reset_title || 'Reset Password' }}</h1>
              <p class="text-muted text-sm mb-8">{{ c.reset_subtitle || 'Enter a new password for your account.' }}</p>

              <form class="space-y-5" @submit.prevent="submit">
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address</label>
                  <input v-model="form.email" type="email" class="input-field" :class="{ 'border-error': form.errors.email }" required>
                  <p v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">New Password</label>
                  <input v-model="form.password" type="password" class="input-field" :class="{ 'border-error': form.errors.password }" placeholder="••••••••" required>
                  <p v-if="form.errors.password" class="text-error text-xs mt-1">{{ form.errors.password }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Confirm Password</label>
                  <input v-model="form.password_confirmation" type="password" class="input-field" placeholder="••••••••" required>
                </div>
                <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3.5 text-base disabled:opacity-60">
                  {{ form.processing ? 'Resetting…' : 'Reset Password' }}
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
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { LockClosedIcon } from '@heroicons/vue/24/outline'
import { useBrandLogo } from '@/composables/useBrandLogo'
import { computed } from 'vue'

const props = defineProps({
  token: String,
  email: { type: String, default: '' },
  authContent: { type: Object, default: () => ({}) },
})
const c = computed(() => props.authContent || {})
const { light: lightLogo, dark: darkLogo, darkInvert } = useBrandLogo()

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => form.post(route('password.store'), {
  onFinish: () => form.reset('password', 'password_confirmation'),
})
</script>
