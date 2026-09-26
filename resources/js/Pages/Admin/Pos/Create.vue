<template>
  <AdminLayout>
    <Head title="Create POS Order" />
    <h1 class="font-display text-2xl font-bold text-charcoal mb-5">Create POS Order</h1>

    <div class="grid lg:grid-cols-5 gap-5">
      <!-- ── Products ── -->
      <div class="lg:col-span-3 bg-white rounded-2xl border border-purple-50 p-4">
        <div class="relative mb-3">
          <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input v-model="productQuery" type="text" placeholder="Search products by name or SKU…" class="input-field text-sm pl-9 py-2.5 w-full">
        </div>
        <div class="grid sm:grid-cols-2 gap-2 max-h-[64vh] overflow-y-auto scrollbar-slim pr-1">
          <button v-for="p in filteredProducts" :key="p.id" type="button" @click="addToCart(p)" :disabled="p.stock <= 0"
            class="flex items-center gap-3 p-2.5 rounded-xl border border-purple-100 text-left hover:border-ink hover:bg-purple-50/40 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <img :src="p.image || '/images/product-placeholder.svg'" class="w-12 h-12 rounded-lg object-cover bg-purple-50 shrink-0" alt="">
            <div class="min-w-0 flex-1">
              <p class="font-medium text-charcoal text-sm truncate">{{ p.name }}</p>
              <p class="text-muted text-xs font-mono">{{ p.sku || '—' }}</p>
              <p class="text-primary font-bold text-sm">৳{{ p.price.toLocaleString() }}
                <span :class="p.stock <= 0 ? 'text-error' : p.stock <= 10 ? 'text-amber-500' : 'text-muted'" class="font-normal text-xs">· {{ p.stock }} in stock</span>
              </p>
            </div>
            <PlusCircleIcon class="w-6 h-6 text-primary shrink-0" />
          </button>
          <p v-if="!filteredProducts.length" class="text-muted text-sm col-span-full text-center py-10">No products match.</p>
        </div>
      </div>

      <!-- ── Order panel ── -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Cart -->
        <div class="bg-white rounded-2xl border border-purple-50 p-4">
          <h3 class="font-semibold text-charcoal mb-3">Cart ({{ cart.length }})</h3>
          <div v-if="!cart.length" class="text-muted text-sm text-center py-8">Add products to begin.</div>
          <div v-else class="space-y-2 max-h-64 overflow-y-auto scrollbar-slim pr-1">
            <div v-for="line in cart" :key="line.product.id" class="flex items-center gap-2 p-2 rounded-lg bg-purple-50/40">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-charcoal truncate">{{ line.product.name }}</p>
                <p class="text-xs text-muted">৳{{ line.product.price.toLocaleString() }} × {{ line.quantity }} = ৳{{ (line.product.price * line.quantity).toLocaleString() }}</p>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <button type="button" @click="dec(line)" class="w-6 h-6 rounded bg-white border border-purple-100 text-charcoal">−</button>
                <span class="w-6 text-center text-sm font-semibold">{{ line.quantity }}</span>
                <button type="button" @click="inc(line)" class="w-6 h-6 rounded bg-white border border-purple-100 text-charcoal">+</button>
                <button type="button" @click="remove(line)" class="w-6 h-6 rounded text-error">✕</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Customer -->
        <div class="bg-white rounded-2xl border border-purple-50 p-4 space-y-3">
          <h3 class="font-semibold text-charcoal">Customer</h3>
          <div class="inline-flex p-1 rounded-xl bg-[#F1F1F2] text-sm w-full">
            <button type="button" @click="form.customer_type = 'existing'" :class="form.customer_type === 'existing' ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="flex-1 px-3 py-1.5 rounded-lg font-semibold">Existing</button>
            <button type="button" @click="form.customer_type = 'walkin'" :class="form.customer_type === 'walkin' ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="flex-1 px-3 py-1.5 rounded-lg font-semibold">Walk-in</button>
          </div>
          <template v-if="form.customer_type === 'existing'">
            <div class="relative">
              <input v-model="customerQuery" type="text" placeholder="Search customer name/phone…" class="input-field text-sm">
              <div v-if="customerQuery && !form.customer_id" class="absolute z-10 mt-1 w-full bg-white border border-purple-100 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                <button v-for="c in filteredCustomers" :key="c.id" type="button" @click="pickCustomer(c)" class="block w-full text-left px-3 py-2 hover:bg-purple-50 text-sm">
                  {{ c.name }} <span class="text-muted text-xs">{{ c.phone }}</span>
                </button>
                <p v-if="!filteredCustomers.length" class="px-3 py-2 text-muted text-sm">No match.</p>
              </div>
            </div>
            <p v-if="form.customer_id" class="text-sm text-success flex items-center gap-1"><CheckCircleIcon class="w-4 h-4" /> {{ form.name }} selected <button type="button" @click="clearCustomer" class="text-error text-xs ml-2 hover:underline">change</button></p>
            <p v-if="form.errors.customer_id" class="text-error text-xs">{{ form.errors.customer_id }}</p>
          </template>
          <template v-else>
            <input v-model="form.name" type="text" placeholder="Full name *" class="input-field text-sm">
            <p v-if="form.errors.name" class="text-error text-xs">{{ form.errors.name }}</p>
            <input v-model="form.email" type="email" placeholder="Email (optional)" class="input-field text-sm">
            <PhoneInput v-model="form.phone" input-class="input-field text-sm" />
          </template>
        </div>

        <!-- Delivery (same fields & order as checkout) -->
        <div class="bg-white rounded-2xl border border-purple-50 p-4 space-y-3">
          <h3 class="font-semibold text-charcoal">Delivery</h3>
          <DeliveryAreaPicker :cities="deliveryCities" :areas="deliveryAreas"
            :model-value="form.delivery_area_id" @update:model-value="form.delivery_area_id = $event"
            :error="form.errors.delivery_area_id" />
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Delivery Address *</label>
            <textarea v-model="form.address" rows="2" placeholder="House, road, flat, landmark…" class="input-field text-sm"></textarea>
            <p v-if="form.errors.address" class="text-error text-xs mt-1">{{ form.errors.address }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Order Notes (optional)</label>
            <textarea v-model="form.notes" rows="2" class="input-field text-sm" placeholder="Any special instructions…"></textarea>
          </div>
        </div>

        <!-- Payment -->
        <div class="bg-white rounded-2xl border border-purple-50 p-4 space-y-3">
          <h3 class="font-semibold text-charcoal">Payment</h3>
          <SelectMenu v-model="form.payment_method" :options="paymentOptions" trigger-class="text-sm" />
          <!-- Location-based delivery charge: entered manually per order -->
          <div v-if="manualDelivery">
            <label class="text-xs text-muted">Delivery Charge (৳)</label>
            <input v-model.number="form.delivery_charge" type="number" min="0" step="0.01" class="input-field text-sm">
            <p v-if="form.errors.delivery_charge" class="text-error text-xs mt-1">{{ form.errors.delivery_charge }}</p>
            <p class="text-[11px] text-muted mt-1">Set manually because “Delivery charge based on location” is ON in Delivery Settings.</p>
          </div>
          <div>
            <label class="text-xs text-muted">Discount (৳)</label>
            <input v-model.number="form.discount" type="number" min="0" class="input-field text-sm">
          </div>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl border border-purple-50 p-4 space-y-2">
          <div class="flex justify-between text-sm"><span class="text-muted">Product total</span><span>৳{{ subtotal.toLocaleString() }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-muted">Delivery</span><span :class="deliveryCharge === 0 ? 'text-success font-semibold' : ''">{{ deliveryCharge === 0 ? 'FREE' : '৳' + deliveryCharge.toLocaleString() }}</span></div>
          <div class="flex justify-between text-sm"><span class="text-muted">Discount</span><span>- ৳{{ (Number(form.discount) || 0).toLocaleString() }}</span></div>
          <div class="flex justify-between font-bold text-lg pt-2 border-t border-purple-100"><span>Grand Total</span><span class="text-primary">৳{{ grandTotal.toLocaleString() }}</span></div>
          <p v-if="form.errors.items" class="text-error text-xs">{{ form.errors.items }}</p>
          <button type="button" @click="submit" :disabled="!cart.length || form.processing" class="btn-primary w-full py-3 mt-2 disabled:opacity-50">
            {{ form.processing ? 'Processing…' : 'Complete Order' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PhoneInput from '@/Components/ui/PhoneInput.vue'
import DeliveryAreaPicker from '@/Components/DeliveryAreaPicker.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { useDelivery } from '@/composables/useDelivery'
import { MagnifyingGlassIcon, PlusCircleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  products: Array, customers: Array, deliveryCities: Array, deliveryAreas: Array, paymentMethods: Array,
})
const del = useDelivery()

// Products
const productQuery = ref('')
const filteredProducts = computed(() => {
  const q = productQuery.value.trim().toLowerCase()
  if (!q) return props.products
  return props.products.filter((p) => p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q))
})

// Cart
const cart = ref([])
const addToCart = (p) => {
  const line = cart.value.find((l) => l.product.id === p.id)
  if (line) { if (line.quantity < p.stock) line.quantity++ }
  else cart.value.push({ product: p, quantity: 1 })
}
const inc = (line) => { if (line.quantity < line.product.stock) line.quantity++ }
const dec = (line) => { line.quantity > 1 ? line.quantity-- : remove(line) }
const remove = (line) => { cart.value = cart.value.filter((l) => l !== line) }

const subtotal = computed(() => cart.value.reduce((s, l) => s + l.product.price * l.quantity, 0))

// Customer
const customerQuery = ref('')
const filteredCustomers = computed(() => {
  const q = customerQuery.value.trim().toLowerCase()
  if (!q) return []
  return props.customers.filter((c) => c.name.toLowerCase().includes(q) || (c.phone || '').includes(q)).slice(0, 8)
})
const pickCustomer = (c) => { form.customer_id = c.id; form.name = c.name; form.phone = c.phone; form.email = c.email || ''; if (c.delivery_address) form.address = c.delivery_address; customerQuery.value = c.name }
const clearCustomer = () => { form.customer_id = null; form.name = ''; customerQuery.value = '' }

// Delivery
const selectedCity = computed(() => {
  const a = props.deliveryAreas.find((x) => x.id === form.delivery_area_id)
  return a ? props.deliveryCities.find((c) => c.id === a.delivery_city_id) : null
})
// "Delivery charge based on location" is on → staff type the charge in themselves
// (free delivery still overrides it).
const manualDelivery = computed(() => del.locationBased && !del.qualifiesFree(subtotal.value))
// Per-city charge; an unset city charge resolves to 0 for POS (staff can adjust).
const deliveryCharge = computed(() => {
  if (manualDelivery.value) return Number(form.delivery_charge) || 0
  return form.delivery_area_id ? (del.charge(subtotal.value, selectedCity.value ? selectedCity.value.delivery_charge : null) ?? 0) : 0
})
const grandTotal = computed(() => Math.max(0, subtotal.value + deliveryCharge.value - (Number(form.discount) || 0)))

const paymentLabel = (m) => ({ cash: 'Cash', card: 'Card', mobile_banking: 'Mobile Banking', bank_transfer: 'Bank Transfer', other: 'Other' }[m] || m)
const paymentOptions = computed(() => (props.paymentMethods || []).map(m => ({ value: m, label: paymentLabel(m) })))

const form = useForm({
  items: [], customer_type: 'walkin', customer_id: null, name: '', phone: '', email: '',
  address: '', delivery_area_id: null, payment_method: 'cash', delivery_charge: 0, discount: 0, notes: '',
})

const submit = () => {
  form.items = cart.value.map((l) => ({ product_id: l.product.id, quantity: l.quantity }))
  form.post(route('admin.pos.store'))
}
</script>
