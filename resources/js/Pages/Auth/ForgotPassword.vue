<template>
  <AppLayout>
    <Head title="Forgot Password" />

    <section class="flex-1 flex">

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

          <form class="space-y-5">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address</label>
              <input type="email" class="input-field" placeholder="your@email.com" required>
            </div>
            <button type="submit" class="btn-primary w-full py-3.5 text-base">Send Reset Link</button>
          </form>

          <div class="mt-6 pt-6 border-t border-purple-100">
            <p class="text-center text-sm text-muted">
              Remember your password?
              <Link :href="route('login')" class="text-primary font-semibold hover:underline">Sign in</Link>
            </p>
          </div>
        </div>
      </div>

    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { LockClosedIcon } from '@heroicons/vue/24/outline'
import { useBrandLogo } from '@/composables/useBrandLogo'

const { light: lightLogo, dark: darkLogo, darkInvert } = useBrandLogo()
</script>
