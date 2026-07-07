<template>
  <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="checkbox" :checked="enabled" @change="setEnabled($event.target.checked)" class="rounded text-primary w-4 h-4">
      <div>
        <p class="font-display font-semibold text-charcoal text-lg leading-tight">Enable variations</p>
        <p class="text-muted text-xs">Add one or more variation groups (Color, Size, Material…), each with its own options &amp; images.</p>
      </div>
    </label>

    <div v-if="enabled" class="mt-5 space-y-5">
      <!-- Variation groups -->
      <div v-for="(g, gi) in items" :key="gi" class="rounded-2xl border border-purple-100 bg-purple-50/30 p-4 space-y-3">
        <!-- group header -->
        <div class="flex items-center gap-2">
          <input :value="g.name" @input="setGroup(gi, 'name', $event.target.value)" type="text"
            class="input-field font-semibold flex-1" placeholder="Group name — e.g. Color, Size, Material">
          <select :value="g.type" @change="setGroupType(gi, $event.target.value)" class="input-field w-40 shrink-0">
            <option value="color">Color swatches</option>
            <option value="button">Buttons</option>
          </select>
          <button type="button" @click="removeGroup(gi)" aria-label="Remove group"
            class="w-9 h-9 rounded-lg border border-purple-100 bg-white text-error flex items-center justify-center shrink-0 hover:bg-error/5">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- options -->
        <div class="space-y-2">
          <div v-for="(o, oi) in g.options" :key="oi" class="flex items-start gap-2 rounded-xl border border-purple-100 bg-white p-2.5">
            <span class="mt-2 text-[11px] font-bold text-muted w-4 shrink-0">{{ oi + 1 }}</span>
            <div class="flex-1 min-w-0 space-y-2">
              <template v-if="g.type === 'color'">
                <ColorField :model-value="o.value" @update:model-value="setOption(gi, oi, 'value', $event)" />
                <input :value="o.label" @input="setOption(gi, oi, 'label', $event.target.value)" type="text"
                  class="input-field text-sm" placeholder="Colour name — e.g. Red, Navy Blue">
              </template>
              <input v-else :value="o.value" @input="setOption(gi, oi, 'value', $event.target.value)" type="text"
                class="input-field text-sm" placeholder="Option value — e.g. S, M, L, Cotton">
              <!-- optional per-option price adjustment + stock -->
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <input :value="o.price" @input="setOption(gi, oi, 'price', $event.target.value)" type="number" step="0.01"
                    class="input-field text-sm" placeholder="± Price (৳)">
                  <p class="text-[10px] text-muted mt-0.5">Added to product price. Empty = no change.</p>
                </div>
                <div>
                  <input :value="o.stock" @input="setOption(gi, oi, 'stock', $event.target.value)" type="number" min="0"
                    class="input-field text-sm" placeholder="Stock (optional)">
                  <p class="text-[10px] text-muted mt-0.5">Empty = shares product stock.</p>
                </div>
              </div>
              <!-- optional per-option image -->
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg border border-purple-100 bg-purple-50/40 overflow-hidden flex items-center justify-center shrink-0">
                  <img v-if="o.image" :src="o.image" class="w-full h-full object-cover" alt="">
                  <PhotoIcon v-else class="w-4 h-4 text-muted/40" />
                </div>
                <button type="button" @click="openPicker((url) => setOption(gi, oi, 'image', url))" class="btn-secondary text-xs px-3 py-1.5">
                  {{ o.image ? 'Change image' : 'Choose image' }}
                </button>
                <button v-if="o.image" type="button" @click="setOption(gi, oi, 'image', '')" class="text-error text-xs hover:underline">Remove</button>
              </div>
            </div>
            <button type="button" @click="removeOption(gi, oi)" aria-label="Remove option"
              class="mt-1 w-8 h-8 rounded-lg border border-purple-100 bg-white text-error flex items-center justify-center shrink-0 hover:bg-error/5">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <button type="button" @click="addOption(gi)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-accent transition-colors">
          <PlusIcon class="w-3.5 h-3.5" /> Add option
        </button>
      </div>

      <button type="button" @click="addGroup" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-accent transition-colors">
        <PlusIcon class="w-4 h-4" /> Add variation group
      </button>

      <MediaPicker :open="picker.open" @close="picker.open = false" @select="onPicked" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import ColorField from '@/Components/ui/ColorField.vue'
import { PhotoIcon, TrashIcon, XMarkIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  enabled: { type: Boolean, default: false },
  items:   { type: Array, default: () => [] }, // [{ name, type, options:[{value,image}] }]
})
const emit = defineEmits(['update:enabled', 'update:items'])

const picker = ref({ open: false, apply: null })

const newOption = (type) => (type === 'color'
  ? { value: '#111827', label: '', image: '', price: '', stock: '' }
  : { value: '', image: '', price: '', stock: '' })
const newGroup = () => ({ name: '', type: 'color', options: [newOption('color')] })

const setEnabled = (v) => {
  emit('update:enabled', v)
  if (v && !props.items.length) emit('update:items', [newGroup()])
}
const addGroup = () => emit('update:items', [...(props.items || []), newGroup()])
const removeGroup = (gi) => { const a = [...props.items]; a.splice(gi, 1); emit('update:items', a) }
const setGroup = (gi, key, val) => emit('update:items', props.items.map((g, i) => (i === gi ? { ...g, [key]: val } : g)))
const setGroupType = (gi, type) => emit('update:items', props.items.map((g, i) => (i === gi ? { ...g, type } : g)))

const addOption = (gi) => emit('update:items', props.items.map((g, i) =>
  (i === gi ? { ...g, options: [...(g.options || []), newOption(g.type)] } : g)))
const removeOption = (gi, oi) => emit('update:items', props.items.map((g, i) =>
  (i === gi ? { ...g, options: g.options.filter((_, j) => j !== oi) } : g)))
const setOption = (gi, oi, key, val) => emit('update:items', props.items.map((g, i) =>
  (i === gi ? { ...g, options: g.options.map((o, j) => (j === oi ? { ...o, [key]: val } : o)) } : g)))

const openPicker = (apply) => { picker.value = { open: true, apply } }
const onPicked = (media) => {
  if (picker.value.apply && media?.file_url) picker.value.apply(media.file_url)
  picker.value.open = false
}
</script>
