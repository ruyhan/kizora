<template>
  <Combobox :modelValue="modelValue" @update:modelValue="onSelect" :disabled="disabled" as="div" class="relative">

    <!-- Trigger field — shows the selected value -->
    <ComboboxButton
      class="input-field w-full flex items-center justify-between gap-2 text-left cursor-pointer"
      :class="{ 'border-error': error, 'opacity-50 cursor-not-allowed': disabled }">
      <span class="truncate" :class="selectedLabel ? 'text-charcoal' : 'text-muted'">
        {{ selectedLabel || placeholder }}
      </span>
      <ChevronUpDownIcon class="w-4.5 h-4.5 text-muted shrink-0" />
    </ComboboxButton>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
      @after-enter="focusSearch"
      @after-leave="query = ''"
    >
      <ComboboxOptions
        class="absolute z-50 mt-2 w-full rounded-2xl bg-white border border-purple-100
               shadow-xl shadow-purple-900/5 overflow-hidden focus:outline-none">

        <!-- Search bar inside the dropdown -->
        <div class="p-2 border-b border-purple-50">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
            <ComboboxInput
              ref="searchInput"
              class="w-full pl-9 pr-3 py-2 text-sm rounded-xl bg-purple-50/60 border border-transparent
                     focus:bg-white focus:border-purple-200 focus:outline-none text-charcoal placeholder:text-muted"
              :displayValue="() => query"
              @change="query = $event.target.value"
              placeholder="Search…"
              autocomplete="off" />
          </div>
        </div>

        <!-- Options -->
        <div class="max-h-56 overflow-auto py-1.5 scrollbar-hide">
          <div v-if="filtered.length === 0" class="px-4 py-6 text-center">
            <p class="text-sm text-muted">No results found</p>
          </div>

          <ComboboxOption
            v-for="opt in filtered" :key="opt.value"
            :value="opt.value" v-slot="{ active, selected }" as="template">
            <li :class="[
                  'flex items-center justify-between gap-2 px-4 py-2.5 cursor-pointer text-sm transition-colors',
                  active ? 'bg-purple-50 text-primary' : 'text-charcoal',
                ]">
              <span :class="['truncate', selected ? 'font-semibold text-primary' : 'font-medium']">{{ opt.label }}</span>
              <CheckIcon v-if="selected" class="w-4 h-4 text-primary shrink-0" />
            </li>
          </ComboboxOption>
        </div>
      </ComboboxOptions>
    </Transition>
  </Combobox>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import {
  Combobox, ComboboxInput, ComboboxButton, ComboboxOptions, ComboboxOption,
} from '@headlessui/vue'
import { MagnifyingGlassIcon, ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue:  { type: [Number, String, null], default: null },
  options:     { type: Array, default: () => [] },   // [{ value, label }]
  placeholder: { type: String, default: 'Select…' },
  disabled:    { type: Boolean, default: false },
  error:       { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const query       = ref('')
const searchInput = ref(null)

const selectedLabel = computed(() =>
  props.options.find(o => o.value === props.modelValue)?.label ?? ''
)

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter(o => o.label.toLowerCase().includes(q))
})

const onSelect = (val) => {
  query.value = ''
  emit('update:modelValue', val)
}

// Autofocus the search box once the panel has opened
const focusSearch = () => {
  nextTick(() => searchInput.value?.$el?.focus())
}
</script>
