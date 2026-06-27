<template>
  <AdminLayout>
    <Head title="Email · Logs" />

    <div class="space-y-6 w-full max-w-6xl">
      <SettingsTabs />
      <EmailTabs />

      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Email Logs</h1>
          <p class="text-muted text-sm mt-1">Every email the store has queued, sent or failed to send.</p>
        </div>
        <button v-if="logs.total" type="button" @click="clearLogs"
          class="btn-secondary text-sm px-4 shrink-0 inline-flex items-center gap-1.5 text-error border-error/30 hover:bg-error/5">
          <TrashIcon class="w-4 h-4" /> Clear all logs
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-4 grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input v-model="f.search" type="text" class="input-field" placeholder="Search recipient…" @keyup.enter="apply">
        <SelectMenu v-model="f.status" :options="statusOptions" placeholder="All statuses" />
        <SelectMenu v-model="f.type" :options="typeOptions" placeholder="All types" />
        <input v-model="f.from" type="date" class="input-field" title="From date">
        <input v-model="f.to" type="date" class="input-field" title="To date">
        <div class="sm:col-span-2 lg:col-span-5 flex gap-2 justify-end">
          <button type="button" @click="clear" class="btn-secondary text-sm px-4">Clear</button>
          <button type="button" @click="apply" class="btn-primary text-sm px-5">Apply filters</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-purple-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[760px]">
            <thead>
              <tr class="text-left text-xs text-muted uppercase tracking-wide border-b border-purple-100 bg-purple-50/40">
                <th class="px-4 py-3 font-semibold">Type</th>
                <th class="px-4 py-3 font-semibold">Recipient</th>
                <th class="px-4 py-3 font-semibold">Subject</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Created</th>
                <th class="px-4 py-3 font-semibold text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-purple-50">
              <tr v-for="l in logs.data" :key="l.id" class="hover:bg-purple-50/30">
                <td class="px-4 py-3 whitespace-nowrap text-charcoal font-medium">{{ prettyType(l.template_key) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-muted">{{ l.recipient }}</td>
                <td class="px-4 py-3 max-w-[260px] truncate text-charcoal">{{ l.subject }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="badgeClass(l.status)" class="badge text-[11px] capitalize">{{ l.status }}</span>
                  <span v-if="l.status === 'failed' && l.error" class="block text-[11px] text-error/80 truncate max-w-[220px]" :title="l.error">{{ l.error }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-muted text-xs">{{ l.created_at }}</td>
                <td class="px-4 py-3 text-right">
                  <button type="button" @click="resend(l)" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                    <ArrowPathIcon class="w-3.5 h-3.5" /> Resend
                  </button>
                </td>
              </tr>
              <tr v-if="!logs.data.length">
                <td colspan="6" class="px-4 py-12 text-center text-muted">No emails logged yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="logs.links && logs.links.length > 3" class="flex flex-wrap gap-1 justify-center">
        <component :is="link.url ? 'button' : 'span'" v-for="(link, i) in logs.links" :key="i"
          @click="link.url && go(link.url)"
          :class="[link.active ? 'bg-ink text-white' : 'text-muted hover:text-ink', !link.url && 'opacity-40 cursor-default']"
          class="px-3 py-1.5 rounded-lg text-sm font-medium" v-html="link.label" />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import EmailTabs from '@/Components/admin/EmailTabs.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ logs: Object, filters: Object, types: Array })

const f = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  type: props.filters.type || '',
  from: props.filters.from || '',
  to: props.filters.to || '',
})

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'queued', label: 'Queued' },
  { value: 'sent', label: 'Sent' },
  { value: 'failed', label: 'Failed' },
]
const typeOptions = [{ value: '', label: 'All types' }, ...props.types.map(t => ({ value: t, label: prettyType(t) }))]

function prettyType (k) {
  if (!k) return '—'
  return String(k).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
const badgeClass = (s) => ({ sent: 'badge-success', failed: 'badge-error', queued: 'badge-gray' }[s] || 'badge-gray')

const apply = () => router.get(route('admin.email.logs'), cleaned(), { preserveState: true, preserveScroll: true, replace: true })
const clear = () => { Object.keys(f).forEach(k => f[k] = ''); apply() }
const cleaned = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v))
const go = (url) => router.get(url, {}, { preserveState: true, preserveScroll: true })
const resend = (l) => router.post(route('admin.email.logs.resend', l.id), {}, { preserveScroll: true })
const clearLogs = () => {
  if (confirm('Delete ALL email logs? This cannot be undone.')) {
    router.delete(route('admin.email.logs.clear'), { preserveScroll: true })
  }
}
</script>
