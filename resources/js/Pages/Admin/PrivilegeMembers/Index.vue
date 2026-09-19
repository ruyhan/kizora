<template>
  <AdminLayout>
    <Head title="Privilege Members" />

    <PrivilegeTabs />

    <!-- Status filter + search -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="flex flex-wrap gap-1.5">
        <button v-for="s in statusTabs" :key="s.value" @click="setStatus(s.value)"
          :class="f.status === s.value ? 'bg-ink text-white border-ink' : 'bg-white text-charcoal border-purple-100 hover:bg-purple-50'"
          class="px-3.5 py-2 rounded-xl text-sm font-medium border transition-colors inline-flex items-center gap-2">
          {{ s.label }}
          <span :class="f.status === s.value ? 'bg-white/20' : 'bg-purple-50 text-muted'" class="px-1.5 rounded-md text-xs font-semibold">{{ counts[s.value] ?? 0 }}</span>
        </button>
      </div>
      <div class="relative flex-1 min-w-[220px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search name, email, phone, card no…"
          class="input-field text-sm pl-9 py-2 w-full">
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="th">Customer</th>
              <th class="th">Phone</th>
              <th class="th">Applied</th>
              <th class="th">Card No.</th>
              <th class="th">Status</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="m in members.data" :key="m.id" class="hover:bg-purple-50/20 align-top">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <Avatar :name="m.name" :src="m.avatar" />
                  <div class="min-w-0">
                    <p class="font-semibold text-charcoal text-sm truncate">{{ m.name }}</p>
                    <p class="text-muted text-xs truncate">{{ m.email }}</p>
                    <p v-if="m.note" class="text-xs text-charcoal/70 mt-1 max-w-xs italic line-clamp-2" :title="m.note">“{{ m.note }}”</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-charcoal whitespace-nowrap">{{ m.phone || '—' }}</td>
              <td class="px-4 py-3 text-xs text-muted whitespace-nowrap">{{ m.applied_at || '—' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span v-if="m.card_number" class="font-mono font-semibold text-sm text-[#9a7b4f] bg-[#f7f1e8] px-2.5 py-1 rounded-lg">{{ m.card_number }}</span>
                <span v-else class="text-muted text-xs">Assigned on approval</span>
              </td>
              <td class="px-4 py-3">
                <span :class="badgeClass(m.status)" class="badge text-xs capitalize">{{ m.status }}</span>
                <p v-if="m.status === 'approved' && m.approved_at" class="text-[11px] text-muted mt-1">since {{ m.approved_at }}</p>
                <p v-if="m.reviewer && m.status !== 'pending'" class="text-[11px] text-muted">by {{ m.reviewer }}</p>
                <p v-if="m.admin_note && ['rejected', 'revoked'].includes(m.status)" class="text-[11px] text-rose-600 mt-1 max-w-[180px] line-clamp-2" :title="m.admin_note">{{ m.admin_note }}</p>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                  <template v-if="canManage">
                    <button v-if="['pending', 'rejected', 'revoked'].includes(m.status)" @click="approve(m)" :disabled="busy === m.id"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors disabled:opacity-50">
                      <CheckIcon class="w-3.5 h-3.5" /> {{ m.status === 'pending' ? 'Approve' : 'Re-approve' }}
                    </button>
                    <button v-if="m.status === 'pending'" @click="openReason(m, 'reject')"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors">
                      <XMarkIcon class="w-3.5 h-3.5" /> Reject
                    </button>
                  </template>
                  <button v-if="m.status === 'approved'" @click="preview = m" title="View card"
                    class="p-2 text-muted hover:text-ink hover:bg-purple-50 rounded-lg transition-colors">
                    <CreditCardIcon class="w-4 h-4" />
                  </button>
                  <button v-if="canManage && m.status === 'approved'" @click="openReason(m, 'revoke')" title="Revoke membership"
                    class="p-2 text-muted hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                    <NoSymbolIcon class="w-4 h-4" />
                  </button>
                  <button v-if="canManage" @click="destroy(m)" title="Delete record"
                    class="p-2 text-muted hover:text-error hover:bg-red-50 rounded-lg transition-colors">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!members.data.length" class="py-16 text-center">
        <div class="w-14 h-14 rounded-full bg-[#f7f1e8] flex items-center justify-center mx-auto mb-3">
          <CreditCardIcon class="w-7 h-7 text-[#b8955f]" />
        </div>
        <p class="text-muted">{{ f.status === 'pending' ? 'No pending requests — all caught up.' : 'No records found.' }}</p>
      </div>
      <Pagination :links="members.links" />
    </div>

    <!-- Reject / revoke reason -->
    <Modal :open="!!reasonFor" @update:open="reasonFor = null" max-width="sm:max-w-md"
      :title="reasonMode === 'reject' ? 'Reject request' : 'Revoke membership'">
      <form @submit.prevent="submitReason" class="p-5 space-y-4">
        <p class="text-sm text-muted">
          <template v-if="reasonMode === 'reject'">Reject <strong class="text-charcoal">{{ reasonFor?.name }}</strong>'s application? They can apply again later.</template>
          <template v-else>Revoke <strong class="text-charcoal">{{ reasonFor?.name }}</strong>'s membership? Card <span class="font-mono">{{ reasonFor?.card_number }}</span> stays reserved if you re-approve later.</template>
        </p>
        <div>
          <label class="block text-sm font-medium text-charcoal mb-1">Reason <span class="text-muted font-normal">(shown to the customer, optional)</span></label>
          <textarea v-model="reason" rows="3" maxlength="500" class="input-field text-sm" placeholder="e.g. Please complete at least one order first."></textarea>
        </div>
        <div class="flex gap-3">
          <button type="button" @click="reasonFor = null" class="btn-secondary flex-1">Cancel</button>
          <button type="submit" :disabled="busy" class="btn-accent flex-1">{{ reasonMode === 'reject' ? 'Reject' : 'Revoke' }}</button>
        </div>
      </form>
    </Modal>

    <!-- Card preview -->
    <Modal :open="!!preview" @update:open="preview = null" max-width="sm:max-w-lg" :title="preview?.name">
      <div class="p-6 bg-[#0d0d0e]">
        <PrivilegeCard v-model:flipped="previewFlipped" :card-number="preview?.card_number" :holder-name="preview?.name"
          :tier-line1="card.tier_line_1" :tier-line2="card.tier_line_2" :terms-heading="card.terms_heading" :terms="card.terms" />
        <p class="text-center text-xs text-white/40 mt-4">Click the card to flip</p>
      </div>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PrivilegeTabs from '@/Components/admin/PrivilegeTabs.vue'
import PrivilegeCard from '@/Components/PrivilegeCard.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import Modal from '@/Components/ui/Modal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { usePermissions } from '@/composables/usePermissions'
import { MagnifyingGlassIcon, CheckIcon, XMarkIcon, TrashIcon, CreditCardIcon, NoSymbolIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  members: Object,
  filters: { type: Object, default: () => ({}) },
  counts: { type: Object, default: () => ({}) },
  card: { type: Object, default: () => ({}) },
})

const { can } = usePermissions()
const canManage = can('membership.manage')

const statusTabs = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Members' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'revoked', label: 'Revoked' },
  { value: 'all', label: 'All' },
]

const f = reactive({ status: props.filters.status || 'pending', search: props.filters.search || '' })
const apply = () => router.get(route('admin.privilege.index'), { ...f }, { preserveState: true, preserveScroll: true, replace: true })
let _t
const applyDebounced = () => { clearTimeout(_t); _t = setTimeout(apply, 350) }
const setStatus = (s) => { f.status = s; apply() }

const badgeClass = (s) => ({
  pending: 'badge-warning',
  approved: 'badge-success',
  rejected: 'badge-error',
  revoked: 'bg-gray-100 text-gray-600',
}[s])

const busy = ref(null)
const opts = { preserveScroll: true, onFinish: () => { busy.value = null } }

const approve = (m) => {
  if (!confirm(`Approve ${m.name} as a Privilege Member?${m.card_number ? '' : ' A card number will be issued automatically.'}`)) return
  busy.value = m.id
  router.patch(route('admin.privilege.approve', m.id), {}, opts)
}

const reasonFor = ref(null)
const reasonMode = ref('reject')
const reason = ref('')
const openReason = (m, mode) => { reasonFor.value = m; reasonMode.value = mode; reason.value = '' }
const submitReason = () => {
  const m = reasonFor.value
  busy.value = m.id
  router.patch(route(reasonMode.value === 'reject' ? 'admin.privilege.reject' : 'admin.privilege.revoke', m.id), { reason: reason.value }, {
    ...opts, onSuccess: () => { reasonFor.value = null },
  })
}

const destroy = (m) => {
  if (!confirm(`Delete ${m.name}'s membership record? ${m.card_number ? 'Their card number will be released.' : ''}`)) return
  router.delete(route('admin.privilege.destroy', m.id), { preserveScroll: true })
}

const preview = ref(null)
const previewFlipped = ref(false)
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
