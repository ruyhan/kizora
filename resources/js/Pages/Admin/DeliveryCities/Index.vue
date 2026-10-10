<template>
  <AdminLayout>
    <Head title="Delivery Cities" />

    <div class="space-y-6 w-full">

      <SettingsTabs />

      <!-- Header -->
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Delivery Cities</h1>
          <p class="text-muted text-sm mt-1">Enable a city to make all its areas available at checkout</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge badge-success text-xs">{{ stats.active }} active</span>
          <span class="badge badge-gray text-xs">{{ stats.total }} total</span>
          <button type="button" @click="openCreate" class="btn-primary text-sm inline-flex items-center gap-1.5">
            <PlusIcon class="w-4 h-4" /> Add New City
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="relative max-w-sm">
        <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
        <input v-model="search" @input="onSearch" type="text"
          placeholder="Search city / district…" class="input-field pl-10 text-sm">
      </div>

      <!-- City grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="city in cities" :key="city.id"
          class="bg-white rounded-xl border border-purple-100 p-4 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="font-semibold text-charcoal truncate">{{ city.name }}</p>
            <p class="text-xs text-muted mt-0.5">{{ city.areas_count }} areas</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span :class="city.is_active ? 'badge-success' : 'badge-gray'" class="badge text-[10px]">
              {{ city.is_active ? 'Active' : 'Inactive' }}
            </span>
            <!-- Toggle -->
            <button @click="toggle(city)" type="button"
              :class="city.is_active ? 'bg-success' : 'bg-gray-300'"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0">
              <span :class="city.is_active ? 'translate-x-6' : 'translate-x-1'"
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
            </button>
          </div>
        </div>
      </div>

      <div v-if="!cities.length" class="py-16 text-center">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
          <BuildingOffice2Icon class="w-7 h-7 text-primary/40" />
        </div>
        <p class="text-muted">No cities found.</p>
      </div>

    </div>

    <!-- Add City Modal -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
          <div class="p-6 border-b border-purple-100 flex items-center justify-between">
            <h3 class="font-display font-semibold text-charcoal text-lg">Add New City</h3>
            <button @click="modalOpen = false" class="p-2 text-muted hover:text-charcoal rounded-lg hover:bg-gray-100">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <form @submit.prevent="save" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-charcoal mb-1.5">City Name *</label>
              <input v-model="form.name" type="text" class="input-field" required>
              <p v-if="formErrors.name" class="text-error text-xs mt-1">{{ formErrors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-charcoal mb-1.5">Delivery Charge</label>
              <input v-model="form.delivery_charge" type="number" min="0" step="0.01" class="input-field" placeholder="e.g. 150">
              <p class="text-xs text-muted mt-1">Checkout is blocked for cities without a delivery charge.</p>
              <p v-if="formErrors.delivery_charge" class="text-error text-xs mt-1">{{ formErrors.delivery_charge }}</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-charcoal">
              <input v-model="form.is_active" type="checkbox" class="rounded border-purple-200 text-primary">
              Active (available at checkout)
            </label>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="modalOpen = false" class="btn-secondary flex-1 text-sm">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary flex-1 text-sm">
                {{ saving ? 'Saving…' : 'Add City' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import { MagnifyingGlassIcon, BuildingOffice2Icon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ cities: Array, filters: Object, stats: Object })

const search = ref(props.filters?.search || '')
let searchTimer = null
const onSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('admin.delivery-cities.index'), { search: search.value }, {
      preserveState: true, preserveScroll: true, replace: true,
    })
  }, 350)
}

const toggle = (city) => {
  router.patch(route('admin.delivery-cities.update', city.id),
    { is_active: !city.is_active },
    { preserveScroll: true, preserveState: false }
  )
}

// Add city modal
const modalOpen  = ref(false)
const saving     = ref(false)
const formErrors = ref({})
const form = ref({ name: '', delivery_charge: '', is_active: true })

const openCreate = () => {
  formErrors.value = {}
  form.value = { name: '', delivery_charge: '', is_active: true }
  modalOpen.value = true
}

const save = () => {
  saving.value = true
  router.post(route('admin.delivery-cities.store'), {
    ...form.value,
    delivery_charge: form.value.delivery_charge === '' ? null : form.value.delivery_charge,
  }, {
    preserveScroll: true,
    onError: (errs) => { formErrors.value = errs },
    onSuccess: () => { modalOpen.value = false },
    onFinish: () => { saving.value = false },
  })
}
</script>
