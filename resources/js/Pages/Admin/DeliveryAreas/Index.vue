<template>
  <AdminLayout>
    <Head title="Delivery Areas" />

    <div class="space-y-6 w-full">

      <SettingsTabs />

      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold font-display text-charcoal">Delivery Areas</h1>
        <p class="text-muted text-sm mt-1">
          Browse seeded areas. Availability is controlled per-city in
          <Link :href="route('admin.delivery-cities.index')" class="text-primary hover:underline">Delivery Cities</Link>.
        </p>
      </div>

      <!-- Filters -->
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1 max-w-sm">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
          <input v-model="search" @input="applyFilters" type="text"
            placeholder="Search area or postal code…" class="input-field pl-10 text-sm">
        </div>
        <div class="sm:w-56">
          <SearchableSelect
            :modelValue="cityId"
            @update:modelValue="onCityFilter"
            :options="cityFilterOptions"
            placeholder="All cities" />
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-purple-50 border-b border-purple-100">
            <tr>
              <th class="text-left px-5 py-3.5 text-xs font-semibold text-muted uppercase tracking-wider">Area</th>
              <th class="text-left px-5 py-3.5 text-xs font-semibold text-muted uppercase tracking-wider">City</th>
              <th class="text-left px-5 py-3.5 text-xs font-semibold text-muted uppercase tracking-wider">Postal Code</th>
              <th class="text-left px-5 py-3.5 text-xs font-semibold text-muted uppercase tracking-wider">City Status</th>
              <th class="text-right px-5 py-3.5 text-xs font-semibold text-muted uppercase tracking-wider">Edit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="area in areas.data" :key="area.id" class="hover:bg-purple-50/30 transition-colors">
              <td class="px-5 py-3 font-medium text-charcoal">{{ area.name }}</td>
              <td class="px-5 py-3 text-muted">{{ area.city.name }}</td>
              <td class="px-5 py-3 text-muted font-mono">{{ area.postal_code || '—' }}</td>
              <td class="px-5 py-3">
                <span :class="area.city.is_active ? 'badge-success' : 'badge-gray'" class="badge text-xs">
                  {{ area.city.is_active ? 'Available' : 'Unavailable' }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <button @click="openEdit(area)" class="p-2 text-muted hover:text-primary hover:bg-purple-50 rounded-lg transition-colors">
                  <PencilSquareIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!areas.data.length" class="py-16 text-center">
          <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
            <MapPinIcon class="w-7 h-7 text-primary/40" />
          </div>
          <p class="text-muted">No areas found.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="areas.last_page > 1" class="flex justify-center gap-2">
        <Link v-for="link in areas.links" :key="link.label"
          :href="link.url || '#'"
          :class="[
            link.active ? 'bg-primary text-white' : 'bg-white text-charcoal hover:bg-purple-50',
            !link.url ? 'opacity-40 pointer-events-none' : ''
          ]"
          class="px-3 py-1.5 rounded-lg text-sm border border-purple-100 transition-colors"
          v-html="link.label" />
      </div>
    </div>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
          <div class="p-6 border-b border-purple-100 flex items-center justify-between">
            <h3 class="font-display font-semibold text-charcoal text-lg">Edit Area</h3>
            <button @click="modalOpen = false" class="p-2 text-muted hover:text-charcoal rounded-lg hover:bg-gray-100">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <form @submit.prevent="save" class="p-6 space-y-4">
            <p class="text-xs text-muted">City: <span class="font-medium text-charcoal">{{ editing?.city.name }}</span></p>
            <div>
              <label class="block text-sm font-medium text-charcoal mb-1.5">Area Name *</label>
              <input v-model="form.name" type="text" class="input-field" required>
              <p v-if="formErrors.name" class="text-error text-xs mt-1">{{ formErrors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-charcoal mb-1.5">Postal Code</label>
              <input v-model="form.postal_code" type="text" class="input-field font-mono" placeholder="e.g. 3900">
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="modalOpen = false" class="btn-secondary flex-1 text-sm">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary flex-1 text-sm">
                {{ saving ? 'Saving…' : 'Save' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { MagnifyingGlassIcon, MapPinIcon, PencilSquareIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ areas: Object, cities: Array, filters: Object })

const search = ref(props.filters?.search || '')
const cityId = ref(props.filters?.city_id || '')

const cityFilterOptions = computed(() => [
  { value: '', label: 'All cities' },
  ...props.cities.map(c => ({ value: c.id, label: c.name })),
])

const onCityFilter = (val) => {
  cityId.value = val
  applyFilters()
}

let timer = null
const applyFilters = () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('admin.delivery-areas.index'),
      { search: search.value, city_id: cityId.value },
      { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
}

// Edit modal
const modalOpen  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formErrors = ref({})
const form = ref({ name: '', postal_code: '' })

const openEdit = (area) => {
  editing.value = area
  formErrors.value = {}
  form.value = { name: area.name, postal_code: area.postal_code || '' }
  modalOpen.value = true
}

const save = () => {
  saving.value = true
  router.patch(route('admin.delivery-areas.update', editing.value.id), form.value, {
    preserveScroll: true,
    onError: (errs) => { formErrors.value = errs; saving.value = false },
    onSuccess: () => { saving.value = false; modalOpen.value = false },
    onFinish: () => { saving.value = false },
  })
}
</script>
