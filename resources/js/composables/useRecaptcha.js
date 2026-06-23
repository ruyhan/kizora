import { usePage } from '@inertiajs/vue3'

/**
 * Reusable Google reCAPTCHA helper.
 *
 *   const { isActiveFor, getToken } = useRecaptcha()
 *   const token = await getToken('login')   // null if disabled, string if ok
 *
 * For v3 it loads the script and silently executes with the form action.
 * Config comes from the shared Inertia prop `recaptcha` (site key only).
 */

let scriptPromise = null

function loadV3Script(siteKey) {
  if (scriptPromise) return scriptPromise
  scriptPromise = new Promise((resolve, reject) => {
    if (window.grecaptcha?.execute) return resolve()
    const s = document.createElement('script')
    s.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`
    s.async = true
    s.defer = true
    s.onload = () => resolve()
    s.onerror = () => { scriptPromise = null; reject(new Error('reCAPTCHA failed to load')) }
    document.head.appendChild(s)
  })
  return scriptPromise
}

export function useRecaptcha() {
  const config = () => usePage().props.recaptcha || { enabled: false }

  const isActiveFor = (form) => {
    const c = config()
    return !!(c.enabled && c.site_key && c.forms?.[form])
  }

  /**
   * Returns a token string when protection is active, or null when the
   * form is not protected (so the caller proceeds normally).
   * Throws on a hard failure so the caller can show an error.
   */
  const getToken = async (form) => {
    const c = config()
    if (!isActiveFor(form)) return null

    // v3 — silent execution
    if (c.version === 'v3') {
      await loadV3Script(c.site_key)
      await new Promise((res) => window.grecaptcha.ready(res))
      return await window.grecaptcha.execute(c.site_key, { action: form })
    }

    // v2 — token must be supplied by the checkbox widget (RecaptchaCheckbox)
    const token = window.__recaptchaV2Tokens?.[form]
    if (!token) throw new Error('captcha-required')
    return token
  }

  return { isActiveFor, getToken, config }
}
