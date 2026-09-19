<template>
  <div>
    <div class="flex items-center justify-between mb-1">
      <label class="block text-sm font-medium text-charcoal">{{ label }}</label>
      <span class="text-xs text-muted">{{ items.length }} / {{ max }}</span>
    </div>
    <p v-if="hint" class="text-xs text-muted mb-3">{{ hint }}</p>

    <ul class="space-y-2">
      <li v-for="(item, i) in items" :key="i" class="flex items-start gap-2 group">
        <span class="mt-2.5 w-5 text-xs font-semibold text-muted text-right shrink-0">{{ i + 1 }}.</span>
        <div class="flex-1 min-w-0">
          <textarea :value="item" @input="update(i, $event.target.value)" rows="2" :maxlength="maxlength"
            class="input-field text-sm resize-none" :placeholder="placeholder"></textarea>
          <p class="text-[10px] text-muted text-right mt-0.5">{{ (item || '').length }}/{{ maxlength }}</p>
        </div>
        <div class="flex flex-col gap-0.5 shrink-0 pt-1">
          <button type="button" @click="move(i, -1)" :disabled="i === 0" title="Move up"
            class="p-1 rounded-md text-muted hover:text-ink hover:bg-purple-50 disabled:opacity-25"><ChevronUpIcon class="w-4 h-4" /></button>
          <button type="button" @click="move(i, 1)" :disabled="i === items.length - 1" title="Move down"
            class="p-1 rounded-md text-muted hover:text-ink hover:bg-purple-50 disabled:opacity-25"><ChevronDownIcon class="w-4 h-4" /></button>
        </div>
        <button type="button" @click="remove(i)" title="Remove"
          class="mt-1.5 p-1.5 rounded-md text-muted hover:text-error hover:bg-red-50 shrink-0"><TrashIcon class="w-4 h-4" /></button>
      </li>
    </ul>

    <button type="button" @click="add" :disabled="items.length >= max"
      class="mt-3 w-full inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl border border-dashed border-purple-200 text-sm font-medium text-charcoal hover:bg-purple-50 disabled:opacity-40 transition-colors">
      <PlusIcon class="w-4 h-4" /> {{ placeholder }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { PlusIcon, TrashIcon, ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: String,
  hint: String,
  placeholder: { type: String, default: 'Add item…' },
  max: { type: Number, default: 10 },
  maxlength: { type: Number, default: 140 },
})
const emit = defineEmits(['update:modelValue'])

const items = computed(() => props.modelValue)
const set = (arr) => emit('update:modelValue', arr)
const update = (i, v) => { const a = [...items.value]; a[i] = v; set(a) }
const add = () => set([...items.value, ''])
const remove = (i) => set(items.value.filter((_, j) => j !== i))
const move = (i, d) => {
  const a = [...items.value]
  const j = i + d
  if (j < 0 || j >= a.length) return
  ;[a[i], a[j]] = [a[j], a[i]]
  set(a)
}
</script>
