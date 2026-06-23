import { computed, reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Admin-controlled delivery config + charge calculation, shared across the
 * storefront so cart, checkout and promo messaging stay in sync.
 *
 * config: { free_enabled, type: 'all'|'minimum', minimum, inside, outside }
 */
export function useDelivery() {
  const config = computed(() => usePage().props.delivery || {
    free_enabled: false, type: 'all', minimum: 0, inside: 80, outside: 150,
  })

  const isFreeForAll = computed(() => config.value.free_enabled && config.value.type === 'all')
  const freeMinimum = computed(() =>
    config.value.free_enabled && config.value.type === 'minimum' ? Number(config.value.minimum) || 0 : null
  )

  // Headline promo message (null when free delivery is off).
  const message = computed(() => {
    if (isFreeForAll.value) return 'Free delivery on all orders'
    if (freeMinimum.value) return `Free delivery on orders above ৳${freeMinimum.value.toLocaleString()}`
    return null
  })

  // Whether a given subtotal currently qualifies for free delivery.
  const qualifiesFree = (subtotal) => {
    const c = config.value
    if (!c.free_enabled) return false
    return c.type === 'all' || Number(subtotal) >= (Number(c.minimum) || 0)
  }

  // How much more is needed for free delivery in minimum mode (0 when N/A or met).
  const amountToFree = (subtotal) => {
    if (freeMinimum.value === null) return 0
    return Math.max(0, freeMinimum.value - Number(subtotal || 0))
  }

  /**
   * Delivery charge for a subtotal + location.
   * insideDhaka: true | false | null (unknown → Inside-Dhaka estimate)
   */
  const charge = (subtotal, insideDhaka = null) => {
    const c = config.value
    if (qualifiesFree(subtotal)) return 0
    const inside = Number(c.inside) || 0
    const outside = Number(c.outside) || 0
    if (insideDhaka === null || insideDhaka === undefined) return inside
    return insideDhaka ? inside : outside
  }

  // reactive() so nested computed refs auto-unwrap when accessed as `del.message` in templates.
  return reactive({ config, isFreeForAll, freeMinimum, message, qualifiesFree, amountToFree, charge })
}
