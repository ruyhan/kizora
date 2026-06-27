<template>
  <AdminLayout>
    <Head title="Customers" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Customers</h1>
        <p class="text-muted text-sm">{{ totals.count }} consumers · {{ totals.active }} active</p>
      </div>
      <DateRangeFilter route-name="admin.customers.index" :filters="filters" :params="f" />
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search name, email, phone…"
          class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.status" @update:model-value="v => { f.status = v; apply() }" :options="statusOptions" trigger-class="text-sm py-2" class="w-36" />
      <SelectMenu :model-value="f.sort" @update:model-value="v => { f.sort = v; apply() }" :options="sortOptions" trigger-class="text-sm py-2" class="w-40" />
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="th">Customer</th>
              <th class="th">Joined</th>
              <th class="th text-center">Orders</th>
              <th class="th text-right">Lifetime spend</th>
              <th class="th">Last order</th>
              <th class="th">Status</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="c in customers.data" :key="c.id" class="hover:bg-purple-50/20">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <Avatar :name="c.name" :src="c.avatar" />
                  <div class="min-w-0">
                    <p class="font-semibold text-charcoal text-sm truncate">{{ c.name }}</p>
                    <p class="text-muted text-xs truncate">{{ c.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-muted text-xs">{{ c.created_at }}</td>
              <td class="px-4 py-3 text-center">
                <span class="badge badge-purple text-xs">{{ c.orders_count }}</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-charcoal text-sm">৳{{ Number(c.lifetime_spend).toLocaleString() }}</td>
              <td class="px-4 py-3 text-muted text-xs">{{ c.last_order_at || '—' }}</td>
              <td class="px-4 py-3"><span :class="statusClass(c.status)" class="badge text-xs">{{ statusLabel(c.status) }}</span></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1.5">
                  <button v-if="can('customers.view_orders')" @click="openOrders(c)"
                    class="inline-flex items-center gap-1 text-xs text-primary px-2.5 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <ClipboardDocumentListIcon class="w-3.5 h-3.5" /> View orders
                  </button>
                  <button v-if="can('customers.edit')" @click="openEdit(c)"
                    class="inline-flex items-center gap-1 text-xs text-charcoal px-2.5 py-1.5 border border-purple-100 rounded-lg hover:bg-purple-50 transition-colors">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button v-if="can('customers.edit')" @click="openReset(c)"
                    class="inline-flex items-center gap-1 text-xs text-charcoal px-2.5 py-1.5 border border-purple-100 rounded-lg hover:bg-purple-50 transition-colors">
                    <KeyIcon class="w-3.5 h-3.5" /> Reset
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!customers.data.length"><td colspan="7" class="px-5 py-12 text-center text-muted text-sm">No customers found.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="customers.links" />
    </div>

    <!-- ── View orders popup ── -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="ordersModal.open" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="ordersModal.open = false"></div>
          <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50 shrink-0">
              <div class="flex items-center gap-3 min-w-0">
                <Avatar :name="ordersModal.customer?.name" />
                <div class="min-w-0">
                  <p class="font-semibold text-charcoal truncate">{{ ordersModal.customer?.name }}</p>
                  <p class="text-muted text-xs truncate">{{ ordersModal.customer?.email }}</p>
                </div>
              </div>
              <button @click="ordersModal.open = false" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center text-muted hover:text-charcoal shrink-0"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <div class="px-6 py-3 border-b border-purple-50 flex gap-6 text-sm shrink-0">
              <div><span class="text-muted">Orders</span> <span class="font-semibold text-charcoal ml-1">{{ ordersModal.customer?.orders_count ?? '—' }}</span></div>
              <div><span class="text-muted">Lifetime spend</span> <span class="font-semibold text-charcoal ml-1">৳{{ Number(ordersModal.customer?.lifetime_spend || 0).toLocaleString() }}</span></div>
              <span v-if="ordersModal.customer" :class="statusClass(ordersModal.customer.status)" class="badge text-xs ml-auto">{{ statusLabel(ordersModal.customer.status) }}</span>
            </div>
            <div class="flex-1 overflow-y-auto">
              <div v-if="ordersModal.loading" class="p-6 space-y-3"><div v-for="i in 4" :key="i" class="h-12 bg-purple-50 rounded-xl animate-pulse"></div></div>
              <table v-else class="w-full">
                <thead class="bg-purple-50/40">
                  <tr><th class="th2">Order</th><th class="th2">Status</th><th class="th2">Payment</th><th class="th2 text-right">Total</th><th class="th2">Date</th><th class="th2"></th></tr>
                </thead>
                <tbody class="divide-y divide-purple-50">
                  <tr v-for="o in ordersModal.orders" :key="o.id" class="hover:bg-purple-50/20">
                    <td class="px-4 py-2.5 text-sm">
                      <span class="font-medium text-charcoal">{{ o.order_number }}</span>
                      <span v-if="o.source && o.source !== 'web'" class="badge badge-purple text-[10px] uppercase ml-1">{{ o.source }}</span>
                      <span class="text-muted text-xs block">{{ o.items_count }} item(s)</span>
                    </td>
                    <td class="px-4 py-2.5"><span :class="statusBadge(o.status)" class="badge text-xs">{{ o.status_label }}</span></td>
                    <td class="px-4 py-2.5"><span :class="paymentBadge(o.payment_status)" class="badge text-xs">{{ o.payment_status }}</span></td>
                    <td class="px-4 py-2.5 text-right font-semibold text-charcoal text-sm">৳{{ Number(o.total).toLocaleString() }}</td>
                    <td class="px-4 py-2.5 text-muted text-xs">{{ o.created_at }}</td>
                    <td class="px-4 py-2.5 text-right"><button @click="orderModal.open(o.id)" class="text-xs text-primary px-2.5 py-1 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">View</button></td>
                  </tr>
                  <tr v-if="!ordersModal.orders.length"><td colspan="6" class="px-4 py-12 text-center text-muted text-sm">No orders yet.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Edit customer popup ── -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="editModal.open" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="editModal.open = false"></div>
          <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50">
              <h3 class="font-display font-semibold text-charcoal text-lg">Edit Customer</h3>
              <button @click="editModal.open = false" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center text-muted hover:text-charcoal"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="saveEdit" class="p-6 space-y-4">
              <div>
                <label class="lbl">Full name *</label>
                <input v-model="editForm.name" type="text" class="input-field" required>
                <p v-if="editErrors.name" class="err">{{ editErrors.name }}</p>
              </div>
              <div>
                <label class="lbl">Email *</label>
                <input v-model="editForm.email" type="email" class="input-field" required>
                <p v-if="editErrors.email" class="err">{{ editErrors.email }}</p>
              </div>
              <div>
                <label class="lbl">Phone</label>
                <input v-model="editForm.phone" type="text" class="input-field" placeholder="Optional">
              </div>
              <div>
                <label class="lbl">Account status</label>
                <SelectMenu v-model="editForm.status" :options="editStatusOptions" />
                <p class="text-xs text-muted mt-1">Set to <strong>Blocked</strong> (or Inactive) to stop this customer from logging in or ordering.</p>
              </div>
              <div class="flex gap-3 pt-2">
                <button type="button" @click="editModal.open = false" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" :disabled="editSaving" class="btn-primary flex-1">{{ editSaving ? 'Saving…' : 'Save changes' }}</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Reset password popup ── -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="resetModal.open" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="resetModal.open = false"></div>
          <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50">
              <h3 class="font-display font-semibold text-charcoal text-lg">Reset password</h3>
              <button @click="resetModal.open = false" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center text-muted hover:text-charcoal"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <form @submit.prevent="submitReset" class="p-6 space-y-4">
              <p class="text-sm text-muted">Set a new password for <span class="font-semibold text-charcoal">{{ resetModal.name }}</span>.</p>
              <div>
                <input v-model="resetForm.password" type="text" class="input-field" placeholder="New password (min 8 characters)" required>
                <p v-if="resetErrors.password" class="err">{{ resetErrors.password }}</p>
              </div>
              <div class="flex gap-3 pt-1">
                <button type="button" @click="resetModal.open = false" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" :disabled="resetSaving" class="btn-primary flex-1">{{ resetSaving ? 'Resetting…' : 'Reset password' }}</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Shared full order popup (opened from the orders list) -->
    <OrderModal ref="orderModal" />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import OrderModal from '@/Components/admin/OrderModal.vue'
import { usePermissions } from '@/composables/usePermissions'
import { statusBadge, paymentBadge } from '@/composables/useOrderStatus'
import { MagnifyingGlassIcon, ClipboardDocumentListIcon, PencilSquareIcon, XMarkIcon, KeyIcon } from '@heroicons/vue/24/outline'

const statusOptions = [
  { value: '', label: 'All status' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Blocked' },
]
const sortOptions = [
  { value: 'latest', label: 'Newest' },
  { value: 'spend', label: 'Top spenders' },
  { value: 'orders', label: 'Most orders' },
]
const editStatusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Blocked' },
]

const props = defineProps({ customers: Object, filters: Object, totals: Object })
const { can } = usePermissions()
const toast = useToast()

const statusClass = (s) => ({ active: 'badge-success', inactive: 'badge-gray', suspended: 'badge-error' }[s] || 'badge-gray')
const statusLabel = (s) => ({ active: 'Active', inactive: 'Inactive', suspended: 'Blocked' }[s] || s)

const f = ref({ search: props.filters?.search || '', status: props.filters?.status || '', sort: props.filters?.sort || 'latest', range: props.filters?.range, from: props.filters?.from, to: props.filters?.to })
let t = null
const apply = () => router.get(route('admin.customers.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(t); t = setTimeout(apply, 350) }

// ── View orders popup ──
const orderModal = ref(null)
const ordersModal = reactive({ open: false, loading: false, customer: null, orders: [] })
const openOrders = async (c) => {
  ordersModal.open = true
  ordersModal.loading = true
  ordersModal.customer = { name: c.name, email: c.email, status: c.status, orders_count: c.orders_count, lifetime_spend: c.lifetime_spend }
  ordersModal.orders = []
  try {
    const { data } = await axios.get(route('admin.customers.orders-data', c.id))
    ordersModal.customer = data.customer
    ordersModal.orders = data.orders
  } catch {
    toast.error('Failed to load orders.')
  } finally {
    ordersModal.loading = false
  }
}

// ── Edit popup ──
const editModal = reactive({ open: false, id: null })
const editForm = reactive({ name: '', email: '', phone: '', status: 'active' })
const editErrors = ref({})
const editSaving = ref(false)
const openEdit = (c) => {
  editModal.open = true
  editModal.id = c.id
  editErrors.value = {}
  Object.assign(editForm, { name: c.name, email: c.email, phone: c.phone || '', status: c.status })
}
const saveEdit = () => {
  editSaving.value = true
  editErrors.value = {}
  router.patch(route('admin.customers.update', editModal.id), { ...editForm }, {
    preserveScroll: true,
    onSuccess: () => { editModal.open = false; toast.success('Customer updated.'); router.reload({ only: ['customers', 'totals'] }) },
    onError: (e) => { editErrors.value = e },
    onFinish: () => { editSaving.value = false },
  })
}

// ── Reset password popup ──
const resetModal = reactive({ open: false, id: null, name: '' })
const resetForm = reactive({ password: '' })
const resetErrors = ref({})
const resetSaving = ref(false)
const openReset = (c) => {
  resetModal.open = true
  resetModal.id = c.id
  resetModal.name = c.name
  resetForm.password = ''
  resetErrors.value = {}
}
const submitReset = () => {
  resetSaving.value = true
  resetErrors.value = {}
  router.patch(route('admin.customers.reset-password', resetModal.id), { password: resetForm.password }, {
    preserveScroll: true,
    onSuccess: () => { resetModal.open = false; toast.success('Password reset.') },
    onError: (e) => { resetErrors.value = e },
    onFinish: () => { resetSaving.value = false },
  })
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
.th2 { @apply text-left px-4 py-2.5 text-[11px] font-semibold text-muted uppercase tracking-wider; }
.lbl { @apply block text-sm font-medium text-charcoal mb-1; }
.err { @apply text-error text-xs mt-1; }
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .2s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
</style>
