<template>
  <AdminLayout>
    <Head title="Audit Logs" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Audit Logs</h1>
        <p class="text-muted text-sm">Every important action, who did it and when.</p>
      </div>
      <button v-if="total > 0" @click="clearLogs" class="btn-secondary text-error text-sm px-4 py-2 inline-flex items-center gap-1.5">
        <TrashIcon class="w-4 h-4" /> Clear logs
      </button>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search description or user…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <select v-model="f.module" @change="apply" class="input-field text-sm py-2 w-40">
        <option value="">All modules</option>
        <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
      </select>
      <select v-model="f.action" @change="apply" class="input-field text-sm py-2 w-40">
        <option value="">All actions</option>
        <option v-for="a in actions" :key="a" :value="a" class="capitalize">{{ a }}</option>
      </select>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="th">User</th>
              <th class="th">Action</th>
              <th class="th">Module</th>
              <th class="th">Description</th>
              <th class="th">IP</th>
              <th class="th text-right">When</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="l in logs.data" :key="l.id" class="hover:bg-purple-50/20">
              <td class="px-4 py-3 font-medium text-charcoal text-sm">{{ l.user_name }}</td>
              <td class="px-4 py-3"><span :class="actionClass(l.action)" class="badge text-xs capitalize">{{ l.action }}</span></td>
              <td class="px-4 py-3 text-charcoal text-xs">{{ l.module }}</td>
              <td class="px-4 py-3 text-muted text-xs max-w-[320px] truncate">{{ l.description }}</td>
              <td class="px-4 py-3 text-muted text-xs font-mono">{{ l.ip_address || '—' }}</td>
              <td class="px-4 py-3 text-right text-muted text-xs">{{ l.created_at }}</td>
            </tr>
            <tr v-if="!logs.data.length"><td colspan="6" class="px-5 py-12 text-center text-muted text-sm">No activity yet.</td></tr>
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
import Pagination from '@/Components/ui/Pagination.vue'
import { MagnifyingGlassIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ logs: Object, modules: Array, actions: Array, filters: Object, total: { type: Number, default: 0 } })

const clearLogs = () => {
  if (confirm('Clear all audit logs? This cannot be undone.')) {
    router.delete(route('admin.audit-logs.clear'), { preserveScroll: true })
  }
}

const actionClass = (a) => {
  if (['created', 'login', 'invited'].includes(a)) return 'badge-success'
  if (['deleted', 'denied'].includes(a)) return 'badge-error'
  if (['updated', 'status', 'reset_password'].includes(a)) return 'badge-blue'
  return 'badge-gray'
}

const f = ref({ search: props.filters?.search || '', module: props.filters?.module || '', action: props.filters?.action || '' })
let t = null
const apply = () => router.get(route('admin.audit-logs.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(t); t = setTimeout(apply, 350) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
