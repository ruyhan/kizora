<template>
  <div class="bg-white rounded-2xl border border-purple-100 shadow-sm overflow-hidden">

    <!-- ── Gateway Header ──────────────────────────────────────────── -->
    <div class="px-6 py-5 border-b border-purple-50 flex items-center gap-4"
      :style="`border-left:4px solid ${color}`">

      <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden"
        :style="{ backgroundColor: color + '14' }">
        <img v-if="currentLogoUrl" :src="currentLogoUrl" :alt="title"
          class="w-full h-full object-contain p-1.5">
        <span v-else class="font-bold text-lg" :style="{ color }">{{ title[0] }}</span>
      </div>

      <div class="flex-1 min-w-0">
        <h2 class="font-display font-semibold text-charcoal text-lg">{{ title }}</h2>
        <p class="text-xs text-muted mt-0.5">Payment gateway configuration</p>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <span :class="form.enabled === '1' ? 'badge-success' : 'badge-error'" class="badge text-xs">
          {{ form.enabled === '1' ? 'Enabled' : 'Disabled' }}
        </span>
        <span class="badge text-xs"
          :class="form.mode === 'live' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : 'badge-purple'">
          {{ form.mode === 'live' ? 'Live' : 'Sandbox' }}
        </span>
      </div>
    </div>

    <div class="p-6 space-y-8">

      <!-- ── Section 1: Status & Mode ────────────────────────────── -->
      <section>
        <p class="text-sm font-semibold text-charcoal mb-4">Gateway Status &amp; Mode</p>
        <div class="grid sm:grid-cols-2 gap-4">

          <!-- Enable/Disable -->
          <div class="p-4 rounded-xl border border-purple-100 bg-purple-50/30">
            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">Status</p>
            <div class="flex gap-5">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.enabled" value="1" class="accent-primary">
                <span class="text-sm font-medium text-success">Enabled</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.enabled" value="0" class="accent-primary">
                <span class="text-sm font-medium text-muted">Disabled</span>
              </label>
            </div>
          </div>

          <!-- Sandbox/Live -->
          <div class="p-4 rounded-xl border"
            :class="form.mode === 'live' ? 'border-yellow-200 bg-yellow-50/40' : 'border-purple-100 bg-purple-50/30'">
            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">API Mode</p>
            <div class="flex gap-5">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.mode" value="sandbox" class="accent-primary">
                <span class="text-sm font-medium text-charcoal">Sandbox</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.mode" value="live" class="accent-yellow-500">
                <span class="text-sm font-medium text-yellow-700">Live</span>
              </label>
            </div>
            <p v-if="form.mode === 'live'" class="text-xs text-yellow-700 mt-2 flex items-center gap-1">
              <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
              Real transactions will be processed
            </p>
          </div>
        </div>
      </section>

      <!-- ── Section 2: Logo ──────────────────────────────────────── -->
      <section>
        <p class="text-sm font-semibold text-charcoal mb-1">Gateway Logo</p>
        <p class="text-xs text-muted mb-4">Displayed on the checkout page. Pick an image from your Media Library.</p>

        <div class="flex flex-col sm:flex-row items-start gap-5">
          <!-- Preview box -->
          <div class="w-28 h-16 rounded-xl border-2 border-dashed border-purple-200 bg-purple-50/40
                      flex items-center justify-center overflow-hidden shrink-0">
            <img v-if="currentLogoUrl" :src="currentLogoUrl" :alt="title"
              class="max-w-full max-h-full object-contain p-2">
            <span v-else class="text-[10px] text-muted text-center leading-tight px-2">No logo selected</span>
          </div>

          <!-- Library picker controls -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <button type="button" @click="pickerOpen = true"
                class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-2">
                <PhotoIcon class="w-4 h-4" />
                {{ currentLogoUrl ? 'Change Logo' : 'Choose from Library' }}
              </button>

              <button v-if="currentLogoUrl" type="button" @click="deleteLogo" :disabled="deletingLogo"
                class="flex items-center gap-1.5 text-sm text-error hover:underline disabled:opacity-50">
                <TrashIcon class="w-4 h-4" />
                Remove
              </button>
            </div>
            <p v-if="logoError" class="text-error text-xs mt-2">{{ logoError }}</p>
          </div>
        </div>

        <MediaPicker :open="pickerOpen" @close="pickerOpen = false" @select="onLogoSelect" />
      </section>

      <!-- ── Section 3: API Credentials ──────────────────────────── -->
      <section>
        <p class="text-sm font-semibold text-charcoal mb-1">API Credentials</p>
        <p class="text-xs text-muted mb-5">Sensitive fields are encrypted at rest. Leave blank to keep existing values.</p>

        <form @submit.prevent="saveCredentials" class="space-y-4">

          <!-- ── bKash fields ── -->
          <template v-if="gateway === 'bkash'">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">App Key</label>
                <input v-model="form.app_key" type="text" class="input-field"
                  placeholder="bKash App Key" autocomplete="off">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
                  App Secret
                  <span class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">encrypted</span>
                </label>
                <input v-model="form.app_secret" type="password" class="input-field"
                  placeholder="Keep blank to retain existing" autocomplete="new-password">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Username</label>
                <input v-model="form.username" type="text" class="input-field"
                  placeholder="bKash Username" autocomplete="off">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
                  Password
                  <span class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">encrypted</span>
                </label>
                <input v-model="form.password" type="password" class="input-field"
                  placeholder="Keep blank to retain existing" autocomplete="new-password">
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Callback URL</label>
              <input v-model="form.callback_url" type="url" class="input-field"
                :placeholder="callbackUrl('bkash')">
            </div>
          </template>

          <!-- ── Nagad fields ── -->
          <template v-else-if="gateway === 'nagad'">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Merchant ID</label>
                <input v-model="form.merchant_id" type="text" class="input-field"
                  placeholder="Nagad Merchant ID" autocomplete="off">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Merchant Number</label>
                <input v-model="form.merchant_number" type="text" class="input-field"
                  placeholder="01XXXXXXXXX">
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Public Key (Base64)</label>
              <textarea v-model="form.public_key" rows="3"
                class="input-field resize-none font-mono text-xs"
                placeholder="Nagad merchant public key"></textarea>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
                Private Key
                <span class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">encrypted</span>
              </label>
              <textarea v-model="form.private_key" rows="3"
                class="input-field resize-none font-mono text-xs"
                placeholder="Keep blank to retain existing"></textarea>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Callback URL</label>
              <input v-model="form.callback_url" type="url" class="input-field"
                :placeholder="callbackUrl('nagad')">
            </div>
          </template>

          <!-- ── SSLCommerz fields ── -->
          <template v-else-if="gateway === 'sslcommerz'">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Store ID</label>
                <input v-model="form.store_id" type="text" class="input-field"
                  placeholder="SSLCommerz Store ID" autocomplete="off">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
                  Store Password
                  <span class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">encrypted</span>
                </label>
                <input v-model="form.store_password" type="password" class="input-field"
                  placeholder="Keep blank to retain existing" autocomplete="new-password">
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Success URL</label>
                <input v-model="form.success_url" type="url" class="input-field"
                  :placeholder="sslRoute('success')">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Fail URL</label>
                <input v-model="form.fail_url" type="url" class="input-field"
                  :placeholder="sslRoute('fail')">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Cancel URL</label>
                <input v-model="form.cancel_url" type="url" class="input-field"
                  :placeholder="sslRoute('cancel')">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">IPN URL</label>
                <input v-model="form.ipn_url" type="url" class="input-field"
                  :placeholder="sslRoute('ipn')">
              </div>
            </div>
          </template>

          <!-- Save button -->
          <div class="flex items-center justify-between pt-4 border-t border-purple-50">
            <p v-if="credError" class="text-error text-sm">{{ credError }}</p>
            <button type="submit" :disabled="savingCreds"
              class="btn-primary ml-auto flex items-center gap-2 text-sm px-6">
              <span v-if="savingCreds"
                class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin">
              </span>
              {{ savingCreds ? 'Saving…' : 'Save Settings' }}
            </button>
          </div>

        </form>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ExclamationTriangleIcon, TrashIcon, PhotoIcon } from '@heroicons/vue/24/outline'
import MediaPicker from '@/Components/MediaPicker.vue'

const props = defineProps({
  gateway:  { type: String, required: true },
  title:    { type: String, required: true },
  color:    { type: String, default: '#17181B' },
  initial:  { type: Object, default: () => ({}) },
})
const emit = defineEmits(['saved', 'logo-saved', 'logo-deleted'])

// ── Credentials form ──────────────────────────────────────────────────────────
const form = reactive({
  enabled: props.initial.enabled || '0',
  mode:    props.initial.mode    || 'sandbox',
  // bKash
  app_key:         props.initial.app_key         || '',
  app_secret:      props.initial.app_secret      || '',
  username:        props.initial.username        || '',
  password:        props.initial.password        || '',
  // Nagad
  merchant_id:     props.initial.merchant_id     || '',
  merchant_number: props.initial.merchant_number || '',
  public_key:      props.initial.public_key      || '',
  private_key:     props.initial.private_key     || '',
  // SSLCommerz
  store_id:        props.initial.store_id        || '',
  store_password:  props.initial.store_password  || '',
  success_url:     props.initial.success_url     || '',
  fail_url:        props.initial.fail_url        || '',
  cancel_url:      props.initial.cancel_url      || '',
  ipn_url:         props.initial.ipn_url         || '',
  // Shared
  callback_url:    props.initial.callback_url    || '',
})

watch(() => props.initial, (v) => Object.assign(form, v), { deep: true })

const savingCreds = ref(false)
const credError   = ref('')

const saveCredentials = () => {
  savingCreds.value = true
  credError.value   = ''

  const payload = { enabled: form.enabled, mode: form.mode }
  const gw = props.gateway
  if (gw === 'bkash')      Object.assign(payload, { app_key: form.app_key, app_secret: form.app_secret, username: form.username, password: form.password, callback_url: form.callback_url })
  if (gw === 'nagad')      Object.assign(payload, { merchant_id: form.merchant_id, merchant_number: form.merchant_number, public_key: form.public_key, private_key: form.private_key, callback_url: form.callback_url })
  if (gw === 'sslcommerz') Object.assign(payload, { store_id: form.store_id, store_password: form.store_password, success_url: form.success_url, fail_url: form.fail_url, cancel_url: form.cancel_url, ipn_url: form.ipn_url })

  router.post(route('admin.payments.update', gw), payload, {
    preserveScroll: true,
    onSuccess:  () => emit('saved', props.title),
    onError: (errors) => { credError.value = Object.values(errors)[0] ?? 'Save failed.' },
    onFinish: () => { savingCreds.value = false },
  })
}

// ── Logo (Media Library) ───────────────────────────────────────────────────────
const currentLogoUrl = ref(props.initial.logo_url || null)
watch(() => props.initial.logo_url, (v) => { currentLogoUrl.value = v || null })

const pickerOpen   = ref(false)
const logoError    = ref('')
const deletingLogo = ref(false)

const onLogoSelect = (item) => {
  const url = item?.file_url
  if (!url) return
  logoError.value = ''
  router.post(route('admin.payments.logo.upload', props.gateway), { logo_url: url }, {
    preserveScroll: true,
    onSuccess: () => { currentLogoUrl.value = url; emit('logo-saved', props.title) },
    onError:   (errors) => { logoError.value = errors.logo_url ?? 'Could not set logo.' },
  })
}

const deleteLogo = () => {
  if (!confirm('Remove this logo?')) return
  deletingLogo.value = true
  router.delete(route('admin.payments.logo.delete', props.gateway), {
    preserveScroll: true,
    onSuccess: () => { currentLogoUrl.value = null; emit('logo-deleted', props.title) },
    onFinish:  () => { deletingLogo.value = false },
  })
}

// ── URL helpers ───────────────────────────────────────────────────────────────
const callbackUrl = (gw) => {
  try { return route(`payment.${gw}.callback`) } catch { return '' }
}
const sslRoute = (type) => {
  try { return route(`payment.sslcommerz.${type}`) } catch { return '' }
}
</script>
