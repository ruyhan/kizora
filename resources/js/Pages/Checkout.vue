<template>
  <AppLayout>
    <Head title="Checkout" />

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="font-display text-3xl md:text-4xl font-bold text-charcoal mb-8">Checkout</h1>

      <form @submit.prevent="submitOrder">
        <div class="grid lg:grid-cols-3 gap-8">
          <!-- Left: Delivery & Payment -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Delivery Information -->
            <div class="card p-6">
              <h2 class="font-display font-semibold text-charcoal text-xl mb-5 flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-primary text-white text-sm flex items-center justify-center font-bold">1</span>
                Delivery Information
              </h2>
              <!-- Saved addresses — one tap fills the whole form -->
              <div v-if="savedAddresses?.length" class="mb-5">
                <p class="text-sm font-medium text-charcoal mb-2">Deliver to a saved address</p>
                <div class="flex flex-wrap gap-2">
                  <button v-for="a in savedAddresses" :key="a.id" type="button" @click="useSavedAddress(a)"
                    :class="usedAddressId === a.id ? 'border-primary bg-purple-50 ring-1 ring-primary/30' : 'border-purple-100 bg-white hover:border-primary/40'"
                    class="text-left rounded-xl border px-3.5 py-2.5 transition-all max-w-full">
                    <p class="text-xs font-bold text-charcoal">{{ a.label }}<span v-if="a.is_default" class="text-primary font-semibold"> · Default</span></p>
                    <p class="text-[11px] text-muted truncate max-w-[260px]">{{ a.name }} · {{ a.address_line }}</p>
                  </button>
                </div>
              </div>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Full Name *</label>
                  <input v-model="form.name" type="text" class="input-field" :class="{'border-error': errors.name}"
                    :placeholder="user?.name || 'Enter full name'">
                  <p v-if="errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address *</label>
                  <input v-model="form.email" type="email" class="input-field" :class="{'border-error': errors.email}"
                    :placeholder="user?.email || 'your@email.com'">
                  <p v-if="errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Phone Number *</label>
                  <PhoneInput v-model="form.phone" :input-class="errors.phone ? 'input-field border-error' : 'input-field'" />
                  <p v-if="errors.phone" class="text-error text-xs mt-1">{{ errors.phone }}</p>
                </div>
                <!-- City + Area picker -->
                <div class="md:col-span-2">
                  <DeliveryAreaPicker
                    :cities="deliveryCities"
                    :areas="deliveryAreas"
                    v-model="form.delivery_area_id"
                    :error="errors.delivery_area_id"
                    warning="Product is not available in your city yet. Please select another delivery city." />
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Delivery Address *</label>
                  <textarea v-model="form.address" rows="3" class="input-field" :class="{'border-error': errors.address}"
                    placeholder="House, road, flat, landmark…"></textarea>
                  <p v-if="errors.address" class="text-error text-xs mt-1">{{ errors.address }}</p>
                  <label v-if="user" class="flex items-center gap-2 mt-2 text-sm text-charcoal cursor-pointer">
                    <input v-model="form.save_address" type="checkbox" class="rounded text-primary">
                    Save this address to my address book
                  </label>
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Order Notes (optional)</label>
                  <textarea v-model="form.notes" rows="2" class="input-field"
                    placeholder="Any special instructions for your order?"></textarea>
                </div>
              </div>
            </div>

            <!-- Payment Method -->
            <div class="card p-6">
              <h2 class="font-display font-semibold text-charcoal text-xl mb-5 flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-primary text-white text-sm flex items-center justify-center font-bold">2</span>
                Payment Method
              </h2>
              <!-- Payment error -->
              <p v-if="errors.payment" class="text-error text-sm mb-3 p-3 bg-red-50 rounded-xl">{{ errors.payment }}</p>

              <div class="space-y-3">
                <label v-for="method in paymentMethods" :key="method.value"
                  :class="form.payment_method === method.value ? 'border-primary bg-purple-50' : 'border-purple-100'"
                  class="flex items-center gap-4 p-4 rounded-2xl border-2 cursor-pointer transition-all hover:border-purple-300">
                  <input v-model="form.payment_method" :value="method.value" type="radio" class="text-primary">

                  <!-- Logo (uploaded) or fallback heroicon -->
                  <div class="w-20 h-8 flex items-center justify-start shrink-0">
                    <img v-if="method.logoUrl"
                      :src="method.logoUrl" :alt="method.label"
                      class="max-h-8 max-w-[80px] object-contain"
                      loading="lazy">
                    <component v-else :is="method.icon" class="w-6 h-6 text-primary" />
                  </div>

                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-charcoal">{{ method.label }}</p>
                    <p class="text-muted text-sm">{{ method.desc }}</p>
                  </div>
                  <span :class="method.badgeClass" class="badge text-xs shrink-0">{{ method.badge }}</span>
                </label>
              </div>

              <!-- Gateway redirect notice -->
              <p v-if="form.payment_method !== 'cod'" class="text-xs text-muted mt-3 flex items-start gap-1.5">
                <ShieldCheckIcon class="w-3.5 h-3.5 shrink-0 mt-0.5 text-success" />
                You will be securely redirected to complete payment after placing your order.
              </p>
            </div>
          </div>

          <!-- Right: Order Summary -->
          <div class="lg:col-span-1">
            <div class="card p-6 sticky top-24">
              <h3 class="font-display font-semibold text-charcoal text-xl mb-5">Order Summary</h3>

              <!-- Items -->
              <div class="space-y-3 mb-5 max-h-60 overflow-y-auto">
                <div v-for="item in cart.items" :key="item.id"
                  class="flex gap-3 items-center">
                  <img :src="item.image" :alt="item.name"
                    class="w-12 h-12 object-cover rounded-xl shrink-0">
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-charcoal text-sm truncate">{{ item.name }}</p>
                    <VariationTags :variations="item.variations" dense />
                    <p class="text-muted text-xs">x{{ item.quantity }}</p>
                  </div>
                  <p class="font-semibold text-charcoal text-sm shrink-0">৳{{ formatPrice(item.total) }}</p>
                </div>
              </div>

              <hr class="border-purple-100 mb-4">

              <!-- Totals -->
              <div class="space-y-2 mb-6">
                <div class="flex justify-between text-sm">
                  <span class="text-muted">Subtotal</span>
                  <span class="font-medium text-charcoal">৳{{ formatPrice(cart.subtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-muted">
                    Delivery<span v-if="!freeApplied && selectedCity" class="text-xs"> ({{ selectedCity.name }})</span>
                  </span>
                  <span :class="deliveryCharge === 0 ? 'text-success font-semibold' : ''">
                    <template v-if="noChargeForCity">—</template>
                    <template v-else-if="deliveryCharge === null">Select city</template>
                    <template v-else>{{ deliveryCharge === 0 ? 'FREE' : '৳' + formatPrice(deliveryCharge) }}</template>
                  </span>
                </div>
                <p v-if="freeApplied" class="text-xs text-success font-medium flex items-center gap-1">
                  <SparklesIcon class="w-3.5 h-3.5" /> Free delivery applied
                </p>
                <p v-else-if="del.freeMinimum" class="text-xs text-muted">
                  Add ৳{{ formatPrice(del.amountToFree(cart.subtotal)) }} more for free delivery
                </p>
                <div v-if="cart.discount > 0" class="flex justify-between text-sm">
                  <span class="text-success">Coupon Discount</span>
                  <span class="text-success font-semibold">-৳{{ formatPrice(cart.discount) }}</span>
                </div>
                <hr class="border-purple-100">
                <div class="flex justify-between font-bold text-lg">
                  <span class="text-charcoal">Total</span>
                  <span class="text-primary">৳{{ formatPrice(orderTotal) }}</span>
                </div>
              </div>

              <RecaptchaCheckbox form="checkout" class="mb-3 flex justify-center" />

              <!-- Unavailable-city notice -->
              <p v-if="cityUnavailable" class="text-error text-xs mb-3 text-center">
                Product is not available in your city yet. Please select another delivery city.
              </p>
              <!-- City has no delivery charge configured -->
              <p v-else-if="noChargeForCity" class="text-error text-xs mb-3 text-center">
                {{ del.noChargeMessage }}
              </p>

              <!-- Submit -->
              <button type="submit" :disabled="submitting || cityUnavailable || noChargeForCity"
                class="btn-primary w-full py-4 text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="submitting" class="flex items-center justify-center gap-2">
                  <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  Placing Order...
                </span>
                <span v-else class="flex items-center justify-center gap-2">
                  <SparklesIcon class="w-5 h-5" /> Place Order
                </span>
              </button>

              <p class="text-center text-xs text-muted mt-4">
                By placing your order, you agree to our
                <Link :href="route('terms')" class="text-primary hover:underline">Terms & Conditions</Link>.
              </p>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeliveryAreaPicker from '@/Components/DeliveryAreaPicker.vue'
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import VariationTags from '@/Components/ui/VariationTags.vue'
import PhoneInput from '@/Components/ui/PhoneInput.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'
import { useDelivery } from '@/composables/useDelivery'
import { BanknotesIcon, DevicePhoneMobileIcon, CreditCardIcon, SparklesIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'

const { getToken } = useRecaptcha()

const props = defineProps({
  cart:            Object,
  user:            Object,
  deliveryCities:  { type: Array, default: () => [] },
  deliveryAreas:   { type: Array, default: () => [] },
  savedAddresses:  { type: Array, default: () => [] },
  // Object { bkash: { logo_url: '...' }, nagad: {...}, sslcommerz: {...} }
  enabledGateways: { type: Object, default: () => ({}) },
})

const errors     = ref({})
const submitting = ref(false)

// Base gateway definitions
const GATEWAY_META = {
  cod:        { value: 'cod',        icon: BanknotesIcon,         label: 'Cash on Delivery', desc: 'Pay when your order arrives',                          badge: 'Available',          badgeClass: 'badge-success' },
  bkash:      { value: 'bkash',      icon: DevicePhoneMobileIcon, label: 'bKash',             desc: 'Pay securely via bKash mobile banking',                badge: 'Redirects to bKash', badgeClass: 'badge-purple'  },
  nagad:      { value: 'nagad',      icon: CreditCardIcon,        label: 'Nagad',             desc: 'Pay securely via Nagad digital wallet',                badge: 'Redirects to Nagad', badgeClass: 'badge-purple'  },
  sslcommerz: { value: 'sslcommerz', icon: ShieldCheckIcon,       label: 'Card / Net Banking', desc: 'Visa, Mastercard, or internet banking via SSLCommerz', badge: 'Secure payment',     badgeClass: 'badge-purple'  },
}

// COD always shown; gateways only if enabled (prop is an object keyed by gateway name)
const paymentMethods = computed(() => {
  const list = [{ ...GATEWAY_META.cod, logoUrl: null }]
  for (const [gw, meta] of Object.entries(props.enabledGateways)) {
    if (GATEWAY_META[gw]) {
      list.push({ ...GATEWAY_META[gw], logoUrl: meta.logo_url || null })
    }
  }
  return list
})

// Only pre-fill the saved area if it belongs to a currently-enabled city
const savedAreaId = props.user?.delivery_area_id ?? null
const savedAreaAvailable = savedAreaId
  ? props.deliveryAreas.some(a => a.id === savedAreaId)
  : false

const form = ref({
  name:             props.user?.name             || '',
  email:            props.user?.email            || '',
  phone:            props.user?.phone            || '',
  address:          props.user?.delivery_address || '',
  delivery_area_id: savedAreaAvailable ? savedAreaId : null,
  payment_method:   'cod',
  notes:            '',
  save_address:     false,
})

// Fill the form from a saved address (address book)
const usedAddressId = ref(null)
const useSavedAddress = (a) => {
  usedAddressId.value = a.id
  form.value.name    = a.name || form.value.name
  form.value.phone   = a.phone || form.value.phone
  form.value.address = a.address_line
  form.value.save_address = false // already saved
}

// True when an area is selected but its city is not active for delivery
const cityUnavailable = computed(() => {
  if (!form.value.delivery_area_id) return false
  const area = props.deliveryAreas.find(a => a.id === form.value.delivery_area_id)
  if (!area) return false
  const city = props.deliveryCities.find(c => c.id === area.delivery_city_id)
  return city ? !city.is_active : false
})

const formatPrice = (p) => Number(p || 0).toLocaleString()

// ── Delivery charge (per-city + free-delivery rules, admin-controlled) ──
const del = useDelivery()
const selectedCity = computed(() => {
  const area = props.deliveryAreas.find(a => a.id === form.value.delivery_area_id)
  return area ? props.deliveryCities.find(c => c.id === area.delivery_city_id) : null
})
// null = no city chosen yet OR the chosen city has no charge (blocks checkout)
const deliveryCharge = computed(() =>
  del.charge(props.cart.subtotal, selectedCity.value ? selectedCity.value.delivery_charge : null)
)
const freeApplied = computed(() => del.qualifiesFree(props.cart.subtotal))
// A city is picked but has no charge configured (and free delivery isn't covering it).
const noChargeForCity = computed(() =>
  !!selectedCity.value && !freeApplied.value &&
  (selectedCity.value.delivery_charge === null || selectedCity.value.delivery_charge === undefined)
)
const orderTotal = computed(() =>
  Number(props.cart.subtotal || 0) + Number(deliveryCharge.value || 0) - Number(props.cart.discount || 0)
)

const submitOrder = async () => {
  submitting.value = true
  errors.value = {}

  let token = null
  try {
    token = await getToken('checkout')
  } catch {
    errors.value = { payment: 'Please complete the verification.' }
    submitting.value = false
    return
  }

  router.post(route('checkout.store'), { ...form.value, recaptcha_token: token }, {
    onError: (errs) => {
      errors.value     = errs
      submitting.value = false
    },
    onSuccess: () => { submitting.value = false },
  })
}
</script>
