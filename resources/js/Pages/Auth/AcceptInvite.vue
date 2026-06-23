<template>
  <div class="min-h-screen app-gradient flex items-center justify-center p-4">
    <Head title="Accept invitation" />
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-purple-50 p-7">
      <Link :href="route('home')" class="block text-center mb-6">
        <span class="font-display text-2xl font-bold text-ink">Kizora</span>
      </Link>

      <!-- Invalid / expired -->
      <div v-if="invalid" class="text-center py-6">
        <div class="w-16 h-16 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
          <ExclamationTriangleIcon class="w-8 h-8 text-error" />
        </div>
        <h1 class="font-display text-xl font-bold text-charcoal mb-1">Invitation invalid</h1>
        <p class="text-muted text-sm mb-5">This invitation link is invalid, already used, or expired.</p>
        <Link :href="route('login')" class="btn-primary inline-flex px-5 py-2.5 text-sm">Go to sign in</Link>
      </div>

      <!-- Accept form -->
      <div v-else>
        <h1 class="font-display text-xl font-bold text-charcoal text-center mb-1">You're invited!</h1>
        <p class="text-muted text-sm text-center mb-5">
          Join the Kizora team as <span class="font-semibold text-charcoal">{{ role }}</span>. Set your password to activate your account.
        </p>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="lbl">Email</label>
            <input :value="email" type="email" disabled class="input-field bg-purple-50/50">
          </div>
          <div>
            <label class="lbl">Full name *</label>
            <input v-model="form.name" type="text" class="input-field" required>
            <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="lbl">Password *</label>
            <input v-model="form.password" type="password" class="input-field" required placeholder="Min 8 characters">
            <p v-if="form.errors.password" class="err">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="lbl">Confirm password *</label>
            <input v-model="form.password_confirmation" type="password" class="input-field" required>
          </div>
          <button type="submit" :disabled="form.processing" class="btn-primary w-full py-2.5 text-sm">Activate account</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  invalid: { type: Boolean, default: false },
  token: { type: String, default: '' },
  email: { type: String, default: '' },
  role: { type: String, default: '' },
})

const form = useForm({ name: '', password: '', password_confirmation: '' })
const submit = () => form.post(route('invite.complete', props.token))
</script>

<style scoped>
@reference "../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
.err { @apply text-error text-xs mt-1; }
</style>
