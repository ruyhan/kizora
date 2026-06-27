<template>
  <AccountLayout>
    <Head title="My Orders" />

    <div class="space-y-5">

      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">My Orders</h1>
        <p class="text-muted text-sm mt-1">Track and review all your past orders</p>
      </div>

      <div v-if="orders.data?.length" class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div v-for="order in orders.data" :key="order.id"
          class="flex items-center gap-4 px-5 py-4 hover:bg-purple-50/50 transition-colors border-b border-purple-50/70 last:border-0 group">

          <!-- Clickable area → detail -->
          <Link :href="route('account.orders.show', order.id)" class="flex items-center gap-4 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
              <ArchiveBoxIcon class="w-5 h-5 text-primary" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-charcoal text-sm group-hover:text-primary transition-colors">
                {{ order.order_number }}
              </p>
              <p class="text-muted text-xs mt-0.5">
                {{ order.created_at }} · {{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}
              </p>
            </div>
          </Link>

          <!-- Status -->
          <span :class="statusBadge(order.status)" class="badge text-xs shrink-0 hidden sm:inline-flex">
            {{ order.status_label }}
          </span>

          <!-- Amount -->
          <p class="font-bold text-primary text-sm shrink-0">৳{{ Number(order.total).toFixed(0) }}</p>

          <!-- Leave a review -->
          <button v-if="reviewable(order)" type="button" @click="openReview(order)"
            class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-700 shrink-0">
            <StarIcon class="w-4 h-4" /> Review
          </button>

          <!-- Arrow -->
          <Link :href="route('account.orders.show', order.id)" class="shrink-0">
            <ChevronRightIcon class="w-4 h-4 text-muted group-hover:text-primary transition-colors" />
          </Link>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-sm border border-purple-100/50 px-6 py-20 text-center">
        <div class="w-20 h-20 rounded-2xl bg-purple-100 flex items-center justify-center mx-auto mb-5">
          <ArchiveBoxIcon class="w-10 h-10 text-primary" />
        </div>
        <h2 class="font-display text-xl font-bold text-charcoal mb-2">No orders yet</h2>
        <p class="text-muted text-sm mb-6">Looks like you haven't placed any orders. Start shopping!</p>
        <Link :href="route('shop.index')" class="btn-primary text-sm py-2.5 px-6">Browse Products</Link>
      </div>

      <ReviewModal :open="review.open" :order="review.order" @close="review.open = false" @submitted="review.open = false" />

    </div>
  </AccountLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import ReviewModal from '@/Components/ReviewModal.vue'
import { ArchiveBoxIcon, ChevronRightIcon, StarIcon } from '@heroicons/vue/24/outline'
import { statusBadge } from '@/composables/useOrderStatus'

defineProps({ orders: Object })

const review = ref({ open: false, order: {} })
const reviewable = (order) => order.can_review && (order.items || []).some((i) => i.slug && !i.reviewed)
const openReview = (order) => { review.value = { open: true, order } }
</script>
