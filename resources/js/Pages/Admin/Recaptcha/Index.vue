<template>
  <AdminLayout>
    <Head title="Security / CAPTCHA" />

    <div class="space-y-6 w-full max-w-3xl">

      <SettingsTabs />

      <!-- Header -->
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Security · reCAPTCHA</h1>
          <p class="text-muted text-sm mt-1">Protect public forms from spam with Google reCAPTCHA</p>
        </div>
        <span :class="form.enabled ? 'badge-success' : 'badge-gray'" class="badge text-xs">
          {{ form.enabled ? 'Enabled' : 'Disabled' }}
        </span>
      </div>

      <!-- Help note -->
      <div class="flex items-start gap-3 p-4 rounded-2xl bg-purple-50 border border-purple-100">
        <InformationCircleIcon class="w-5 h-5 text-primary shrink-0 mt-0.5" />
        <p class="text-sm text-charcoal/80">
          Create your reCAPTCHA <strong>Site Key</strong> and <strong>Secret Key</strong> in the
          <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-primary hover:underline">Google reCAPTCHA Admin Console</a>,
          then paste them below. reCAPTCHA v3 runs silently with no visible challenge.
        </p>
      </div>

      <form @submit.prevent="save" class="space-y-6">

        <!-- General -->
        <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-5">
          <h3 class="font-display font-semibold text-charcoal">General</h3>

          <div class="grid sm:grid-cols-2 gap-4">
            <!-- Enable -->
            <div class="p-4 rounded-xl border border-purple-100 bg-purple-50/30">
              <p class="text-sm font-semibold text-charcoal mb-3">reCAPTCHA Status</p>
              <div class="flex gap-5">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.enabled" :value="true" class="accent-primary">
                  <span class="text-sm font-medium text-success">Enabled</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.enabled" :value="false" class="accent-primary">
                  <span class="text-sm font-medium text-muted">Disabled</span>
                </label>
              </div>
            </div>

            <!-- Version -->
            <div class="p-4 rounded-xl border border-purple-100 bg-purple-50/30">
              <p class="text-sm font-semibold text-charcoal mb-3">Version</p>
              <div class="flex gap-5">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.version" value="v3" class="accent-primary">
                  <span class="text-sm font-medium text-charcoal">v3 (silent)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.version" value="v2" class="accent-primary">
                  <span class="text-sm font-medium text-charcoal">v2 (checkbox)</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Keys -->
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Site Key</label>
              <input v-model="form.site_key" type="text" class="input-field" placeholder="6Lc…" autocomplete="off">
              <p v-if="errors.site_key" class="text-error text-xs mt-1">{{ errors.site_key }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
                Secret Key
                <span class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">encrypted</span>
              </label>
              <input v-model="form.secret_key" type="password" class="input-field"
                :placeholder="settings.has_secret ? '•••••••• (saved)' : 'Enter secret key'" autocomplete="new-password">
              <p class="text-xs text-muted mt-1">Leave blank to keep the saved key.</p>
            </div>
          </div>

          <!-- Score threshold (v3) -->
          <div v-if="form.version === 'v3'" class="max-w-xs">
            <label class="text-sm font-medium text-charcoal mb-1.5 block">
              v3 Score Threshold
              <span class="text-muted font-normal">({{ Number(form.score_threshold).toFixed(2) }})</span>
            </label>
            <input v-model="form.score_threshold" type="range" min="0" max="1" step="0.1"
              class="w-full accent-primary">
            <div class="flex justify-between text-[10px] text-muted mt-1">
              <span>0 (lenient)</span><span>1 (strict)</span>
            </div>
            <p class="text-xs text-muted mt-1">Requests scoring below this are blocked. 0.5 is recommended.</p>
          </div>
        </div>

        <!-- Per-form protection -->
        <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6">
          <h3 class="font-display font-semibold text-charcoal mb-1">Protected Forms</h3>
          <p class="text-xs text-muted mb-4">Enable reCAPTCHA per form. Disabled forms submit normally.</p>

          <div class="grid sm:grid-cols-2 gap-2">
            <label v-for="f in forms" :key="f"
              class="flex items-center justify-between gap-3 p-3 rounded-xl border border-purple-100 hover:bg-purple-50/40 cursor-pointer transition-colors">
              <span class="text-sm font-medium text-charcoal capitalize">{{ formLabel(f) }}</span>
              <input type="checkbox" v-model="form.forms[f]" class="w-4 h-4 accent-primary">
            </label>
          </div>
        </div>

        <!-- Save -->
        <div class="flex items-center justify-end gap-3">
          <p v-if="!form.enabled" class="text-xs text-muted mr-auto">reCAPTCHA is off — all forms submit normally.</p>
          <button type="submit" :disabled="saving" class="btn-primary flex items-center gap-2 text-sm px-6">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Saving…' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ settings: Object, forms: Array })

const saving = ref(false)
const errors = ref({})

const form = ref({
  enabled:         props.settings.enabled,
  version:         props.settings.version,
  site_key:        props.settings.site_key,
  secret_key:      '',
  score_threshold: props.settings.score_threshold,
  forms:           { ...props.settings.forms },
})

const formLabel = (f) => f.replace(/_/g, ' ')

const save = () => {
  saving.value = true
  errors.value = {}
  router.post(route('admin.recaptcha.update'), form.value, {
    preserveScroll: true,
    onError: (errs) => { errors.value = errs; saving.value = false },
    onSuccess: () => { form.value.secret_key = ''; saving.value = false },
    onFinish: () => { saving.value = false },
  })
}
</script>
