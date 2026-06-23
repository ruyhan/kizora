import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const FALLBACK = '/images/kizora-logo.png'

/**
 * Brand logos from Settings (Admin → Page Content → Settings).
 *  - light:      logo for LIGHT backgrounds (header). The dark wordmark.
 *  - dark:       logo for DARK backgrounds (footer, admin, auth panels).
 *                Uses the dedicated light/white logo if uploaded, else the
 *                main logo (which then gets `darkInvert` applied).
 *  - darkInvert: true when we must CSS-invert the main logo to show on dark
 *                backgrounds (i.e. no dedicated light logo was uploaded).
 */
export function useBrandLogo() {
  const page = usePage()
  const light = computed(() => page.props.site?.logo || FALLBACK)
  const dark = computed(() => page.props.site?.logo_light || page.props.site?.logo || FALLBACK)
  const darkInvert = computed(() => !page.props.site?.logo_light)
  return { light, dark, darkInvert }
}
