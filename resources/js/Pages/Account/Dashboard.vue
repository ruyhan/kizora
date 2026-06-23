<template>
  <AccountLayout>
    <Head title="My Account" />

    <div class="space-y-6">

      <!-- Page heading -->
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Overview</h1>
        <p class="text-muted text-sm mt-1">Welcome back, {{ user?.name }}!</p>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-3 lg:gap-5">
        <div class="bg-white rounded-2xl p-4 lg:p-5 shadow-sm border border-purple-100/50">
          <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center mb-3">
            <ShoppingBagIcon class="w-5 h-5 text-primary" />
          </div>
          <p class="text-2xl lg:text-3xl font-bold text-charcoal">{{ stats?.total_orders ?? 0 }}</p>
          <p class="text-muted text-xs lg:text-sm mt-0.5">Total Orders</p>
        </div>

        <div class="bg-white rounded-2xl p-4 lg:p-5 shadow-sm border border-purple-100/50">
          <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center mb-3">
            <CheckCircleIcon class="w-5 h-5 text-success" />
          </div>
          <p class="text-2xl lg:text-3xl font-bold text-charcoal">{{ stats?.completed_orders ?? 0 }}</p>
          <p class="text-muted text-xs lg:text-sm mt-0.5">Completed</p>
        </div>

        <div class="bg-white rounded-2xl p-4 lg:p-5 shadow-sm border border-purple-100/50">
          <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center mb-3">
            <HeartIcon class="w-5 h-5 text-rose-500" />
          </div>
          <p class="text-2xl lg:text-3xl font-bold text-charcoal">{{ stats?.wishlist_count ?? 0 }}</p>
          <p class="text-muted text-xs lg:text-sm mt-0.5">Wishlist</p>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-purple-50">
          <h2 class="font-display font-semibold text-charcoal text-lg">Recent Orders</h2>
          <Link :href="route('account.orders')" class="text-primary text-sm font-medium hover:underline">
            View All
          </Link>
        </div>

        <template v-if="recentOrders?.length">
          <Link v-for="order in recentOrders" :key="order.order_number"
            :href="route('account.orders.show', order.id)"
            class="flex items-center gap-3 px-5 py-4 hover:bg-purple-50/50 transition-colors border-b border-purple-50/70 last:border-0 group">
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-charcoal text-sm group-hover:text-primary transition-colors">
                {{ order.order_number }}
              </p>
              <p class="text-muted text-xs mt-0.5">{{ order.created_at }}</p>
            </div>
            <span :class="statusBadge(order.status)" class="badge text-xs shrink-0">{{ order.status_label }}</span>
            <p class="font-bold text-primary text-sm shrink-0">৳{{ Number(order.total).toFixed(0) }}</p>
            <ChevronRightIcon class="w-4 h-4 text-muted group-hover:text-primary transition-colors shrink-0" />
          </Link>
        </template>

        <div v-else class="px-5 py-14 text-center">
          <div class="w-16 h-16 rounded-2xl bg-purple-100 flex items-center justify-center mx-auto mb-4">
            <ShoppingBagIcon class="w-8 h-8 text-primary" />
          </div>
          <p class="text-muted text-sm mb-4">No orders yet</p>
          <Link :href="route('shop.index')" class="btn-primary text-sm py-2.5 px-6">Start Shopping</Link>
        </div>
      </div>

    </div>
  </AccountLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import { statusBadge } from '@/composables/useOrderStatus'
import {
  ShoppingBagIcon,
  CheckCircleIcon,
  HeartIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const user = computed(() => page.props.auth?.user)

defineProps({
  stats: Object,
  recentOrders: Array,
})
</script>
