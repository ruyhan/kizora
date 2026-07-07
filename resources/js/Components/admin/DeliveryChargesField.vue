<template>
  <div>
    <!-- Header row: summary + add button -->
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
      <div class="flex items-center gap-2">
        <span class="badge badge-success text-xs">{{ configured.length }} cities with charge</span>
        <span v-if="unsetCount" class="badge badge-gray text-xs">{{ unsetCount }} without</span>
      </div>
      <button type="button" @click="openAdd" class="btn-primary text-sm px-4 py-2">+ Add Delivery Charge</button>
    </div>

    <!-- Grouped charges table (one row per charge amount) -->
    <div v-if="groups.length" class="border border-purple-100 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-purple-50/60">
          <tr class="text-left text-[11px] text-muted uppercase tracking-wider">
            <th class="px-4 py-2.5 font-semibold">Delivery Charge</th>
            <th class="px-4 py-2.5 font-semibold">Cities</th>
            <th class="px-4 py-2.5 font-semibold text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-purple-50">
          <tr v-for="g in groups" :key="g.charge" class="hover:bg-purple-50/30 align-top">
            <td class="px-4 py-3 font-semibold text-charcoal whitespace-nowrap">৳{{ Number(g.charge).toLocaleString() }}</td>
            <td class="px-4 py-3">
              <p class="text-charcoal font-medium">{{ g.count }} {{ g.count === 1 ? 'city' : 'cities' }}</p>
              <p class="text-xs text-muted mt-0.5 leading-relaxed">{{ cityNames(g.cities) }}</p>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <button type="button" @click="openEdit(g)" class="text-xs text-primary px-2.5 py-1 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">Edit</button>
              <button type="button" @click="removeGroup(g)" class="text-xs text-error px-2.5 py-1 bg-red-50 rounded-lg hover:bg-red-100 transition-colors ml-1.5">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="text-muted text-sm">No delivery charges set yet. Click "Add Delivery Charge" to assign charges to your cities.</p>

    <!-- ── Add / Edit popup ── -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="modal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="modal.open = false"></div>
          <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-border flex flex-col max-h-[85vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border shrink-0">
              <h3 class="font-display font-semibold text-charcoal">
                {{ modal.mode === 'edit' ? `Edit charge — ৳${Number(modal.originalCharge).toLocaleString()}` : 'Add Delivery Charge' }}
              </h3>
              <button type="button" @click="modal.open = false" class="text-muted hover:text-ink"><XMarkIcon class="w-5 h-5" /></button>
            </div>

            <div class="p-5 space-y-4 overflow-y-auto">
              <!-- City multi-select -->
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="text-sm font-medium text-charcoal">Cities</label>
                  <span class="text-xs text-muted">{{ modal.cityIds.length }} selected</span>
                </div>
                <div class="relative mb-2">
                  <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
                  <input v-model="modalSearch" type="text" placeholder="Search cities…" class="input-field pl-9 text-sm py-2">
                </div>
                <div class="flex items-center gap-3 mb-2 px-0.5">
                  <button type="button" @click="selectAllSelectable" class="text-xs text-primary hover:underline">Select all available</button>
                  <button type="button" @click="clearSelection" class="text-xs text-muted hover:underline">Clear</button>
                </div>
                <div class="border border-border rounded-xl divide-y divide-purple-50 max-h-60 overflow-y-auto">
                  <label v-for="city in filteredModalCities" :key="city.id"
                    :class="isDisabled(city) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-purple-50/40'"
                    class="flex items-center gap-3 px-3.5 py-2.5">
                    <input type="checkbox" :value="city.id" v-model="modal.cityIds" :disabled="isDisabled(city)"
                      class="w-4 h-4 rounded border-purple-200" style="accent-color:#17181B">
                    <span class="text-sm text-charcoal flex-1">
                      {{ city.name }}
                      <span v-if="!city.is_active" class="badge badge-gray text-[9px] ml-1">Inactive</span>
                    </span>
                    <span v-if="isDisabled(city)" class="text-[11px] text-muted">already ৳{{ Number(city.delivery_charge).toLocaleString() }}</span>
                  </label>
                  <p v-if="!filteredModalCities.length" class="px-3.5 py-6 text-center text-sm text-muted">No cities match.</p>
                </div>
                <p class="text-[11px] text-muted mt-1">Cities that already have a charge are shown disabled — edit or delete their charge from the table.</p>
                <p v-if="errors.city_ids" class="text-error text-xs mt-1">{{ errors.city_ids }}</p>
              </div>

              <!-- Charge input -->
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Delivery charge (৳)</label>
                <input v-model="modal.charge" type="number" min="0" step="0.01" class="input-field" placeholder="e.g. 100" @keyup.enter="save">
                <p v-if="errors.delivery_charge" class="text-error text-xs mt-1">{{ errors.delivery_charge }}</p>
                <p class="text-xs text-muted mt-1">Applied to every selected city.</p>
              </div>
            </div>

            <div class="px-5 py-4 border-t border-border flex justify-end gap-2 shrink-0">
              <button type="button" @click="modal.open = false" class="btn-secondary text-sm px-4">Cancel</button>
              <button type="button" @click="save" :disabled="saving" class="btn-primary text-sm px-5 disabled:opacity-60">{{ saving ? 'Saving…' : 'Save' }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  cities: { type: Array, default: () => [] }, // all delivery cities (id, name, delivery_charge, is_active)
})

const hasCharge = (c) => c.delivery_charge !== null && c.delivery_charge !== undefined
const configured = computed(() => props.cities.filter(hasCharge))
const unsetCount = computed(() => props.cities.length - configured.value.length)

// Group configured cities by charge amount → one table row per amount.
const groups = computed(() => {
  const map = new Map()
  for (const c of configured.value) {
    const key = Number(c.delivery_charge)
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(c)
  }
  return [...map.entries()]
    .map(([charge, cities]) => ({ charge, cities: [...cities].sort((a, b) => a.name.localeCompare(b.name)), count: cities.length }))
    .sort((a, b) => a.charge - b.charge)
})

const cityNames = (cities, limit = 4) => {
  const names = cities.map(c => c.name)
  return names.length <= limit ? names.join(', ') : `${names.slice(0, limit).join(', ')} +${names.length - limit} more`
}

// ── Modal ──
const modal = reactive({ open: false, mode: 'add', charge: '', cityIds: [], originalIds: [], originalCharge: '' })
const modalSearch = ref('')
const errors = ref({})
const saving = ref(false)

// In "add": disable any city that already has a charge.
// In "edit": disable cities that belong to a *different* charge group.
const isDisabled = (city) => {
  if (!hasCharge(city)) return false
  return modal.mode === 'add' ? true : !modal.originalIds.includes(city.id)
}

const filteredModalCities = computed(() => {
  const q = modalSearch.value.trim().toLowerCase()
  const list = q ? props.cities.filter(c => c.name.toLowerCase().includes(q)) : props.cities
  // selectable first, then disabled; alphabetical within each
  return [...list].sort((a, b) => (isDisabled(a) - isDisabled(b)) || a.name.localeCompare(b.name))
})

const openAdd = () => {
  modal.mode = 'add'; modal.charge = ''; modal.cityIds = []; modal.originalIds = []; modal.originalCharge = ''
  modalSearch.value = ''; errors.value = {}; modal.open = true
}
const openEdit = (g) => {
  modal.mode = 'edit'; modal.charge = g.charge; modal.originalCharge = g.charge
  modal.originalIds = g.cities.map(c => c.id)
  modal.cityIds = [...modal.originalIds]
  modalSearch.value = ''; errors.value = {}; modal.open = true
}
const selectAllSelectable = () => {
  const ids = new Set(modal.cityIds)
  filteredModalCities.value.forEach(c => { if (!isDisabled(c)) ids.add(c.id) })
  modal.cityIds = [...ids]
}
const clearSelection = () => {
  // keep disabled (other-group) cities out; just drop the selectable ones
  modal.cityIds = modal.cityIds.filter(id => {
    const c = props.cities.find(x => x.id === id)
    return c && isDisabled(c)
  })
}

const save = () => {
  errors.value = {}
  if (modal.charge === '' || Number(modal.charge) < 0 || isNaN(Number(modal.charge))) {
    errors.value.delivery_charge = 'Enter a valid charge (0 or more).'
    return
  }
  if (!modal.cityIds.length) {
    errors.value.city_ids = 'Select at least one city.'
    return
  }
  saving.value = true
  // On edit, cities removed from the group get their charge cleared.
  const removeIds = modal.mode === 'edit'
    ? modal.originalIds.filter(id => !modal.cityIds.includes(id))
    : []
  router.post(route('admin.delivery-charges.store'), {
    city_ids: modal.cityIds,
    delivery_charge: modal.charge,
    remove_ids: removeIds,
  }, {
    preserveScroll: true,
    onSuccess: () => { modal.open = false },
    onError: (e) => { errors.value = e },
    onFinish: () => { saving.value = false },
  })
}

const removeGroup = (g) => {
  const label = `৳${Number(g.charge).toLocaleString()}`
  if (!confirm(`Remove the ${label} charge from ${g.count} ${g.count === 1 ? 'city' : 'cities'}? Customers won't be able to check out to those cities until a charge is set again.`)) return
  router.post(route('admin.delivery-charges.bulk-delete'), { city_ids: g.cities.map(c => c.id) }, { preserveScroll: true })
}
</script>

<style scoped>
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .2s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
</style>
