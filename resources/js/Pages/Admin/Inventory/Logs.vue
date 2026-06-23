<template>
  <AdminLayout>
    <Head title="Inventory Logs" />
    <InventoryTabs />

    <div class="relative max-w-md mb-4">
      <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
      <input v-model="search" @input="applyDebounced" type="text" placeholder="Search product or SKU…" class="input-field text-sm pl-9 py-2 w-full">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="th">Product</th>
              <th class="th text-center">Previous</th>
              <th class="th text-center">Added</th>
              <th class="th text-center">New</th>
              <th class="th">Updated by</th>
              <th class="th">Note</th>
              <th class="th text-right">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="l in logs.data" :key="l.id" class="hover:bg-purple-50/20">
              <td class="px-4 py-3">
                <p class="font-medium text-charcoal text-sm">{{ l.product }}</p>
                <p class="text-muted text-xs font-mono">{{ l.sku }}</p>
              </td>
              <td class="px-4 py-3 text-center text-muted">{{ l.previous_stock }}</td>
              <td class="px-4 py-3 text-center"><span class="badge badge-success text-xs">+{{ l.added_quantity }}</span></td>
              <td class="px-4 py-3 text-center font-bold text-charcoal">{{ l.new_stock }}</td>
              <td class="px-4 py-3 text-sm text-charcoal">{{ l.user }}</td>
              <td class="px-4 py-3 text-muted text-xs max-w-[200px] truncate">{{ l.note || '—' }}</td>
              <td class="px-4 py-3 text-right text-muted text-xs">{{ l.created_at }}</td>
            </tr>
            <tr v-if="!logs.data.length"><td colspan="7" class="px-5 py-12 text-center text-muted text-sm">No stock additions yet.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="logs.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import InventoryTabs from '@/Components/admin/InventoryTabs.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ logs: Object, filters: Object })
const search = ref(props.filters?.search || '')
let t = null
const applyDebounced = () => { clearTimeout(t); t = setTimeout(() => router.get(route('admin.inventory.logs'), { search: search.value }, { preserveState: true, preserveScroll: true, replace: true }), 350) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
