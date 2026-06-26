<template>
  <AdminLayout>
    <Head title="Email · SMTP" />

    <div class="space-y-6 w-full max-w-3xl">
      <SettingsTabs />
      <EmailTabs />

      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">SMTP Configuration</h1>
          <p class="text-muted text-sm mt-1">Configure how your store sends email, then verify and test delivery.</p>
        </div>
        <span :class="form.enabled ? 'badge-success' : 'badge-gray'" class="badge text-xs">
          {{ form.enabled ? 'Sending enabled' : 'Sending disabled' }}
        </span>
      </div>

      <div class="flex items-start gap-3 p-4 rounded-2xl bg-purple-50 border border-purple-100">
        <InformationCircleIcon class="w-5 h-5 text-primary shrink-0 mt-0.5" />
        <p class="text-sm text-charcoal/80">
          Works with Gmail, SendGrid, Mailgun, Amazon SES and any SMTP provider — pick a provider to auto-fill the host/port,
          then enter the credentials. Emails are sent in the background, so make sure a queue worker is running
          (<code class="text-xs bg-white px-1.5 py-0.5 rounded border border-purple-100">php artisan queue:work</code>).
        </p>
      </div>

      <form @submit.prevent="save" class="space-y-6">
        <!-- General -->
        <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-5">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-display font-semibold text-charcoal">Enable email sending</h3>
              <p class="text-xs text-muted mt-0.5">Master switch — when off, no transactional emails are sent.</p>
            </div>
            <button type="button" role="switch" :aria-checked="form.enabled" @click="form.enabled = !form.enabled"
              :class="form.enabled ? 'bg-ink' : 'bg-gray-300'"
              class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors shrink-0">
              <span :class="form.enabled ? 'translate-x-[22px]' : 'translate-x-[3px]'"
                class="inline-block w-[18px] h-[18px] bg-white rounded-full shadow transition-transform"></span>
            </button>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Mail provider</label>
              <SelectMenu v-model="form.driver" :options="driverOptions" @update:modelValue="applyPreset" />
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Encryption</label>
              <SelectMenu v-model="form.encryption" :options="encryptionOptions" />
            </div>
          </div>

          <div class="grid sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
              <label class="text-sm font-medium text-charcoal mb-1.5 block">SMTP host</label>
              <input v-model="form.host" type="text" class="input-field" placeholder="smtp.example.com" autocomplete="off">
              <p v-if="errors.host" class="text-error text-xs mt-1">{{ errors.host }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Port</label>
              <input v-model="form.port" type="text" class="input-field" placeholder="587" autocomplete="off">
            </div>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">SMTP username</label>
              <input v-model="form.username" type="text" class="input-field" autocomplete="off" placeholder="username / API key">
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
                SMTP password
                <span class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">hidden</span>
              </label>
              <input v-model="form.password" type="password" class="input-field" autocomplete="new-password"
                :placeholder="settings.password ? '•••••••• (saved)' : 'Enter password'">
              <p class="text-xs text-muted mt-1">Leave blank to keep the saved password.</p>
            </div>
          </div>
        </div>

        <!-- Sender identity -->
        <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-5">
          <h3 class="font-display font-semibold text-charcoal">Sender identity</h3>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">From email address</label>
              <input v-model="form.from_address" type="email" class="input-field" placeholder="orders@yourstore.com">
              <p v-if="errors.from_address" class="text-error text-xs mt-1">{{ errors.from_address }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">From name</label>
              <input v-model="form.from_name" type="text" class="input-field" placeholder="Your Store">
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Reply-to email</label>
              <input v-model="form.reply_to" type="email" class="input-field" placeholder="support@yourstore.com">
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Admin notification email(s)</label>
              <input v-model="form.admin_recipients" type="text" class="input-field" placeholder="admin@yourstore.com, ops@yourstore.com">
              <p class="text-xs text-muted mt-1">Comma-separated. Receives the “new order” alert.</p>
            </div>
          </div>
        </div>

        <!-- Verify + test -->
        <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-4">
          <h3 class="font-display font-semibold text-charcoal">Verify &amp; test delivery</h3>
          <div class="flex flex-wrap items-end gap-3">
            <button type="button" @click="verify" :disabled="busy.verify"
              class="btn-secondary text-sm px-4 flex items-center gap-2">
              <span v-if="busy.verify" class="w-4 h-4 border-2 border-ink/40 border-t-ink rounded-full animate-spin"></span>
              <SignalIcon v-else class="w-4 h-4" /> Verify connection
            </button>
            <div class="flex-1 min-w-[220px]">
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Send a test email to</label>
              <div class="flex gap-2">
                <input v-model="testTo" type="email" class="input-field flex-1" placeholder="you@example.com">
                <button type="button" @click="sendTest" :disabled="busy.test || !testTo"
                  class="btn-primary text-sm px-4 flex items-center gap-2 shrink-0">
                  <span v-if="busy.test" class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
                  <PaperAirplaneIcon v-else class="w-4 h-4" /> Send test
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button type="submit" :disabled="busy.save" class="btn-primary flex items-center gap-2 text-sm px-6">
            <span v-if="busy.save" class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
            {{ busy.save ? 'Saving…' : 'Save configuration' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import EmailTabs from '@/Components/admin/EmailTabs.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { InformationCircleIcon, SignalIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ settings: Object, presets: Object })

const form = reactive({ ...props.settings, password: '' })
const errors = ref({})
const busy = reactive({ save: false, verify: false, test: false })
const testTo = ref('')

const driverOptions = Object.entries(props.presets).map(([value, p]) => ({ value, label: p.label }))
const encryptionOptions = [
  { value: 'tls', label: 'TLS (recommended)' },
  { value: 'ssl', label: 'SSL' },
  { value: 'none', label: 'None' },
]

const applyPreset = (driver) => {
  const p = props.presets[driver]
  if (!p) return
  if (p.host) form.host = p.host
  if (p.port) form.port = p.port
  if (p.encryption) form.encryption = p.encryption
}

const save = () => {
  busy.save = true
  errors.value = {}
  router.post(route('admin.email.smtp.update'), { ...form }, {
    preserveScroll: true,
    onError: (e) => { errors.value = e },
    onSuccess: () => { form.password = '' },
    onFinish: () => { busy.save = false },
  })
}

const verify = () => {
  busy.verify = true
  router.post(route('admin.email.smtp.verify'), {}, {
    preserveScroll: true,
    onFinish: () => { busy.verify = false },
  })
}

const sendTest = () => {
  busy.test = true
  router.post(route('admin.email.smtp.test'), { to: testTo.value }, {
    preserveScroll: true,
    onFinish: () => { busy.test = false },
  })
}
</script>
