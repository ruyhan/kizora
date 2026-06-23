<template>
  <AdminLayout>
    <Head title="Roles & Permissions" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Roles &amp; Permissions</h1>
        <p class="text-muted text-sm">Control what each role can access.</p>
      </div>
      <button v-if="can('roles.create')" @click="openCreate" class="btn-primary text-sm px-4 py-2 inline-flex items-center gap-1.5">
        <PlusIcon class="w-4 h-4" /> New role
      </button>
    </div>

    <!-- Role cards -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="r in roles" :key="r.id" class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5 flex flex-col">
        <div class="flex items-start justify-between gap-2">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-display font-semibold text-charcoal">{{ r.name }}</h3>
              <span v-if="r.is_system" class="badge badge-gray text-[10px]">System</span>
            </div>
            <p class="text-muted text-xs mt-0.5">{{ r.description || '—' }}</p>
          </div>
          <ShieldCheckIcon class="w-6 h-6 text-primary/40 shrink-0" />
        </div>
        <div class="flex items-center gap-3 mt-3 text-xs text-muted">
          <span><span class="font-bold text-charcoal">{{ r.users_count }}</span> users</span>
          <span><span class="font-bold text-charcoal">{{ r.slug === 'super_admin' ? 'All' : r.permissions.length }}</span> permissions</span>
          <span v-if="!r.is_staff" class="badge badge-purple text-[10px]">Frontend only</span>
        </div>
        <div class="flex items-center gap-1.5 mt-4 pt-3 border-t border-purple-50">
          <button v-if="can('roles.edit')" @click="openEdit(r)" class="act">{{ r.is_system && r.slug==='super_admin' ? 'View' : 'Edit' }}</button>
          <button v-if="can('roles.create')" @click="duplicate(r)" class="act">Duplicate</button>
          <button v-if="can('roles.delete') && !PROTECTED.includes(r.slug)" @click="destroy(r)" class="act-danger ml-auto">Delete</button>
        </div>
      </div>
    </div>

    <!-- Create / Edit role modal with permission matrix -->
    <Modal :open="show" @update:open="show = $event" :title="form.id ? `Edit role: ${form.name}` : 'New role'" max-width="sm:max-w-3xl">
      <form @submit.prevent="submit" class="p-5 space-y-5">
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="lbl">Role name *</label>
            <input v-model="form.name" type="text" class="input-field" :disabled="isSystem" required>
            <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="lbl">Description</label>
            <input v-model="form.description" type="text" class="input-field">
          </div>
        </div>
        <label class="flex items-center gap-2.5 cursor-pointer" :class="isSystem ? 'opacity-50 pointer-events-none' : ''">
          <input type="checkbox" v-model="form.is_staff" class="rounded w-4 h-4">
          <span class="text-sm text-charcoal">Staff role (can access the admin panel)</span>
        </label>

        <!-- Permission matrix -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <p class="font-semibold text-charcoal text-sm">Permissions</p>
            <div v-if="!superLock" class="flex items-center gap-3 text-xs">
              <button type="button" @click="selectAll(true)" class="text-primary font-semibold hover:underline">Select all</button>
              <button type="button" @click="selectAll(false)" class="text-muted hover:underline">Clear all</button>
            </div>
          </div>
          <p v-if="superLock" class="text-xs text-muted mb-2">Super Admin always has every permission.</p>

          <div class="space-y-2 max-h-[46vh] overflow-y-auto pr-1 scrollbar-slim">
            <div v-for="group in catalogue" :key="group.module" class="border border-purple-100 rounded-xl overflow-hidden">
              <div class="flex items-center justify-between gap-2 px-3 py-2.5 bg-purple-50/50">
                <button type="button" @click="toggleOpen(group.module)" class="flex items-center gap-2 font-semibold text-sm text-charcoal">
                  <ChevronRightIcon class="w-4 h-4 transition-transform" :class="openModules.includes(group.module) ? 'rotate-90' : ''" />
                  {{ group.module }}
                  <span class="text-xs text-muted font-normal">({{ countInModule(group) }}/{{ group.permissions.length }})</span>
                </button>
                <label v-if="!superLock" class="flex items-center gap-1.5 text-xs text-muted cursor-pointer">
                  <input type="checkbox" :checked="allInModule(group)" @change="toggleModule(group, $event.target.checked)" class="rounded w-3.5 h-3.5">
                  All
                </label>
              </div>
              <div v-show="openModules.includes(group.module)" class="grid sm:grid-cols-2 gap-x-4 gap-y-1.5 px-3 py-3">
                <label v-for="p in group.permissions" :key="p.slug" class="flex items-center gap-2 text-sm cursor-pointer"
                  :class="superLock ? 'opacity-60' : ''">
                  <input type="checkbox" :value="p.slug" v-model="form.permissions" :disabled="superLock" class="rounded w-4 h-4">
                  <span class="text-charcoal">{{ p.label }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-2 pt-1">
          <button type="button" @click="show = false" class="btn-secondary flex-1 py-2.5 text-sm">Cancel</button>
          <button type="submit" :disabled="form.processing" class="btn-primary flex-1 py-2.5 text-sm">{{ form.id ? 'Save changes' : 'Create role' }}</button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/ui/Modal.vue'
import { usePermissions } from '@/composables/usePermissions'
import { PlusIcon, ShieldCheckIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ roles: Array, catalogue: Array })
const { can } = usePermissions()

// Roles the system depends on — never deletable.
const PROTECTED = ['super_admin', 'consumer']

const show = ref(false)
const isSystem = ref(false)
const superLock = ref(false)
const openModules = ref(props.catalogue.map((g) => g.module)) // expanded by default

const form = useForm({ id: null, name: '', description: '', is_staff: true, permissions: [] })

const allSlugs = computed(() => props.catalogue.flatMap((g) => g.permissions.map((p) => p.slug)))

const openCreate = () => {
  form.reset(); form.clearErrors()
  isSystem.value = false; superLock.value = false
  show.value = true
}
const openEdit = (r) => {
  form.reset(); form.clearErrors()
  Object.assign(form, { id: r.id, name: r.name, description: r.description || '', is_staff: r.is_staff, permissions: [...r.permissions] })
  isSystem.value = r.is_system
  superLock.value = r.slug === 'super_admin'
  if (superLock.value) form.permissions = [...allSlugs.value]
  show.value = true
}
const submit = () => {
  const opts = { preserveScroll: true, onSuccess: () => { show.value = false } }
  form.id ? form.put(route('admin.roles.update', form.id), opts) : form.post(route('admin.roles.store'), opts)
}
const duplicate = (r) => router.post(route('admin.roles.duplicate', r.id), {}, { preserveScroll: true })
const destroy = (r) => { if (confirm(`Delete role "${r.name}"? Users on it become Consumers.`)) router.delete(route('admin.roles.destroy', r.id), { preserveScroll: true }) }

// Matrix helpers
const toggleOpen = (m) => { const i = openModules.value.indexOf(m); i === -1 ? openModules.value.push(m) : openModules.value.splice(i, 1) }
const allInModule = (g) => g.permissions.every((p) => form.permissions.includes(p.slug))
const countInModule = (g) => g.permissions.filter((p) => form.permissions.includes(p.slug)).length
const toggleModule = (g, on) => {
  const slugs = g.permissions.map((p) => p.slug)
  form.permissions = on ? [...new Set([...form.permissions, ...slugs])] : form.permissions.filter((s) => !slugs.includes(s))
}
const selectAll = (on) => { form.permissions = on ? [...allSlugs.value] : [] }
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
.err { @apply text-error text-xs mt-1; }
.act { @apply text-xs text-primary px-2.5 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors; }
.act-danger { @apply text-xs text-error px-2.5 py-1.5 bg-red-50 rounded-lg hover:bg-red-100 transition-colors; }
</style>
