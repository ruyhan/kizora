<template>
  <AdminLayout>
    <Head title="Coupons" />

    <div class="space-y-6 w-full">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Coupons</h1>
          <p class="text-muted text-sm mt-1">Manage discount coupons</p>
        </div>
        <button @click="openCreate" class="btn-primary flex items-center gap-2">
          <PlusIcon class="w-4 h-4" />
          New Coupon
        </button>
      </div>

      <!-- Search + date filter -->
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[200px]">
          <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search coupon code…" class="input-field text-sm pl-9 py-2 w-full">
        </div>
        <DateRangeFilter route-name="admin.coupons.index" :filters="filters" :params="f" />
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-purple-50 border-b border-purple-100">
            <tr>
              <th v-if="canDelete" class="px-6 py-4 w-10"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Code</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Type / Value</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Min. Order</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Usage</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Expires</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Status</th>
              <th class="text-right px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-purple-50/30 transition-colors" :class="selected.includes(coupon.id) ? 'bg-purple-50/40' : ''">
              <td v-if="canDelete" class="px-6 py-4"><input type="checkbox" :value="coupon.id" v-model="selected" class="kz-check"></td>
              <td class="px-6 py-4">
                <span class="font-mono font-bold text-primary bg-purple-50 px-3 py-1 rounded-lg text-sm">{{ coupon.code }}</span>
              </td>
              <td class="px-6 py-4 text-charcoal font-medium">
                {{ coupon.type === 'percentage' ? coupon.value + '%' : '৳' + coupon.value }} off
              </td>
              <td class="px-6 py-4 text-muted">৳{{ coupon.minimum_order || 0 }}</td>
              <td class="px-6 py-4 text-muted">
                {{ coupon.used_count }} / {{ coupon.usage_limit || '∞' }}
              </td>
              <td class="px-6 py-4 text-muted">{{ coupon.expires_at || 'No expiry' }}</td>
              <td class="px-6 py-4">
                <span :class="coupon.is_active ? 'badge-success' : 'badge-error'" class="badge text-xs">
                  {{ coupon.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openEdit(coupon)" class="p-2 text-muted hover:text-primary hover:bg-purple-50 rounded-lg transition-colors">
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteCoupon(coupon)" class="p-2 text-muted hover:text-error hover:bg-red-50 rounded-lg transition-colors">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!coupons.length" class="py-16 text-center">
          <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
            <TicketIcon class="w-7 h-7 text-primary/40" />
          </div>
          <p class="text-muted">No coupons yet.</p>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
          <div class="p-6 border-b border-purple-100 flex items-center justify-between">
            <h3 class="font-display font-semibold text-charcoal text-lg">{{ editing ? 'Edit' : 'New' }} Coupon</h3>
            <button @click="closeModal" class="p-2 text-muted hover:text-charcoal rounded-lg hover:bg-gray-100">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <form @submit.prevent="save" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Coupon Code *</label>
                <input v-model="form.code" type="text" class="input-field uppercase font-mono font-bold" required placeholder="KIZORA10">
              </div>
              <div>
                <label class="block text-sm font-medium text-charcoal mb-1">Type *</label>
                <SelectMenu v-model="form.type" :options="typeOptions" />
              </div>
              <div>
                <label class="block text-sm font-medium text-charcoal mb-1">Value *</label>
                <input v-model.number="form.value" type="number" class="input-field" required min="0" step="0.01"
                  :placeholder="form.type === 'percentage' ? 'e.g. 10' : 'e.g. 50'">
              </div>
              <div>
                <label class="block text-sm font-medium text-charcoal mb-1">Minimum Order (৳)</label>
                <input v-model.number="form.minimum_order" type="number" class="input-field" min="0" step="0.01" placeholder="0">
              </div>
              <div>
                <label class="block text-sm font-medium text-charcoal mb-1">Usage Limit</label>
                <input v-model.number="form.usage_limit" type="number" class="input-field" min="1" placeholder="Unlimited">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Expiry Date</label>
                <input v-model="form.expires_at" type="date" class="input-field">
              </div>
              <div class="col-span-2 flex items-center gap-3">
                <input v-model="form.is_active" type="checkbox" id="coupon_active" class="w-4 h-4 accent-primary">
                <label for="coupon_active" class="text-sm font-medium text-charcoal">Active</label>
              </div>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary flex-1">
                {{ saving ? 'Saving...' : 'Save Coupon' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <BulkDeleteBar :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected = []" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { usePermissions } from '@/composables/usePermissions'
import { PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon, TicketIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const typeOptions = [
  { value: 'percentage', label: 'Percentage (%)' },
  { value: 'fixed', label: 'Fixed Amount (Tk)' },
]

const props = defineProps({ coupons: Array, filters: { type: Object, default: () => ({}) } })

const f = ref({ search: props.filters?.search || '', range: props.filters?.range, from: props.filters?.from, to: props.filters?.to })
let _t
const apply = () => router.get(route('admin.coupons.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(_t); _t = setTimeout(apply, 350) }

const toast = useToast()
const { can } = usePermissions()
const canDelete = can('marketing.delete')
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.coupons.length > 0 && selected.value.length === props.coupons.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.coupons.map((c) => c.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.coupons.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(`${selected.value.length} coupon(s) deleted.`); selected.value = [] },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

const modalOpen = ref(false)
const editing = ref(null)
const saving = ref(false)
const form = ref({ code: '', type: 'percentage', value: '', minimum_order: '', usage_limit: '', expires_at: '', is_active: true })

const openCreate = () => {
  editing.value = null
  form.value = { code: '', type: 'percentage', value: '', minimum_order: '', usage_limit: '', expires_at: '', is_active: true }
  modalOpen.value = true
}

const openEdit = (coupon) => {
  editing.value = coupon
  form.value = {
    code: coupon.code, type: coupon.type, value: coupon.value,
    minimum_order: coupon.minimum_order, usage_limit: coupon.usage_limit,
    expires_at: coupon.expires_at, is_active: coupon.is_active
  }
  modalOpen.value = true
}

const closeModal = () => { modalOpen.value = false }

const save = () => {
  saving.value = true
  if (editing.value) {
    router.patch(route('admin.coupons.update', editing.value.id), form.value, {
      preserveScroll: true,
      onFinish: () => { saving.value = false; closeModal() }
    })
  } else {
    router.post(route('admin.coupons.store'), form.value, {
      preserveScroll: true,
      onFinish: () => { saving.value = false; closeModal() }
    })
  }
}

const deleteCoupon = (coupon) => {
  if (!confirm(`Delete coupon "${coupon.code}"?`)) return
  router.delete(route('admin.coupons.destroy', coupon.id))
}
</script>
