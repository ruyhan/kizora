<template>
  <AdminLayout>
    <Head title="Users" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Users</h1>
        <p class="text-muted text-sm">Staff accounts &amp; their roles.</p>
      </div>
      <div class="flex items-center gap-2">
        <a v-if="can('users.view')" :href="route('admin.users.export')" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5">
          <ArrowDownTrayIcon class="w-4 h-4" /> Export
        </a>
        <button v-if="can('users.create')" @click="openInvite" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5">
          <EnvelopeIcon class="w-4 h-4" /> Invite
        </button>
        <button v-if="can('users.create')" @click="openCreate" class="btn-primary text-sm px-4 py-2 inline-flex items-center gap-1.5">
          <PlusIcon class="w-4 h-4" /> Add user
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search name, email, phone…"
          class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.role" @update:model-value="v => { f.role = v; apply() }" :options="filterRoleOptions" trigger-class="text-sm py-2" class="w-40" />
      <SelectMenu :model-value="f.status" @update:model-value="v => { f.status = v; apply() }" :options="filterStatusOptions" trigger-class="text-sm py-2" class="w-36" />
    </div>

    <!-- Bulk bar -->
    <div v-if="selected.length" class="flex items-center gap-3 mb-3 px-4 py-2.5 rounded-xl bg-ink text-white text-sm">
      <span class="font-semibold">{{ selected.length }} selected</span>
      <div class="flex items-center gap-2 ml-auto">
        <button @click="bulk('activate')" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25">Activate</button>
        <button @click="bulk('suspend')" class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25">Suspend</button>
        <button v-if="can('users.delete')" @click="bulk('delete')" class="px-3 py-1.5 rounded-lg bg-accent hover:bg-accent/90">Delete</button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th class="px-4 py-3 w-10"><input type="checkbox" :checked="allChecked" @change="toggleAll" class="rounded"></th>
              <th class="th">User</th>
              <th class="th">Role</th>
              <th class="th">Status</th>
              <th class="th">Verified</th>
              <th class="th">Last login</th>
              <th class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="u in users.data" :key="u.id" class="hover:bg-purple-50/20">
              <td class="px-4 py-3"><input type="checkbox" :value="u.id" v-model="selected" class="rounded"></td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <Avatar :name="u.name" :src="u.avatar" />
                  <div class="min-w-0">
                    <p class="font-semibold text-charcoal text-sm truncate">{{ u.name }}</p>
                    <p class="text-muted text-xs truncate">{{ u.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3"><span class="badge badge-purple text-xs">{{ u.role?.name || '—' }}</span></td>
              <td class="px-4 py-3"><span :class="statusClass(u.status)" class="badge text-xs capitalize">{{ u.status }}</span></td>
              <td class="px-4 py-3">
                <CheckBadgeIcon v-if="u.email_verified" class="w-5 h-5 text-success" />
                <span v-else class="text-muted text-xs">—</span>
              </td>
              <td class="px-4 py-3 text-muted text-xs">{{ u.last_login_at || 'Never' }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1.5">
                  <button v-if="can('users.edit')" @click="openEdit(u)" class="act">Edit</button>
                  <button v-if="can('users.edit')" @click="openReset(u)" class="act">Reset</button>
                  <button v-if="can('users.edit')" @click="toggleStatus(u)" class="act">{{ u.status === 'suspended' ? 'Activate' : 'Suspend' }}</button>
                  <button v-if="can('users.delete')" @click="destroy(u)" class="act-danger">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="!users.data.length"><td colspan="7" class="px-5 py-12 text-center text-muted text-sm">No users found.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="users.links" />
    </div>

    <!-- Pending invitations -->
    <div v-if="invitations.length" class="mt-6 bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
      <h3 class="font-semibold text-charcoal mb-3">Pending invitations</h3>
      <div class="space-y-2">
        <div v-for="inv in invitations" :key="inv.id" class="flex items-center gap-3 text-sm border border-purple-50 rounded-xl px-3 py-2">
          <EnvelopeIcon class="w-4 h-4 text-muted shrink-0" />
          <span class="font-medium text-charcoal">{{ inv.email }}</span>
          <span class="badge badge-purple text-xs">{{ inv.role }}</span>
          <span v-if="inv.expired" class="badge badge-error text-xs">Expired</span>
          <span class="text-muted text-xs ml-auto">{{ inv.created_at }}</span>
          <button v-if="can('users.create')" @click="revoke(inv)" class="text-error text-xs hover:underline">Revoke</button>
        </div>
      </div>
    </div>

    <!-- Create / Edit modal -->
    <Modal :open="showForm" @update:open="showForm = $event" :title="form.id ? 'Edit user' : 'Add user'">
      <form @submit.prevent="submitForm" class="p-5 space-y-4">
        <div>
          <label class="lbl">Full name *</label>
          <input v-model="form.name" type="text" class="input-field" required>
          <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="lbl">Email *</label>
            <input v-model="form.email" type="email" class="input-field" required>
            <p v-if="form.errors.email" class="err">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="lbl">Phone</label>
            <input v-model="form.phone" type="text" class="input-field">
          </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="lbl">Role *</label>
            <SelectMenu v-model="form.role_id" :options="formRoleOptions" placeholder="Select role" :error="!!form.errors.role_id" />
            <p v-if="form.errors.role_id" class="err">{{ form.errors.role_id }}</p>
          </div>
          <div>
            <label class="lbl">Status *</label>
            <SelectMenu v-model="form.status" :options="formStatusOptions" />
          </div>
        </div>
        <div>
          <label class="lbl">{{ form.id ? 'New password (leave blank to keep)' : 'Password *' }}</label>
          <input v-model="form.password" type="password" class="input-field" :required="!form.id" placeholder="Min 8 characters">
          <p v-if="form.errors.password" class="err">{{ form.errors.password }}</p>
        </div>
        <div class="flex gap-2 pt-2">
          <button type="button" @click="showForm = false" class="btn-secondary flex-1 py-2.5 text-sm">Cancel</button>
          <button type="submit" :disabled="form.processing" class="btn-primary flex-1 py-2.5 text-sm">{{ form.id ? 'Save' : 'Create' }}</button>
        </div>
      </form>
    </Modal>

    <!-- Reset password modal -->
    <Modal :open="showReset" @update:open="showReset = $event" title="Reset password" max-width="sm:max-w-md">
      <form @submit.prevent="submitReset" class="p-5 space-y-4">
        <p class="text-sm text-muted">Set a new password for <span class="font-semibold text-charcoal">{{ resetForm.name }}</span>.</p>
        <input v-model="resetForm.password" type="text" class="input-field" placeholder="New password (min 8)" required>
        <p v-if="resetForm.errors.password" class="err">{{ resetForm.errors.password }}</p>
        <button type="submit" :disabled="resetForm.processing" class="btn-primary w-full py-2.5 text-sm">Reset password</button>
      </form>
    </Modal>

    <!-- Invite modal -->
    <Modal :open="showInvite" @update:open="showInvite = $event" title="Invite a team member" max-width="sm:max-w-md">
      <form @submit.prevent="submitInvite" class="p-5 space-y-4">
        <div>
          <label class="lbl">Email *</label>
          <input v-model="inviteForm.email" type="email" class="input-field" required>
          <p v-if="inviteForm.errors.email" class="err">{{ inviteForm.errors.email }}</p>
        </div>
        <div>
          <label class="lbl">Role *</label>
          <SelectMenu v-model="inviteForm.role_id" :options="formRoleOptions" placeholder="Select role" :error="!!inviteForm.errors.role_id" />
        </div>
        <div v-if="inviteLink" class="p-3 bg-purple-50 rounded-xl text-xs">
          <p class="font-semibold text-charcoal mb-1">Invitation link (share with the invitee):</p>
          <div class="flex items-center gap-2">
            <input :value="inviteLink" readonly class="input-field text-xs py-1.5 flex-1" @focus="$event.target.select()">
            <button type="button" @click="copyLink" class="btn-secondary text-xs px-3 py-1.5 shrink-0">Copy</button>
          </div>
        </div>
        <button type="submit" :disabled="inviteForm.processing" class="btn-primary w-full py-2.5 text-sm">Send invitation</button>
      </form>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/ui/Modal.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useToast } from 'vue-toastification'
import {
  PlusIcon, MagnifyingGlassIcon, ArrowDownTrayIcon, EnvelopeIcon, CheckBadgeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ users: Object, roles: Array, filters: Object, invitations: { type: Array, default: () => [] } })

const filterRoleOptions = computed(() => [{ value: '', label: 'All roles' }, ...(props.roles || []).map(r => ({ value: r.slug, label: r.name }))])
const formRoleOptions = computed(() => (props.roles || []).map(r => ({ value: r.id, label: r.name })))
const filterStatusOptions = [
  { value: '', label: 'All status' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
]
const formStatusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
]
const { can } = usePermissions()
const toast = useToast()

const statusClass = (s) => ({ active: 'badge-success', inactive: 'badge-gray', suspended: 'badge-error' }[s] || 'badge-gray')

// ── Filters ──
const f = ref({ search: props.filters?.search || '', role: props.filters?.role || '', status: props.filters?.status || '' })
let t = null
const apply = () => router.get(route('admin.users.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(t); t = setTimeout(apply, 350) }

// ── Bulk select ──
const selected = ref([])
const allChecked = computed(() => props.users.data.length && selected.value.length === props.users.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.users.data.map((u) => u.id) : [] }
const bulk = (action) => {
  if (action === 'delete' && !confirm(`Delete ${selected.value.length} user(s)?`)) return
  router.post(route('admin.users.bulk'), { action, ids: selected.value }, { preserveScroll: true, onSuccess: () => { selected.value = [] } })
}

// ── Create / Edit ──
const showForm = ref(false)
const form = useForm({ id: null, name: '', email: '', phone: '', role_id: '', status: 'active', password: '' })
const openCreate = () => { form.reset(); form.clearErrors(); form.id = null; showForm.value = true }
const openEdit = (u) => { form.reset(); form.clearErrors(); Object.assign(form, { id: u.id, name: u.name, email: u.email, phone: u.phone, role_id: props.roles.find((r) => r.slug === u.role?.slug)?.id || '', status: u.status, password: '' }); showForm.value = true }
const submitForm = () => {
  const opts = { preserveScroll: true, onSuccess: () => { showForm.value = false } }
  form.id ? form.put(route('admin.users.update', form.id), opts) : form.post(route('admin.users.store'), opts)
}

// ── Reset password ──
const showReset = ref(false)
const resetForm = useForm({ id: null, name: '', password: '' })
const openReset = (u) => { resetForm.reset(); resetForm.clearErrors(); resetForm.id = u.id; resetForm.name = u.name; showReset.value = true }
const submitReset = () => resetForm.patch(route('admin.users.reset-password', resetForm.id), { preserveScroll: true, onSuccess: () => { showReset.value = false } })

// ── Status / delete ──
const toggleStatus = (u) => router.patch(route('admin.users.status', u.id), { status: u.status === 'suspended' ? 'active' : 'suspended' }, { preserveScroll: true })
const destroy = (u) => { if (confirm(`Delete ${u.name}?`)) router.delete(route('admin.users.destroy', u.id), { preserveScroll: true }) }

// ── Invitations ──
const showInvite = ref(false)
const inviteForm = useForm({ email: '', role_id: '' })
const inviteLink = ref('')
const openInvite = () => { inviteForm.reset(); inviteForm.clearErrors(); inviteLink.value = ''; showInvite.value = true }
const submitInvite = () => inviteForm.post(route('admin.invitations.store'), {
  preserveScroll: true,
  onSuccess: () => { inviteLink.value = usePage().props.flash?.invite_link || ''; inviteForm.reset('email', 'role_id') },
})
const copyLink = () => { navigator.clipboard.writeText(inviteLink.value); toast.success('Link copied') }
const revoke = (inv) => { if (confirm(`Revoke invitation for ${inv.email}?`)) router.delete(route('admin.invitations.destroy', inv.id), { preserveScroll: true }) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
.err { @apply text-error text-xs mt-1; }
.act { @apply text-xs text-primary px-2.5 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors; }
.act-danger { @apply text-xs text-error px-2.5 py-1.5 bg-red-50 rounded-lg hover:bg-red-100 transition-colors; }
</style>
