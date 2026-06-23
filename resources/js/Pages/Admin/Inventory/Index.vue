<template>
  <AdminLayout>
    <Head title="Inventory" />
    <InventoryTabs />

    <!-- Summary cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <div v-for="c in cards" :key="c.label" class="bg-white rounded-2xl border border-purple-50 p-4">
        <div class="flex items-center justify-between">
          <span class="w-9 h-9 rounded-xl flex items-center justify-center" :class="c.bg"><component :is="c.icon" class="w-5 h-5" :class="c.color" /></span>
        </div>
        <p class="text-2xl font-bold text-charcoal mt-3">{{ Number(c.value).toLocaleString() }}</p>
        <p class="text-muted text-xs">{{ c.label }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search name or SKU…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.status" @update:model-value="v => { f.status = v; apply() }" :options="statusOptions" trigger-class="text-sm py-2" class="w-40" />
      <SelectMenu :model-value="f.sort" @update:model-value="v => { f.sort = v; apply() }" :options="sortOptions" trigger-class="text-sm py-2" class="w-40" />
      <a v-if="can('inventory.export')" :href="route('admin.inventory.export')" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5">
        <ArrowDownTrayIcon class="w-4 h-4" /> Export
      </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="th">Product</th>
              <th class="th">SKU</th>
              <th class="th">Category</th>
              <th class="th text-center">Stock</th>
              <th class="th">Status</th>
              <th class="th">Last updated</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="p in products.data" :key="p.id" class="hover:bg-purple-50/20" :class="p.status === 'out' ? 'bg-red-50/40' : p.status === 'low' ? 'bg-amber-50/40' : ''">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img :src="p.image || '/images/product-placeholder.svg'" class="w-10 h-10 rounded-lg object-cover bg-purple-50 shrink-0" alt="">
                  <span class="font-medium text-charcoal text-sm truncate max-w-[220px]">{{ p.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-muted text-xs font-mono">{{ p.sku || '—' }}</td>
              <td class="px-4 py-3 text-muted text-xs">{{ p.category || '—' }}</td>
              <td class="px-4 py-3 text-center font-bold text-charcoal">{{ p.stock }}</td>
              <td class="px-4 py-3"><span :class="statusClass(p.status)" class="badge text-xs">{{ statusLabel(p.status) }}</span></td>
              <td class="px-4 py-3 text-muted text-xs">{{ p.updated_at }}</td>
              <td class="px-4 py-3 text-right">
                <button v-if="can('inventory.update')" @click="openAdd(p)" class="text-xs text-primary px-3 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 inline-flex items-center gap-1">
                  <PlusIcon class="w-3.5 h-3.5" /> Add stock
                </button>
              </td>
            </tr>
            <tr v-if="!products.data.length"><td colspan="7" class="px-5 py-12 text-center text-muted text-sm">No products found.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="products.links" />
    </div>

    <!-- Add stock modal -->
    <Modal :open="showAdd" @update:open="showAdd = $event" title="Add stock" max-width="sm:max-w-md">
      <form v-if="target" @submit.prevent="submitAdd" class="p-5 space-y-4">
        <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-xl">
          <img :src="target.image || '/images/product-placeholder.svg'" class="w-12 h-12 rounded-lg object-cover" alt="">
          <div class="min-w-0">
            <p class="font-semibold text-charcoal text-sm truncate">{{ target.name }}</p>
            <p class="text-muted text-xs">Current stock: <span class="font-bold text-charcoal">{{ target.stock }}</span></p>
          </div>
        </div>
        <div>
          <label class="lbl">Add quantity <span class="text-error">*</span></label>
          <input v-model.number="addForm.added_quantity" type="number" min="1" class="input-field" required>
          <p v-if="addForm.errors.added_quantity" class="text-error text-xs mt-1">{{ addForm.errors.added_quantity }}</p>
        </div>
        <div>
          <label class="lbl">Note (optional)</label>
          <input v-model="addForm.note" type="text" class="input-field" placeholder="e.g. Restocked from supplier">
        </div>
        <div class="flex items-center justify-between p-3 rounded-xl bg-green-50 border border-green-100 text-sm">
          <span class="text-muted">New stock will be</span>
          <span class="font-bold text-success">{{ (Number(target.stock) || 0) + (Number(addForm.added_quantity) || 0) }}</span>
        </div>
        <div class="flex gap-2">
          <button type="button" @click="showAdd = false" class="btn-secondary flex-1 py-2.5 text-sm">Cancel</button>
          <button type="submit" :disabled="addForm.processing" class="btn-primary flex-1 py-2.5 text-sm">Add stock</button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import InventoryTabs from '@/Components/admin/InventoryTabs.vue'
import Modal from '@/Components/ui/Modal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { usePermissions } from '@/composables/usePermissions'

const statusOptions = [
  { value: '', label: 'All status' },
  { value: 'in', label: 'In stock' },
  { value: 'low', label: 'Low stock' },
  { value: 'out', label: 'Out of stock' },
]
const sortOptions = [
  { value: 'stock_asc', label: 'Stock: Low → High' },
  { value: 'stock_desc', label: 'Stock: High → Low' },
  { value: 'name', label: 'Name' },
]
import {
  MagnifyingGlassIcon, ArrowDownTrayIcon, PlusIcon,
  CubeIcon, ArchiveBoxIcon, CheckCircleIcon, ExclamationTriangleIcon, XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ products: Object, summary: Object, filters: Object, lowThreshold: Number })
const { can } = usePermissions()

const cards = computed(() => [
  { label: 'Total Products', value: props.summary.total_products, icon: ArchiveBoxIcon, bg: 'bg-purple-50', color: 'text-primary' },
  { label: 'Total Stock Units', value: props.summary.total_units, icon: CubeIcon, bg: 'bg-blue-50', color: 'text-blue-500' },
  { label: 'In Stock', value: props.summary.in_stock, icon: CheckCircleIcon, bg: 'bg-green-50', color: 'text-success' },
  { label: 'Low Stock', value: props.summary.low_stock, icon: ExclamationTriangleIcon, bg: 'bg-amber-50', color: 'text-amber-500' },
  { label: 'Out of Stock', value: props.summary.out_of_stock, icon: XCircleIcon, bg: 'bg-red-50', color: 'text-error' },
])

const statusClass = (s) => ({ in: 'badge-success', low: 'badge-warning', out: 'badge-error' }[s])
const statusLabel = (s) => ({ in: 'In Stock', low: 'Low Stock', out: 'Out of Stock' }[s])

const f = ref({ search: props.filters?.search || '', status: props.filters?.status || '', sort: props.filters?.sort || 'stock_asc' })
let t = null
const apply = () => router.get(route('admin.inventory.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(t); t = setTimeout(apply, 350) }

const showAdd = ref(false)
const target = ref(null)
const addForm = useForm({ added_quantity: 1, note: '' })
const openAdd = (p) => { target.value = p; addForm.reset(); addForm.clearErrors(); addForm.added_quantity = 1; showAdd.value = true }
const submitAdd = () => addForm.patch(route('admin.inventory.stock', target.value.id), { preserveScroll: true, onSuccess: () => { showAdd.value = false } })
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
</style>
