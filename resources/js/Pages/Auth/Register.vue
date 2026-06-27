<template>
  <AppLayout>
    <Head title="Create Account" />

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

          <h2 class="font-display text-3xl xl:text-4xl font-bold text-white mb-4 leading-snug whitespace-nowrap">
            {{ c.register_heading || 'Join Kizora' }}
          </h2>
          <p class="text-white/60 text-sm leading-relaxed max-w-sm mx-auto mb-10 text-balance">
            {{ c.register_subtext || 'Create your account to unlock exclusive deals, faster checkout, order tracking and a personalised experience.' }}
          </p>

          <!-- Perks list -->
          <div class="flex flex-col gap-3 text-left max-w-xs mx-auto">
            <div v-for="(perk, i) in perks" :key="i" class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-white/12 flex items-center justify-center shrink-0">
                <component :is="perkIcons[i] || CheckBadgeIcon" class="w-4.5 h-4.5 text-white" />
              </div>
              <span class="text-white/75 text-sm">{{ perk.text }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Right form panel ── -->
      <div class="flex-1 bg-white flex items-center justify-center px-6 py-12 lg:px-14 overflow-y-auto">
        <div class="w-full max-w-md">

          <h1 class="font-display text-3xl font-bold text-charcoal mb-1.5">{{ c.register_title || 'Create Account' }}</h1>
          <p class="text-muted text-sm mb-8">{{ c.register_subtitle || 'Join Kizora to enjoy exclusive benefits' }}</p>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Full Name</label>
              <input v-model="form.name" type="text" class="input-field" :class="{'border-error': errors.name}"
                placeholder="Your full name" required>
              <p v-if="errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
            </div>

            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address</label>
              <input v-model="form.email" type="email" class="input-field" :class="{'border-error': errors.email}"
                placeholder="your@email.com" required>
              <p v-if="errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
            </div>

            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Phone Number</label>
              <PhoneInput v-model="form.phone" :input-class="errors.phone ? 'input-field border-error' : 'input-field'" placeholder="Phone number" />
              <p v-if="errors.phone" class="text-error text-xs mt-1">{{ errors.phone }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Password</label>
                <input v-model="form.password" type="password" class="input-field"
                  :class="{'border-error': errors.password}" placeholder="••••••••" required>
                <p v-if="errors.password" class="text-error text-xs mt-1">{{ errors.password }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Confirm</label>
                <input v-model="form.password_confirmation" type="password" class="input-field"
                  placeholder="Repeat" required>
              </div>
            </div>

            <RecaptchaCheckbox form="register" />

            <button type="submit" :disabled="processing" class="btn-primary w-full py-3.5 text-base">
              <span v-if="processing">Creating account...</span>
              <span v-else>Create Account</span>
            </button>
          </form>

          <div class="mt-6 pt-6 border-t border-purple-100">
            <p class="text-center text-sm text-muted">
              Already have an account?
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
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import PhoneInput from '@/Components/ui/PhoneInput.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'
import { ClipboardDocumentCheckIcon, TagIcon, HeartIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline'
import { useBrandLogo } from '@/composables/useBrandLogo'
import { computed } from 'vue'

const props = defineProps({ authContent: { type: Object, default: () => ({}) } })
const c = computed(() => props.authContent || {})
const perkIcons = [ClipboardDocumentCheckIcon, TagIcon, HeartIcon, CheckBadgeIcon]
const perks = computed(() => c.value.register_perks?.length ? c.value.register_perks : [
  { text: 'Track all your orders easily' },
  { text: 'Exclusive discounts & offers' },
  { text: 'Save your favourite items' },
])

const { dark: darkLogo, darkInvert } = useBrandLogo()

const { getToken } = useRecaptcha()
const form = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const errors = ref({})
const processing = ref(false)

const submit = async () => {
  processing.value = true
  errors.value = {}

  let token = null
  try {
    token = await getToken('register')
  } catch {
    errors.value = { email: 'Please complete the verification.' }
    processing.value = false
    return
  }

  router.post(route('register.store'), { ...form.value, recaptcha_token: token }, {
    onError: (errs) => { errors.value = errs; processing.value = false },
  })
}
</script>
