<template>
  <AdminLayout>
    <Head title="POS Orders" />
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <h1 class="font-display text-2xl font-bold text-charcoal">POS Orders</h1>
      <Link v-if="can('pos.create')" :href="route('admin.pos.create')" class="btn-primary text-sm px-4 py-2 inline-flex items-center gap-1.5">
        <PlusIcon class="w-4 h-4" /> New POS order
      </Link>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search order # or customer…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.payment_method" @update:model-value="v => { f.payment_method = v; apply() }" :options="paymentOptions" trigger-class="text-sm py-2" class="w-44" />
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th v-if="canDelete" class="th w-10"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="th">Order #</th>
              <th class="th">Invoice</th>
              <th class="th">Customer</th>
              <th class="th">Payment</th>
              <th class="th">Status</th>
              <th class="th text-right">Total</th>
              <th class="th">Date</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="o in orders.data" :key="o.id" class="hover:bg-purple-50/20" :class="selected.includes(o.id) ? 'bg-purple-50/40' : ''">
              <td v-if="canDelete" class="px-4 py-3"><input type="checkbox" :value="o.id" v-model="selected" class="kz-check"></td>
              <td class="px-4 py-3 font-medium text-charcoal text-sm">{{ o.order_number }}</td>
              <td class="px-4 py-3 text-muted text-xs font-mono">{{ o.invoice_number || '—' }}</td>
              <td class="px-4 py-3 text-sm text-charcoal">{{ o.customer }}</td>
              <td class="px-4 py-3"><span class="badge badge-purple text-xs capitalize">{{ paymentLabel(o.payment_method) }}</span></td>
              <td class="px-4 py-3"><span :class="statusBadge(o.status)" class="badge text-xs">{{ o.status_label }}</span></td>
              <td class="px-4 py-3 text-right font-semibold text-charcoal text-sm">৳{{ Number(o.total).toLocaleString() }}</td>
              <td class="px-4 py-3 text-muted text-xs">{{ o.created_at }}</td>
              <td class="px-4 py-3 text-right">
                <button @click="orderModal.open(o.id)" class="text-xs text-primary px-3 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">View</button>
              </td>
            </tr>
            <tr v-if="!orders.data.length"><td :colspan="canDelete ? 9 : 8" class="px-5 py-12 text-center text-muted text-sm">No POS orders yet.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="orders.links" />
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
import Pagination from '@/Components/ui/Pagination.vue'
import OrderModal from '@/Components/admin/OrderModal.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { usePermissions } from '@/composables/usePermissions'
import { statusBadge } from '@/composables/useOrderStatus'
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ orders: Object, filters: Object, paymentMethods: Array })
const paymentOptions = [
  { value: '', label: 'All payments' },
  ...(props.paymentMethods || []).map(m => ({ value: m, label: ({ cash: 'Cash', card: 'Card', mobile_banking: 'Mobile Banking', bank_transfer: 'Bank Transfer', other: 'Other' }[m] || m) })),
]
const { can } = usePermissions()
const toast = useToast()
const canDelete = can('orders.delete')
const orderModal = ref(null)
const onUpdated = () => router.reload({ only: ['orders'] })
const paymentLabel = (m) => ({ cash: 'Cash', card: 'Card', mobile_banking: 'Mobile Banking', bank_transfer: 'Bank Transfer', other: 'Other' }[m] || m)

// Bulk selection / delete
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.orders.data?.length > 0 && selected.value.length === props.orders.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.orders.data.map((o) => o.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.orders.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(`${selected.value.length} order(s) deleted.`); selected.value = []; router.reload({ only: ['orders'] }) },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

const f = ref({ search: props.filters?.search || '', payment_method: props.filters?.payment_method || '' })
let t = null
const apply = () => router.get(route('admin.pos.orders'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(t); t = setTimeout(apply, 350) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
