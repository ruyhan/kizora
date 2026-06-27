<template>
  <AdminLayout>
    <Head title="Orders" />

    <div class="flex items-center justify-between gap-3 mb-5">
      <h1 class="font-display text-2xl font-bold text-charcoal">Orders</h1>
      <DateRangeFilter route-name="admin.orders.index" :filters="filters" :params="{ status: statusFilter, search: searchTerm }" />
    </div>

    <!-- Status filter tabs (colourful, with live counts) -->
    <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-1 mb-6">
      <button v-for="t in tabs" :key="t.value" type="button" @click="selectTab(t.value)"
        :class="statusFilter === t.value
          ? tabColors[t.key].active + ' shadow-sm'
          : 'bg-white text-charcoal border border-purple-100 hover:border-purple-200'"
        class="inline-flex items-center gap-2 px-4 h-9 rounded-full text-sm font-semibold whitespace-nowrap transition-all shrink-0">
        <span v-if="statusFilter !== t.value" :class="tabColors[t.key].dot" class="w-2 h-2 rounded-full"></span>
        {{ t.label }}
        <span :class="statusFilter === t.value ? 'bg-white/25 text-white' : 'bg-purple-50 text-muted'"
          class="text-xs font-bold px-1.5 min-w-[20px] h-5 rounded-full inline-flex items-center justify-center">{{ t.count }}</span>
      </button>
    </div>

    <!-- Search -->
    <div class="relative max-w-sm mb-4">
      <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
      <input v-model="searchTerm" @input="onSearch" type="text" placeholder="Search order # or customer…" class="input-field text-sm pl-9 py-2 w-full">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th v-if="canDelete" class="th w-10"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="th">Order #</th>
              <th class="th">Customer</th>
              <th class="th">Date</th>
              <th class="th">Status</th>
              <th class="th">Payment</th>
              <th class="th text-right">Total</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-purple-50/20 transition-colors" :class="selected.includes(order.id) ? 'bg-purple-50/40' : ''">
              <td v-if="canDelete" class="px-5 py-4"><input type="checkbox" :value="order.id" v-model="selected" class="kz-check"></td>
              <td class="px-5 py-4">
                <p class="font-medium text-charcoal text-sm">{{ order.order_number }}
                  <span v-if="order.source && order.source !== 'web'" class="badge badge-purple text-[10px] uppercase ml-1">{{ order.source }}</span>
                </p>
              </td>
              <td class="px-5 py-4 text-sm text-charcoal">{{ order.customer }}</td>
              <td class="px-5 py-4 text-sm text-muted">{{ order.created_at }}</td>
              <td class="px-5 py-4"><span :class="statusBadge(order.status)" class="badge text-xs">{{ order.status_label }}</span></td>
              <td class="px-5 py-4">
                <span class="text-xs text-muted uppercase">{{ order.payment_method }}</span>
                <span :class="paymentBadge(order.payment_status)" class="badge text-[10px] mt-1 block">{{ order.payment_status }}</span>
              </td>
              <td class="px-5 py-4 text-right font-semibold text-charcoal text-sm">৳{{ Number(order.total).toFixed(2) }}</td>
              <td class="px-5 py-4 text-right">
                <button @click="orderModal.open(order.id)" class="text-xs text-primary px-3 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">View</button>
              </td>
            </tr>
            <tr v-if="!orders.data?.length"><td :colspan="canDelete ? 8 : 7" class="px-5 py-12 text-center text-muted text-sm">No orders found</td></tr>
          </tbody>
        </table>
      </div>

      <div v-if="orders.last_page > 1" class="flex justify-center gap-2 p-5 border-t border-purple-50">
        <template v-for="link in orders.links" :key="link.label">
          <Link v-if="link.url" :href="link.url"
            :class="link.active ? 'bg-primary text-white' : 'bg-white text-charcoal hover:bg-purple-50'"
            class="px-4 py-2 rounded-xl text-sm font-medium border border-purple-100 transition-colors" v-html="link.label" />
        </template>
      </div>
    </div>

    <OrderModal ref="orderModal" @updated="onUpdated" />
    <BulkDeleteBar :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected = []" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import OrderModal from '@/Components/admin/OrderModal.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { statusBadge, paymentBadge } from '@/composables/useOrderStatus'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  orders: Object,
  filters: Object,
  counts: { type: Object, default: () => ({}) },
})

const toast = useToast()
const { can } = usePermissions()
const canDelete = can('orders.delete')
const statusFilter = ref(props.filters?.status || '')
const orderModal = ref(null)
const onUpdated = () => router.reload({ only: ['orders', 'counts'] })

// Bulk selection / delete
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.orders.data?.length > 0 && selected.value.length === props.orders.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.orders.data.map((o) => o.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.orders.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(`${selected.value.length} order(s) deleted.`); selected.value = []; router.reload({ only: ['orders', 'counts'] }) },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

const tabColors = {
  all:              { active: 'bg-ink text-white',         dot: 'bg-ink' },
  pending:          { active: 'bg-amber-500 text-white',   dot: 'bg-amber-500' },
  confirmed:        { active: 'bg-blue-500 text-white',    dot: 'bg-blue-500' },
  processing:       { active: 'bg-indigo-500 text-white',  dot: 'bg-indigo-500' },
  out_for_delivery: { active: 'bg-teal-500 text-white',    dot: 'bg-teal-500' },
  completed:        { active: 'bg-emerald-500 text-white', dot: 'bg-emerald-500' },
  cancelled:        { active: 'bg-gray-400 text-white',    dot: 'bg-gray-400' },
}
const tabs = computed(() => [
  { value: '',                 key: 'all',              label: 'All',              count: props.counts.all ?? 0 },
  { value: 'pending',          key: 'pending',          label: 'Pending',          count: props.counts.pending ?? 0 },
  { value: 'confirmed',        key: 'confirmed',        label: 'Confirmed',        count: props.counts.confirmed ?? 0 },
  { value: 'processing',       key: 'processing',       label: 'Processing',       count: props.counts.processing ?? 0 },
  { value: 'out_for_delivery', key: 'out_for_delivery', label: 'Out for Delivery', count: props.counts.out_for_delivery ?? 0 },
  { value: 'completed',        key: 'completed',        label: 'Completed',        count: props.counts.completed ?? 0 },
  { value: 'cancelled',        key: 'cancelled',        label: 'Cancelled',        count: props.counts.cancelled ?? 0 },
])

const dateParams = computed(() => props.filters?.range === 'custom'
  ? { from: props.filters.from, to: props.filters.to }
  : { range: props.filters?.range })

const reload = (overrides = {}) => router.get(route('admin.orders.index'),
  { status: statusFilter.value || undefined, search: searchTerm.value || undefined, ...dateParams.value, ...overrides },
  { preserveState: true, preserveScroll: true, replace: true })

const selectTab = (value) => { statusFilter.value = value; reload({ status: value || undefined }) }

const searchTerm = ref(props.filters?.search || '')
let _st
const onSearch = () => { clearTimeout(_st); _st = setTimeout(reload, 350) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
