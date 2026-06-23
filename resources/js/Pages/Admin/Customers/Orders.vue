<template>
  <AdminLayout>
    <Head :title="`${customer.name} — Orders`" />

    <Link :href="route('admin.customers.index')" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-charcoal mb-4">
      <ArrowLeftIcon class="w-4 h-4" /> Back to customers
    </Link>

    <!-- Customer summary -->
    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5 mb-6">
      <div class="flex flex-wrap items-center gap-4">
        <Avatar :name="customer.name" :src="customer.avatar" size="lg" />
        <div class="min-w-0">
          <h1 class="font-display text-xl font-bold text-charcoal">{{ customer.name }}</h1>
          <p class="text-muted text-sm">{{ customer.email }}<span v-if="customer.phone"> · {{ customer.phone }}</span></p>
          <p class="text-muted text-xs mt-0.5">Joined {{ customer.created_at }} · <span class="capitalize">{{ customer.status }}</span></p>
        </div>
        <div class="grid grid-cols-3 gap-3 ml-auto w-full sm:w-auto">
          <div class="text-center px-5 py-3 rounded-xl bg-purple-50/60">
            <p class="text-lg font-bold text-charcoal">{{ customer.orders_count }}</p>
            <p class="text-[11px] text-muted uppercase tracking-wide">Orders</p>
          </div>
          <div class="text-center px-5 py-3 rounded-xl bg-purple-50/60">
            <p class="text-lg font-bold text-charcoal">৳{{ Number(customer.lifetime_spend).toLocaleString() }}</p>
            <p class="text-[11px] text-muted uppercase tracking-wide">Spent</p>
          </div>
          <div class="text-center px-5 py-3 rounded-xl bg-purple-50/60">
            <p class="text-sm font-bold text-charcoal">{{ customer.last_order_at || '—' }}</p>
            <p class="text-[11px] text-muted uppercase tracking-wide">Last order</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[180px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search order #…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.status" @update:model-value="v => { f.status = v; apply() }" :options="statusOptions" trigger-class="text-sm py-2" class="w-40" />
      <SelectMenu :model-value="f.payment_status" @update:model-value="v => { f.payment_status = v; apply() }" :options="paymentOptions" trigger-class="text-sm py-2" class="w-36" />
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="th">Order #</th>
              <th class="th">Date</th>
              <th class="th">Products</th>
              <th class="th">Status</th>
              <th class="th">Payment</th>
              <th class="th text-right">Total</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="o in orders.data" :key="o.id" class="hover:bg-purple-50/20">
              <td class="px-4 py-3 font-medium text-charcoal text-sm">{{ o.order_number }}</td>
              <td class="px-4 py-3 text-muted text-xs">{{ o.created_at }}</td>
              <td class="px-4 py-3 text-xs text-charcoal max-w-[260px] truncate">
                {{ o.products.join(', ') }}<span v-if="o.items_count > o.products.length" class="text-muted"> +{{ o.items_count - o.products.length }} more</span>
              </td>
              <td class="px-4 py-3"><span :class="statusBadge(o.status)" class="badge text-xs">{{ o.status_label }}</span></td>
              <td class="px-4 py-3"><span :class="paymentBadge(o.payment_status)" class="badge text-xs capitalize">{{ o.payment_status }}</span></td>
              <td class="px-4 py-3 text-right font-semibold text-charcoal text-sm">৳{{ Number(o.total).toLocaleString() }}</td>
              <td class="px-4 py-3 text-right">
                <Link :href="route('admin.orders.show', o.id)" class="text-xs text-primary px-3 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">Open</Link>
              </td>
            </tr>
            <tr v-if="!orders.data.length"><td colspan="7" class="px-5 py-12 text-center text-muted text-sm">No orders found.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="orders.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'

const statusOptions = [
  { value: '', label: 'All statuses' },
  ...['pending', 'confirmed', 'processing', 'out_for_delivery', 'completed', 'cancelled']
    .map(s => ({ value: s, label: s.replaceAll('_', ' ').replace(/^\w/, c => c.toUpperCase()) })),
]
const paymentOptions = [
  { value: '', label: 'All payments' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'failed', label: 'Failed' },
]
import Avatar from '@/Components/ui/Avatar.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { statusBadge, paymentBadge } from '@/composables/useOrderStatus'
import { MagnifyingGlassIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ customer: Object, orders: Object, filters: Object })

const f = ref({ search: props.filters?.search || '', status: props.filters?.status || '', payment_status: props.filters?.payment_status || '' })
let t = null
const apply = () => router.get(route('admin.customers.orders', props.customer.id), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(t); t = setTimeout(apply, 350) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
