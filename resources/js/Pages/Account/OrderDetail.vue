<template>
  <AccountLayout>
    <Head :title="`Order ${order.order_number}`" />

    <div class="space-y-5">

      <!-- Back + heading -->
      <div class="flex items-center gap-3">
        <Link :href="route('account.orders')"
          class="w-9 h-9 rounded-xl bg-white border border-purple-100 hover:bg-purple-50 flex items-center justify-center text-muted hover:text-primary transition-all shrink-0">
          <ArrowLeftIcon class="w-4 h-4" />
        </Link>
        <div>
          <h1 class="font-display text-xl font-bold text-charcoal leading-tight">{{ order.order_number }}</h1>
          <p class="text-muted text-xs mt-0.5">Placed on {{ order.created_at }}</p>
        </div>
        <span :class="statusBadge(order.status)" class="ml-auto badge">{{ order.status_label }}</span>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-purple-50">
          <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
            <ArchiveBoxIcon class="w-4.5 h-4.5 text-primary" />
          </div>
          <h3 class="font-display font-semibold text-charcoal">Order Items</h3>
        </div>

        <div class="divide-y divide-purple-50/80">
          <div v-for="item in order.items" :key="item.id"
            class="flex items-center gap-4 px-5 py-4">
            <img :src="item.product_image" :alt="item.product_name"
              class="w-14 h-14 object-cover rounded-xl bg-purple-50 shrink-0">
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-charcoal text-sm truncate">{{ item.product_name }}</p>
              <VariationTags :variations="item.variations" dense />
              <p class="text-muted text-xs mt-0.5">
                ৳{{ Number(item.price).toFixed(0) }} × {{ item.quantity }}
              </p>
              <button v-if="canReviewItem(item)" type="button" @click="openReview(item.id)"
                class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-700">
                <StarIcon class="w-3.5 h-3.5" /> Leave a review
              </button>
              <span v-if="!canReviewItem(item) && item.reviewed" class="mt-1.5 inline-flex items-center gap-1 text-xs text-success font-medium">
                <CheckCircleIcon class="w-3.5 h-3.5" /> Reviewed
              </span>
            </div>
            <p class="font-bold text-charcoal text-sm shrink-0">৳{{ Number(item.total).toFixed(0) }}</p>
          </div>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">

        <!-- Order Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-purple-50">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
              <ReceiptPercentIcon class="w-4.5 h-4.5 text-primary" />
            </div>
            <h3 class="font-display font-semibold text-charcoal">Summary</h3>
          </div>
          <div class="px-5 py-4 space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-muted">Subtotal</span>
              <span class="font-medium text-charcoal">৳{{ Number(order.subtotal).toFixed(0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-muted">Delivery</span>
              <span class="font-medium" :class="pendingDeliveryCharge ? 'text-success' : 'text-charcoal'">
                <template v-if="pendingDeliveryCharge">To be added</template>
                <template v-else>{{ order.delivery_charge == 0 ? 'FREE' : '৳' + Number(order.delivery_charge).toFixed(0) }}</template>
              </span>
            </div>
            <div v-if="order.discount > 0" class="flex justify-between text-sm">
              <span class="text-success">Discount</span>
              <span class="text-success font-medium">−৳{{ Number(order.discount).toFixed(0) }}</span>
            </div>
            <div class="border-t border-purple-100 pt-3 flex justify-between">
              <span class="font-semibold text-charcoal">Total</span>
              <span class="font-bold text-primary text-lg">৳{{ Number(order.total).toFixed(0) }}</span>
            </div>
          </div>
        </div>

        <!-- Delivery Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-purple-50">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
              <TruckIcon class="w-4.5 h-4.5 text-primary" />
            </div>
            <h3 class="font-display font-semibold text-charcoal">Delivery</h3>
          </div>
          <div class="px-5 py-4 space-y-3">
            <div class="flex gap-2.5">
              <MapPinIcon class="w-4 h-4 text-muted mt-0.5 shrink-0" />
              <div>
                <p class="text-sm text-charcoal">{{ order.delivery_address }}</p>
                <p class="text-sm text-muted">{{ order.delivery_city }}</p>
              </div>
            </div>
            <div class="flex gap-2.5 items-center">
              <CreditCardIcon class="w-4 h-4 text-muted shrink-0" />
              <p class="text-sm text-charcoal">
                {{ order.payment_method.toUpperCase() }}
                <span class="text-muted">· {{ order.payment_status }}</span>
              </p>
            </div>
            <div v-if="order.courier_name || order.tracking_number" class="flex gap-2.5 items-start">
              <TruckIcon class="w-4 h-4 text-muted mt-0.5 shrink-0" />
              <div>
                <p v-if="order.courier_name" class="text-sm text-charcoal">{{ order.courier_name }}</p>
                <p v-if="order.tracking_number" class="text-xs text-muted font-mono mt-0.5">Tracking: {{ order.tracking_number }}</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <ReviewModal :open="review.open" :order="order" :preselect-id="review.itemId"
      @close="review.open = false" @submitted="review.open = false" />
  </AccountLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import VariationTags from '@/Components/ui/VariationTags.vue'
import ReviewModal from '@/Components/ReviewModal.vue'
import { statusBadge } from '@/composables/useOrderStatus'
import { useDelivery } from '@/composables/useDelivery'
import {
  ArrowLeftIcon,
  ArchiveBoxIcon,
  ReceiptPercentIcon,
  TruckIcon,
  MapPinIcon,
  CreditCardIcon,
  StarIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ order: Object })

// Location-based charging: 0 means "not set yet", not free.
const del = useDelivery()
const pendingDeliveryCharge = computed(() =>
  Number(props.order?.delivery_charge || 0) === 0 && del.isManual(props.order?.subtotal || 0)
)

const review = ref({ open: false, itemId: null })
const canReviewItem = (item) => props.order.can_review && item.slug && !item.reviewed
const openReview = (itemId) => { review.value = { open: true, itemId } }
</script>
