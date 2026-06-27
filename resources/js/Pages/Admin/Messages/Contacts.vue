<template>
  <AdminLayout>
    <Head title="Leads & Messages" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Leads &amp; Messages</h1>
        <p class="text-muted text-sm">Manage customer inquiries from the contact form through your lead pipeline.</p>
      </div>
      <DateRangeFilter route-name="admin.messages.contacts" :filters="filters" :params="{ status: statusFilter, search: search }" />
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
      <div v-for="s in statCards" :key="s.key" class="bg-white rounded-2xl border border-purple-50 p-4">
        <p class="text-2xl font-bold text-charcoal">{{ s.value }}</p>
        <p class="text-muted text-xs mt-0.5 flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full" :style="s.color ? { background: s.color } : {}" :class="!s.color ? 'bg-ink' : ''"></span>{{ s.label }}
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
        <button v-for="t in tabs" :key="t.value" type="button" @click="selectStatus(t.value)"
          :class="statusFilter === t.value ? 'bg-ink text-white shadow-sm' : 'bg-white text-charcoal border border-purple-100 hover:border-purple-200'"
          class="inline-flex items-center gap-2 px-3.5 h-9 rounded-full text-sm font-semibold whitespace-nowrap transition-all shrink-0">
          <span v-if="t.color && statusFilter !== t.value" class="w-2 h-2 rounded-full" :style="{ background: t.color }"></span>
          {{ t.label }}
          <span :class="statusFilter === t.value ? 'bg-white/25 text-white' : 'bg-purple-50 text-muted'" class="text-xs font-bold px-1.5 min-w-[20px] h-5 rounded-full inline-flex items-center justify-center">{{ t.count }}</span>
        </button>
      </div>
      <div class="relative ml-auto">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="search" @input="onSearch" type="text" placeholder="Search leads…" class="input-field text-sm pl-9 py-2 w-56">
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th v-if="canDelete" class="th w-10"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="th">Lead</th>
              <th class="th">Contact</th>
              <th class="th">Message</th>
              <th class="th">Status</th>
              <th class="th">Received</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="m in messages.data" :key="m.id" class="hover:bg-purple-50/20 transition-colors" :class="selected.includes(m.id) ? 'bg-purple-50/40' : (!m.is_read ? 'bg-amber-50/30' : '')">
              <td v-if="canDelete" class="px-4 py-3.5"><input type="checkbox" :value="m.id" v-model="selected" class="kz-check"></td>
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full gradient-purple flex items-center justify-center text-white font-bold text-sm shrink-0">{{ (m.name || '?')[0].toUpperCase() }}</div>
                  <div class="min-w-0">
                    <p class="font-semibold text-charcoal text-sm truncate flex items-center gap-1.5">{{ m.name }}<span v-if="!m.is_read" class="w-1.5 h-1.5 rounded-full bg-rose-500" title="Unread"></span></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5 text-sm">
                <a :href="'mailto:' + m.email" class="text-primary hover:underline block truncate max-w-[180px]">{{ m.email }}</a>
                <span v-if="m.phone" class="text-muted text-xs">{{ m.phone }}</span>
              </td>
              <td class="px-4 py-3.5 max-w-[190px]">
                <p v-if="m.subject" class="font-medium text-charcoal text-sm truncate">{{ m.subject }}</p>
                <p class="text-muted text-xs truncate">{{ m.message }}</p>
              </td>
              <td class="px-4 py-3.5">
                <select :value="m.status" @change="changeStatus(m, $event.target.value)"
                  :class="statusPillClass(m.status)" class="text-xs font-semibold rounded-full px-3 py-1.5 border-0 cursor-pointer focus:ring-2 focus:ring-primary/30 appearance-none">
                  <option v-for="s in statuses" :key="s" :value="s">{{ label(s) }}</option>
                </select>
              </td>
              <td class="px-4 py-3.5 text-sm text-muted whitespace-nowrap" :title="m.created_at">{{ m.created_ago }}</td>
              <td class="px-4 py-3.5">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="openDetail(m)" class="text-xs text-primary px-2.5 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">View</button>
                  <a :href="'mailto:' + m.email" class="text-xs px-2.5 py-1.5 border border-purple-100 text-charcoal rounded-lg hover:bg-purple-50 transition-colors">Reply</a>
                  <button @click="askDelete(m)" class="w-8 h-8 rounded-lg text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-colors" title="Delete"><TrashIcon class="w-4 h-4" /></button>
                </div>
              </td>
            </tr>
            <tr v-if="!messages.data?.length"><td :colspan="canDelete ? 7 : 6" class="px-5 py-16 text-center text-muted text-sm">No leads found.</td></tr>
          </tbody>
        </table>
      </div>

      <div v-if="messages.last_page > 1" class="flex justify-center gap-2 p-5 border-t border-purple-50">
        <template v-for="link in messages.links" :key="link.label">
          <Link v-if="link.url" :href="link.url" :class="link.active ? 'bg-primary text-white' : 'bg-white text-charcoal hover:bg-purple-50'"
            class="px-4 py-2 rounded-xl text-sm font-medium border border-purple-100 transition-colors" v-html="link.label" />
        </template>
      </div>
    </div>

    <BulkDeleteBar :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected = []" />

    <!-- Detail modal -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="detail" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="detail = null"></div>
          <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full gradient-purple flex items-center justify-center text-white font-bold shrink-0">{{ (detail.name || '?')[0].toUpperCase() }}</div>
                <div>
                  <p class="font-semibold text-charcoal">{{ detail.name }}</p>
                  <p class="text-muted text-xs">{{ detail.created_at }}</p>
                </div>
              </div>
              <button @click="detail = null" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center text-muted hover:text-charcoal"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <div class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div><p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Email</p><a :href="'mailto:' + detail.email" class="text-primary text-sm hover:underline break-all">{{ detail.email }}</a></div>
                <div><p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Phone</p><p class="text-sm text-charcoal">{{ detail.phone || '—' }}</p></div>
              </div>
              <div>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Lead Status</p>
                <select :value="detail.status" @change="changeStatus(detail, $event.target.value)" :class="statusPillClass(detail.status)" class="text-xs font-semibold rounded-full px-3 py-1.5 border-0 cursor-pointer appearance-none">
                  <option v-for="s in statuses" :key="s" :value="s">{{ label(s) }}</option>
                </select>
              </div>
              <div v-if="detail.subject"><p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Subject</p><p class="text-sm font-medium text-charcoal">{{ detail.subject }}</p></div>
              <div><p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Message</p><p class="text-sm text-charcoal leading-relaxed whitespace-pre-line bg-purple-50/40 rounded-xl p-4">{{ detail.message }}</p></div>
            </div>
            <div class="flex items-center gap-2 px-6 py-4 border-t border-purple-50">
              <a :href="'mailto:' + detail.email" class="btn-primary text-sm px-4 py-2">Reply by Email</a>
              <button @click="askDelete(detail); detail = null" class="text-sm px-4 py-2 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors ml-auto">Delete Lead</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete confirm -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="pendingDelete" class="fixed inset-0 z-[95] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="pendingDelete = null"></div>
          <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-3"><TrashIcon class="w-6 h-6 text-rose-500" /></div>
            <p class="font-semibold text-charcoal">Delete this lead?</p>
            <p class="text-muted text-sm mt-1">{{ pendingDelete.name }}'s message will be permanently removed.</p>
            <div class="flex gap-2 mt-5">
              <button @click="pendingDelete = null" class="btn-secondary flex-1 py-2 text-sm">Cancel</button>
              <button @click="confirmDelete" class="flex-1 py-2 text-sm rounded-xl bg-rose-600 text-white font-semibold hover:bg-rose-700 transition-colors">Delete</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { usePermissions } from '@/composables/usePermissions'
import { MagnifyingGlassIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  messages: Object,
  filters: { type: Object, default: () => ({}) },
  counts: { type: Object, default: () => ({}) },
  statuses: { type: Array, default: () => [] },
})

const toast = useToast()
const { can } = usePermissions()
const canDelete = can('customers.delete')
const statusFilter = ref(props.filters?.status || '')
const search = ref(props.filters?.search || '')
const detail = ref(null)
const pendingDelete = ref(null)
let searchTimer = null

// Bulk selection / delete
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.messages.data?.length > 0 && selected.value.length === props.messages.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.messages.data.map((m) => m.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.messages.contacts.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(`${selected.value.length} lead(s) deleted.`); selected.value = [] },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

const statusMeta = {
  new:       { label: 'New',       color: '#F59E0B', pill: 'bg-amber-100 text-amber-700' },
  contacted: { label: 'Contacted', color: '#3B82F6', pill: 'bg-blue-100 text-blue-700' },
  qualified: { label: 'Qualified', color: '#6366F1', pill: 'bg-indigo-100 text-indigo-700' },
  converted: { label: 'Converted', color: '#10B981', pill: 'bg-emerald-100 text-emerald-700' },
  lost:      { label: 'Lost',      color: '#9CA3AF', pill: 'bg-gray-100 text-gray-600' },
}
const label = (s) => statusMeta[s]?.label || s
const statusPillClass = (s) => statusMeta[s]?.pill || 'bg-purple-50 text-muted'

const statCards = [
  { key: 'all', label: 'Total Leads', value: props.counts.all ?? 0, color: null },
  { key: 'new', label: 'New', value: props.counts.new ?? 0, color: statusMeta.new.color },
  { key: 'contacted', label: 'Contacted', value: props.counts.contacted ?? 0, color: statusMeta.contacted.color },
  { key: 'qualified', label: 'Qualified', value: props.counts.qualified ?? 0, color: statusMeta.qualified.color },
  { key: 'converted', label: 'Converted', value: props.counts.converted ?? 0, color: statusMeta.converted.color },
]

const tabs = [
  { value: '', label: 'All', count: props.counts.all ?? 0, color: null },
  ...props.statuses.map((s) => ({ value: s, label: statusMeta[s]?.label || s, count: props.counts[s] ?? 0, color: statusMeta[s]?.color })),
]

const dateParams = () => props.filters?.range === 'custom'
  ? { from: props.filters.from, to: props.filters.to }
  : { range: props.filters?.range }
const reload = () => {
  router.get(route('admin.messages.contacts'),
    { status: statusFilter.value || undefined, search: search.value || undefined, ...dateParams() },
    { preserveState: true, preserveScroll: true, replace: true })
}
const selectStatus = (v) => { statusFilter.value = v; reload() }
const onSearch = () => { clearTimeout(searchTimer); searchTimer = setTimeout(reload, 350) }

const openDetail = (m) => {
  detail.value = m
  if (!m.is_read) router.patch(route('admin.messages.contacts.read', m.id), {}, { preserveScroll: true, preserveState: true, only: ['messages', 'counts'] })
}

const changeStatus = (m, status) => {
  router.patch(route('admin.messages.contacts.status', m.id), { status }, {
    preserveScroll: true, preserveState: true,
    onSuccess: () => { toast.success('Lead status updated.'); if (detail.value) detail.value.status = status },
    onError: () => toast.error('Could not update status.'),
  })
}

const askDelete = (m) => { pendingDelete.value = m }
const confirmDelete = () => {
  const id = pendingDelete.value.id
  router.delete(route('admin.messages.contacts.destroy', id), {
    preserveScroll: true,
    onSuccess: () => toast.success('Lead deleted.'),
    onError: () => toast.error('Could not delete lead.'),
    onFinish: () => { pendingDelete.value = null },
  })
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3.5 text-xs font-semibold text-muted uppercase tracking-wider; }
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .2s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
</style>
