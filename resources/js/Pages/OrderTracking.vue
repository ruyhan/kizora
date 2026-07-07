<template>
  <AppLayout>
    <Head title="Track Your Order" />
    <div class="site-container py-10 sm:py-14">
      <div class="grid lg:grid-cols-12 gap-8 lg:gap-14 items-start">

        <!-- ── LEFT: title + search (sticky on desktop) ── -->
        <div class="lg:col-span-5 lg:sticky lg:top-28">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-purple-50 mb-5">
            <TruckIcon class="w-6 h-6 text-primary" />
          </span>
          <h1 class="font-display text-3xl sm:text-4xl font-bold text-charcoal mb-2 leading-tight">Track Your Order</h1>
          <p class="text-muted mb-6">Enter your order number to see its status &amp; full details.</p>

          <form @submit.prevent="trackOrder" class="space-y-3">
            <div class="relative">
              <MagnifyingGlassIcon class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none" />
              <input v-model="orderNumber" type="text" class="input-field pl-11 w-full py-3.5" placeholder="e.g. ORD-10102" required>
            </div>
            <button type="submit" class="btn-primary w-full py-3.5">Track order</button>
          </form>

          <p class="text-xs text-muted mt-4 leading-relaxed">
            Your order number is in your confirmation message and on your invoice (e.g. <span class="font-mono text-charcoal">ORD-10102</span>).
          </p>
        </div>

        <!-- ── RIGHT: tracked data ── -->
        <div class="lg:col-span-7">
          <div v-if="order" class="space-y-5">

        <!-- Status header -->
        <div class="card overflow-hidden">
          <div class="px-6 py-7 text-center" :class="headerBg">
            <div :class="[statusColor, statusIconClass]" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3 bg-white/70 ring-4 ring-white/40">
              <component :is="statusIconComponent" class="w-8 h-8" />
            </div>
            <h2 class="font-display text-2xl font-bold text-charcoal">{{ order.order_number }}</h2>
            <p class="text-muted text-sm mt-0.5">Ordered on {{ order.created_at }}</p>
            <span :class="badgeClass" class="inline-flex items-center gap-1.5 mt-3 px-3 py-1 rounded-full text-xs font-semibold">
              <span class="w-1.5 h-1.5 rounded-full" :class="dotClass"></span>{{ order.status_label }}
            </span>
          </div>

          <!-- Progress (hidden when cancelled) -->
          <div v-if="!isCancelled" class="px-6 sm:px-8 py-7 border-t border-purple-50">
            <div class="flex items-start justify-between">
              <div v-for="(step, i) in statusSteps" :key="step.key" class="flex flex-col items-center gap-2 flex-1 min-w-0 relative">
                <div :class="isStepCompleted(step.key) ? 'bg-primary text-white' : (isCurrent(step.key) ? 'bg-primary/15 text-primary ring-2 ring-primary' : 'bg-gray-100 text-muted')"
                  class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-colors relative z-10">
                  <CheckIcon v-if="isStepCompleted(step.key) && !isCurrent(step.key)" class="w-4 h-4" />
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="text-[11px] text-center leading-tight" :class="isStepCompleted(step.key) ? 'text-charcoal font-medium' : 'text-muted'">{{ step.label }}</span>
                <div v-if="i < statusSteps.length - 1"
                  :class="isStepCompleted(statusSteps[i + 1].key) ? 'bg-primary' : 'bg-gray-200'"
                  class="absolute top-[18px] left-1/2 w-full h-0.5 transition-colors"></div>
              </div>
            </div>
          </div>
          <div v-else class="px-6 py-5 border-t border-purple-50 bg-red-50/50 flex items-center justify-center gap-2 text-error text-sm font-medium">
            <XCircleIcon class="w-5 h-5" /> This order was cancelled.
          </div>
        </div>

        <!-- Items -->
        <div class="card overflow-hidden">
          <h3 class="font-display font-semibold text-charcoal px-5 sm:px-6 pt-5 pb-3 text-sm uppercase tracking-wide">
            Items <span class="text-muted font-normal normal-case">· {{ order.items_count }}</span>
          </h3>
          <div class="divide-y divide-purple-50">
            <div v-for="(item, i) in order.items" :key="i" class="flex items-center gap-3 px-5 sm:px-6 py-3.5">
              <img :src="item.image || '/images/product-placeholder.svg'" :alt="item.name" class="w-14 h-14 rounded-xl object-cover bg-purple-50 shrink-0" loading="lazy">
              <div class="flex-1 min-w-0">
                <p class="font-medium text-charcoal text-sm leading-snug line-clamp-2">{{ item.name }}</p>
                <VariationTags :variations="item.variations" dense class="mt-0.5" />
                <p class="text-muted text-xs mt-0.5">৳{{ Number(item.price).toLocaleString() }} × {{ item.quantity }}</p>
              </div>
              <p class="font-semibold text-charcoal text-sm shrink-0">৳{{ Number(item.total).toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <!-- Summary + meta -->
        <div class="grid sm:grid-cols-2 gap-5">
          <div class="card p-5 sm:p-6">
            <h3 class="font-display font-semibold text-charcoal text-sm uppercase tracking-wide mb-3">Summary</h3>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="text-charcoal">৳{{ Number(order.subtotal).toLocaleString() }}</dd></div>
              <div class="flex justify-between"><dt class="text-muted">Delivery</dt><dd :class="order.delivery_charge === 0 ? 'text-success font-medium' : 'text-charcoal'">{{ order.delivery_charge === 0 ? 'FREE' : '৳' + Number(order.delivery_charge).toLocaleString() }}</dd></div>
              <div v-if="order.discount > 0" class="flex justify-between"><dt class="text-success">Discount</dt><dd class="text-success">−৳{{ Number(order.discount).toLocaleString() }}</dd></div>
              <div class="flex justify-between pt-2 mt-1 border-t border-purple-100 font-bold text-base"><dt>Total</dt><dd class="text-primary">৳{{ Number(order.total).toLocaleString() }}</dd></div>
            </dl>
          </div>

          <div class="card p-5 sm:p-6">
            <h3 class="font-display font-semibold text-charcoal text-sm uppercase tracking-wide mb-3">Order info</h3>
            <dl class="space-y-3 text-sm">
              <div class="flex items-center justify-between">
                <dt class="text-muted">Payment</dt>
                <dd class="text-charcoal font-medium">{{ paymentLabel(order.payment_method) }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-muted">Payment status</dt>
                <dd><span :class="payBadge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">{{ order.payment_status }}</span></dd>
              </div>
              <div v-if="order.courier_name" class="flex items-center justify-between">
                <dt class="text-muted">Courier</dt>
                <dd class="text-charcoal font-medium">{{ order.courier_name }}</dd>
              </div>
              <div v-if="order.tracking_number" class="flex items-center justify-between">
                <dt class="text-muted">Tracking no.</dt>
                <dd class="text-charcoal font-mono text-xs bg-purple-50 px-2 py-1 rounded-lg">{{ order.tracking_number }}</dd>
              </div>
              <div v-if="order.delivery_city || order.delivery_area" class="flex items-start justify-between gap-3">
                <dt class="text-muted shrink-0">Delivery to</dt>
                <dd class="text-charcoal text-right">{{ [order.delivery_area, order.delivery_city].filter(Boolean).join(', ') }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-muted">Invoice</dt>
                <dd class="text-charcoal font-mono text-xs">{{ order.invoice_number }}</dd>
              </div>
            </dl>
          </div>
        </div>

            <p class="text-center text-xs text-muted pt-1">Questions about your order? <Link :href="route('contact')" class="text-primary hover:underline font-medium">Contact us</Link>.</p>
          </div>

          <!-- Not found -->
          <div v-else-if="searched" class="card p-10 text-center">
            <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-4">
              <MagnifyingGlassIcon class="w-8 h-8 text-primary/40" />
            </div>
            <p class="font-semibold text-charcoal mb-1">Order not found</p>
            <p class="text-muted text-sm">Please check your order number and try again.</p>
          </div>

          <!-- Placeholder (before searching) -->
          <div v-else class="card border-dashed flex flex-col items-center justify-center text-center px-6 py-16 min-h-[320px]">
            <div class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center mb-4">
              <ArchiveBoxIcon class="w-8 h-8 text-primary/40" />
            </div>
            <p class="font-semibold text-charcoal mb-1">Your order details will appear here</p>
            <p class="text-muted text-sm max-w-xs">Enter your order number on the left and hit <span class="font-medium text-charcoal">Track order</span> to see status, items &amp; totals.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import VariationTags from '@/Components/ui/VariationTags.vue'
import {
  CheckIcon, MagnifyingGlassIcon, ClockIcon, CheckBadgeIcon, FireIcon,
  TruckIcon, CheckCircleIcon, XCircleIcon, ShoppingBagIcon, ArchiveBoxIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ order: Object })
const orderNumber = ref('')
const searched = ref(false)

const trackOrder = () => {
  searched.value = true
  router.get(route('order.track'), { order_number: orderNumber.value }, { preserveState: true })
}

const PAYMENT_LABELS = {
  cod: 'Cash on Delivery', cash: 'Cash', card: 'Card', mobile_banking: 'Mobile Banking',
  bank_transfer: 'Bank Transfer', bkash: 'bKash', nagad: 'Nagad', other: 'Other',
}
const paymentLabel = (m) => PAYMENT_LABELS[m] || (m ? m.replaceAll('_', ' ') : '—')

const statusSteps = [
  { key: 'pending', label: 'Order Placed' },
  { key: 'confirmed', label: 'Confirmed' },
  { key: 'processing', label: 'Processing' },
  { key: 'out_for_delivery', label: 'Out for Delivery' },
  { key: 'completed', label: 'Delivered' },
]
const stepOrder = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'completed']

const isCancelled = computed(() => props.order?.status === 'cancelled')
const isStepCompleted = (k) => props.order ? stepOrder.indexOf(k) <= stepOrder.indexOf(props.order.status) : false
const isCurrent = (k) => props.order?.status === k

const statusIconMap = {
  pending: ClockIcon, confirmed: CheckBadgeIcon, processing: FireIcon,
  out_for_delivery: TruckIcon, completed: CheckCircleIcon, cancelled: XCircleIcon,
}
const statusIconComponent = computed(() => props.order ? (statusIconMap[props.order.status] || ShoppingBagIcon) : ShoppingBagIcon)

const statusColor = computed(() => ({ completed: 'bg-success/10', cancelled: 'bg-red-50', pending: 'bg-warning/10' }[props.order?.status] || 'bg-purple-50'))
const statusIconClass = computed(() => ({ completed: 'text-success', cancelled: 'text-error', pending: 'text-warning' }[props.order?.status] || 'text-primary'))
const headerBg = computed(() => ({ completed: 'bg-gradient-to-b from-green-50 to-transparent', cancelled: 'bg-gradient-to-b from-red-50 to-transparent' }[props.order?.status] || 'bg-gradient-to-b from-purple-50 to-transparent'))
const badgeClass = computed(() => ({ completed: 'bg-green-100 text-green-700', cancelled: 'bg-red-100 text-red-700', pending: 'bg-amber-100 text-amber-700' }[props.order?.status] || 'bg-purple-100 text-primary'))
const dotClass = computed(() => ({ completed: 'bg-green-600', cancelled: 'bg-red-600', pending: 'bg-amber-500' }[props.order?.status] || 'bg-primary'))
const payBadge = computed(() => ({ paid: 'bg-green-100 text-green-700', pending: 'bg-amber-100 text-amber-700', failed: 'bg-red-100 text-red-700', refunded: 'bg-gray-100 text-gray-600', cancelled: 'bg-gray-100 text-gray-600' }[props.order?.payment_status] || 'bg-purple-100 text-primary'))
</script>
