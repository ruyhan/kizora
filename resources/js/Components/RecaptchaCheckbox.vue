<template>
  <!-- Only renders the v2 checkbox when v2 is active for this form -->
  <div v-if="show" class="my-1">
    <div ref="holder"></div>
    <p v-if="error" class="text-error text-xs mt-1">Please complete the verification.</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  form:  { type: String, required: true },
  error: { type: Boolean, default: false },
})

const page = usePage()
const cfg  = computed(() => page.props.recaptcha || { enabled: false })
const show = computed(() => !!(cfg.value.enabled && cfg.value.version === 'v2' && cfg.value.forms?.[props.form]))

const holder   = ref(null)
let widgetId   = null

const storeToken = (token) => {
  window.__recaptchaV2Tokens = window.__recaptchaV2Tokens || {}
  window.__recaptchaV2Tokens[props.form] = token || ''
}

const loadScript = () => new Promise((resolve) => {
  if (window.grecaptcha?.render) return resolve()
  if (document.getElementById('recaptcha-v2-script')) {
    const t = setInterval(() => { if (window.grecaptcha?.render) { clearInterval(t); resolve() } }, 100)
    return
  }
  const s = document.createElement('script')
  s.id = 'recaptcha-v2-script'
  s.src = 'https://www.google.com/recaptcha/api.js?render=explicit'
  s.async = true; s.defer = true
  s.onload = () => {
    const t = setInterval(() => { if (window.grecaptcha?.render) { clearInterval(t); resolve() } }, 100)
  }
  document.head.appendChild(s)
})

onMounted(async () => {
  if (!show.value) return
  await loadScript()
  widgetId = window.grecaptcha.render(holder.value, {
    sitekey: cfg.value.site_key,
    callback: (token) => storeToken(token),
    'expired-callback': () => storeToken(''),
    'error-callback':   () => storeToken(''),
  })
})

onBeforeUnmount(() => {
  if (window.__recaptchaV2Tokens) delete window.__recaptchaV2Tokens[props.form]
})

defineExpose({
  reset: () => { if (widgetId !== null && window.grecaptcha) window.grecaptcha.reset(widgetId); storeToken('') },
})
</script>
