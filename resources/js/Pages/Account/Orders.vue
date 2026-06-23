<template>
  <AccountLayout>
    <Head title="My Orders" />

    <div class="space-y-5">

      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">My Orders</h1>
        <p class="text-muted text-sm mt-1">Track and review all your past orders</p>
      </div>

      <div v-if="orders.data?.length" class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <Link v-for="order in orders.data" :key="order.id"
          :href="route('account.orders.show', order.id)"
          class="flex items-center gap-4 px-5 py-4 hover:bg-purple-50/50 transition-colors border-b border-purple-50/70 last:border-0 group">

          <!-- Icon -->
          <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
            <ArchiveBoxIcon class="w-5 h-5 text-primary" />
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-charcoal text-sm group-hover:text-primary transition-colors">
              {{ order.order_number }}
            </p>
            <p class="text-muted text-xs mt-0.5">
              {{ order.created_at }} · {{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}
            </p>
          </div>

          <!-- Status -->
          <span :class="statusBadge(order.status)" class="badge text-xs shrink-0 hidden sm:inline-flex">
            {{ order.status_label }}
          </span>

          <!-- Amount -->
          <p class="font-bold text-primary text-sm shrink-0">৳{{ Number(order.total).toFixed(0) }}</p>

          <!-- Arrow -->
          <ChevronRightIcon class="w-4 h-4 text-muted group-hover:text-primary transition-colors shrink-0" />
        </Link>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-sm border border-purple-100/50 px-6 py-20 text-center">
        <div class="w-20 h-20 rounded-2xl bg-purple-100 flex items-center justify-center mx-auto mb-5">
          <ArchiveBoxIcon class="w-10 h-10 text-primary" />
        </div>
        <h2 class="font-display text-xl font-bold text-charcoal mb-2">No orders yet</h2>
        <p class="text-muted text-sm mb-6">Looks like you haven't placed any orders. Start shopping!</p>
        <Link :href="route('shop.index')" class="btn-primary text-sm py-2.5 px-6">Browse Products</Link>
      </div>

    </div>
  </AccountLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import { ArchiveBoxIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { statusBadge } from '@/composables/useOrderStatus'

defineProps({ orders: Object })
</script>
