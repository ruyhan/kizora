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

// IDs can arrive as numbers OR strings depending on the DB driver / JSON
// serialisation, and that can differ between local and the live server. Normalise
// every id to a Number so the city/area selects match consistently everywhere
// (otherwise picking an area re-derived the city id as a different type and the
// strict-equality select dropped the chosen city).
const toId = (v) => (v === null || v === undefined || v === '') ? null : Number(v)

const cityIdOfArea = (areaId) => {
  const id = toId(areaId)
  const area = props.areas.find(a => toId(a.id) === id)
  return area ? toId(area.delivery_city_id) : null
}

const selectedAreaId = ref(toId(props.modelValue))
const selectedCityId = ref(cityIdOfArea(props.modelValue))

// Options for the searchable selects (ids normalised to Number)
const cityOptions = computed(() =>
  [...props.cities]
    .sort((a, b) => a.name.localeCompare(b.name))
    .map(c => ({ value: toId(c.id), label: c.name }))
)
const areaOptions = computed(() =>
  props.areas
    .filter(a => toId(a.delivery_city_id) === selectedCityId.value)
    .map(a => ({ value: toId(a.id), label: a.postal_code ? `${a.name} (${a.postal_code})` : a.name }))
)

const selectedCity = computed(() => props.cities.find(c => toId(c.id) === selectedCityId.value) ?? null)
// Only warn when the city is *known* to be inactive — a payload that doesn't
// include is_active (undefined) must not be treated as "unavailable".
const showUnavailable = computed(() =>
  !!selectedCity.value && selectedCity.value.is_active !== undefined && !selectedCity.value.is_active
)

const onCitySelect = (cityId) => {
  selectedCityId.value = toId(cityId)
  selectedAreaId.value = null
  emit('update:modelValue', null)
}
const onAreaSelect = (areaId) => {
  selectedAreaId.value = toId(areaId)
  emit('update:modelValue', toId(areaId))
}

// Keep the city in sync with the area when the parent value changes (autofill, or
// right after an area is chosen) — re-derive the city from the area's city id.
watch(() => props.modelValue, (val) => {
  selectedAreaId.value = toId(val)
  if (val !== null && val !== undefined) selectedCityId.value = cityIdOfArea(val)
})
</script>
