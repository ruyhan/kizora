<template>
  <AppLayout>
    <Head title="Track Your Order" />
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="text-center mb-10">
        <h1 class="font-display text-4xl font-bold text-charcoal mb-3">Track Your Order</h1>
        <p class="text-muted text-lg">Enter your order number to check the status</p>
      </div>

      <div class="card p-8 mb-8">
        <form @submit.prevent="trackOrder" class="flex gap-3">
          <input v-model="orderNumber" type="text" class="input-field flex-1" placeholder="e.g. KZ-ABC123" required>
          <button type="submit" class="btn-primary px-6">Track</button>
        </form>
      </div>

      <!-- Result -->
      <div v-if="order" class="card p-8">
        <div class="text-center mb-6">
          <div :class="[statusColor, statusIconClass]" class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <component :is="statusIconComponent" class="w-10 h-10" />
          </div>
          <h2 class="font-display text-2xl font-bold text-charcoal mb-1">{{ order.order_number }}</h2>
          <p class="text-muted text-sm">Ordered on {{ order.created_at }}</p>
        </div>

        <!-- Progress -->
        <div class="flex items-center justify-between mb-8 overflow-x-auto pb-2">
          <div v-for="(step, i) in statusSteps" :key="step.key"
            class="flex flex-col items-center gap-2 flex-1 min-w-0 relative">
            <div :class="isStepCompleted(step.key) ? 'bg-primary text-white' : 'bg-gray-100 text-muted'"
              class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors">
              <CheckIcon v-if="isStepCompleted(step.key)" class="w-5 h-5" />
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span class="text-xs text-muted text-center leading-tight">{{ step.label }}</span>
            <div v-if="i < statusSteps.length - 1"
              :class="isStepCompleted(step.key) ? 'bg-primary' : 'bg-gray-200'"
              class="absolute top-5 left-1/2 w-full h-0.5 -z-10 transition-colors">
            </div>
          </div>
        </div>

        <div class="bg-purple-50 rounded-2xl p-5 text-center">
          <p class="font-semibold text-charcoal">Current Status: <span class="text-primary">{{ order.status_label }}</span></p>
          <p class="text-muted text-sm mt-1">{{ order.items_count }} items · Total: ৳{{ Number(order.total).toLocaleString() }}</p>
        </div>
      </div>

      <div v-else-if="searched" class="card p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-4">
          <MagnifyingGlassIcon class="w-8 h-8 text-primary/40" />
        </div>
        <p class="font-semibold text-charcoal mb-2">Order not found</p>
        <p class="text-muted text-sm">Please check your order number and try again.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  CheckIcon,
  MagnifyingGlassIcon,
  ClockIcon,
  CheckBadgeIcon,
  FireIcon,
  TruckIcon,
  CheckCircleIcon,
  XCircleIcon,
  ShoppingBagIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ order: Object })
const orderNumber = ref('')
const searched = ref(false)

const trackOrder = () => {
  searched.value = true
  router.get(route('order.track'), { order_number: orderNumber.value }, { preserveState: true })
}

const statusSteps = [
  { key: 'pending', label: 'Order Placed' },
  { key: 'confirmed', label: 'Confirmed' },
  { key: 'processing', label: 'Processing' },
  { key: 'out_for_delivery', label: 'Out for Delivery' },
  { key: 'completed', label: 'Delivered' },
]

const stepOrder = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'completed']

const isStepCompleted = (stepKey) => {
  if (!props.order) return false
  const currentIndex = stepOrder.indexOf(props.order.status)
  const stepIndex = stepOrder.indexOf(stepKey)
  return stepIndex <= currentIndex
}

const statusIconMap = {
  pending: ClockIcon,
  confirmed: CheckBadgeIcon,
  processing: FireIcon,
  out_for_delivery: TruckIcon,
  completed: CheckCircleIcon,
  cancelled: XCircleIcon,
}

const statusIconComponent = computed(() => {
  if (!props.order) return ShoppingBagIcon
  return statusIconMap[props.order.status] || ShoppingBagIcon
})

const statusColor = computed(() => {
  if (!props.order) return 'bg-gray-100'
  const colors = { completed: 'bg-success/10', cancelled: 'bg-red-50', pending: 'bg-warning/10' }
  return colors[props.order.status] || 'bg-purple-50'
})

const statusIconClass = computed(() => {
  if (!props.order) return 'text-muted'
  const colors = { completed: 'text-success', cancelled: 'text-error', pending: 'text-warning' }
  return colors[props.order.status] || 'text-primary'
})
</script>
