import { computed, reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Admin-controlled delivery config + charge calculation, shared across the
 * storefront so cart, checkout and promo messaging stay in sync.
 *
 * The actual charge is per-city (delivery_cities.delivery_charge); this
 * composable only applies the free-delivery rules on top of a city's charge.
 * config: { free_enabled, type: 'all'|'minimum', minimum, no_charge_message }
 */
export function useDelivery() {
  const config = computed(() => usePage().props.delivery || {
    free_enabled: false, type: 'all', minimum: 0, no_charge_message: '',
    location_based: false, location_message: '', charges_enabled: true,
  })

  const noChargeMessage = computed(() =>
    config.value.no_charge_message || 'Delivery is not available for the selected city yet. Please choose another city or contact us.'
  )

  // "Delivery charge based on location" — nothing is charged at checkout; staff
  // add the exact amount later (admin order edit) or while creating a POS order.
  const locationBased = computed(() => !!config.value.location_based)
  const locationMessage = computed(() =>
    config.value.location_message || 'Delivery charge will be added based on your location.'
  )
  // Per-city charge table on/off (defaults to on for older saved settings).
  const chargesEnabled = computed(() => config.value.charges_enabled !== false)

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
   * Delivery charge for a subtotal + the selected city's charge.
   *  - free delivery applies              → 0
   *  - cityCharge null/undefined/'' (none) → null  (unknown / not deliverable)
   *  - otherwise                          → the city's charge
   */
  const charge = (subtotal, cityCharge = null) => {
    if (qualifiesFree(subtotal)) return 0
    if (locationBased.value || !chargesEnabled.value) return 0
    if (cityCharge === null || cityCharge === undefined || cityCharge === '') return null
    return Number(cityCharge) || 0
  }

  // True when the charge is deferred to staff rather than shown at checkout.
  const isManual = (subtotal) => locationBased.value && !qualifiesFree(subtotal)

  // reactive() so nested computed refs auto-unwrap when accessed as `del.message` in templates.
  return reactive({
    config, isFreeForAll, freeMinimum, message, noChargeMessage, qualifiesFree, amountToFree, charge,
    locationBased, locationMessage, chargesEnabled, isManual,
  })
}
