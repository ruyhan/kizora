<template>
  <div>
    <div class="grid sm:grid-cols-2 gap-4">
      <!-- City -->
      <div>
        <label class="text-sm font-medium text-charcoal mb-1.5 block">Delivery City *</label>
        <SearchableSelect
          :modelValue="selectedCityId"
          @update:modelValue="onCitySelect"
          :options="cityOptions"
          placeholder="Search city…"
          :error="!!error" />
      </div>

      <!-- Area -->
      <div>
        <label class="text-sm font-medium text-charcoal mb-1.5 block">Delivery Area *</label>
        <SearchableSelect
          :modelValue="selectedAreaId"
          @update:modelValue="onAreaSelect"
          :options="areaOptions"
          :disabled="!selectedCityId"
          :placeholder="selectedCityId ? 'Search area…' : 'Select city first'"
          :error="!!error" />
      </div>
    </div>

    <p v-if="error" class="text-error text-xs mt-1.5">{{ error }}</p>

    <!-- City unavailable warning (non-blocking display) -->
    <div v-if="showUnavailable"
      class="mt-2.5 flex items-start gap-2 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs">
      <ExclamationTriangleIcon class="w-4 h-4 shrink-0 mt-px" />
      <span>{{ warning }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
  cities:     { type: Array, default: () => [] },  // [{id, name, is_active}]
  areas:      { type: Array, default: () => [] },  // [{id, delivery_city_id, name, postal_code}]
  modelValue: { type: [Number, null], default: null }, // selected area id
  error:      { type: String, default: '' },
  warning:    { type: String, default: 'Delivery is not available in your city yet.' },
})
const emit = defineEmits(['update:modelValue'])

// IDs can arrive as numbers or strings depending on the DB driver/serialisation
// (works locally but breaks after deploy if compared with ===). Compare loosely.
const sameId = (a, b) => a != null && b != null && String(a) === String(b)

const cityIdOfArea = (areaId) => props.areas.find(a => sameId(a.id, areaId))?.delivery_city_id ?? null

const selectedAreaId = ref(props.modelValue)
const selectedCityId = ref(cityIdOfArea(props.modelValue))

// Options for the searchable selects
const cityOptions = computed(() =>
  [...props.cities]
    .sort((a, b) => a.name.localeCompare(b.name))
    .map(c => ({ value: c.id, label: c.name }))
)
const areaOptions = computed(() =>
  props.areas
    .filter(a => sameId(a.delivery_city_id, selectedCityId.value))
    .map(a => ({ value: a.id, label: a.postal_code ? `${a.name} (${a.postal_code})` : a.name }))
)

const selectedCity = computed(() => props.cities.find(c => sameId(c.id, selectedCityId.value)) ?? null)
const showUnavailable = computed(() => selectedCity.value && !selectedCity.value.is_active)

const onCitySelect = (cityId) => {
  selectedCityId.value = cityId
  selectedAreaId.value = null
  emit('update:modelValue', null)
}
const onAreaSelect = (areaId) => {
  selectedAreaId.value = areaId
  emit('update:modelValue', areaId)
}

// Sync if parent changes value (autofill)
watch(() => props.modelValue, (val) => {
  selectedAreaId.value = val
  if (val) selectedCityId.value = cityIdOfArea(val)
})
</script>
