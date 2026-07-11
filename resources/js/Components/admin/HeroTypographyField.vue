<template>
  <div class="rounded-xl border border-purple-100 bg-white overflow-hidden">
    <!-- Mobile / Desktop tabs -->
    <div class="flex items-center gap-1 p-1.5 bg-purple-50/60 border-b border-purple-100">
      <button v-for="t in tabs" :key="t.key" type="button" @click="tab = t.key"
        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors"
        :class="tab === t.key ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-charcoal'">
        <component :is="t.icon" class="w-4 h-4" /> {{ t.label }}
      </button>
    </div>

    <div class="p-4 space-y-4">
      <p class="text-xs text-muted -mt-1">
        Editing <strong class="text-charcoal">{{ tab === 'mobile' ? 'Mobile' : 'Desktop' }}</strong> sizes. Leave a field empty to keep the design default.
      </p>

      <!-- Title -->
      <div>
        <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Title</p>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-muted mb-1 block">Font size (px)</label>
            <input v-model.number="item[k('title_size')]" type="number" min="0" class="input-field text-sm" :placeholder="ph.titleSize">
          </div>
          <div>
            <label class="text-xs text-muted mb-1 block">Font weight</label>
            <select v-model="item[k('title_weight')]" class="input-field text-sm">
              <option v-for="w in weights" :key="w.value" :value="w.value">{{ w.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div>
        <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Description</p>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-muted mb-1 block">Font size (px)</label>
            <input v-model.number="item[k('desc_size')]" type="number" min="0" class="input-field text-sm" :placeholder="ph.descSize">
          </div>
          <div>
            <label class="text-xs text-muted mb-1 block">Font weight</label>
            <select v-model="item[k('desc_weight')]" class="input-field text-sm">
              <option v-for="w in weights" :key="w.value" :value="w.value">{{ w.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Content width -->
      <div>
        <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Content area</p>
        <label class="text-xs text-muted mb-1 block">Max width (px)</label>
        <input v-model.number="item[k('content_width')]" type="number" min="0" class="input-field text-sm" :placeholder="ph.width">
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { DevicePhoneMobileIcon, ComputerDesktopIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ item: { type: Object, required: true } })

const tab = ref('mobile')
const tabs = [
  { key: 'mobile', label: 'Mobile', icon: DevicePhoneMobileIcon },
  { key: 'desktop', label: 'Desktop', icon: ComputerDesktopIcon },
]

// key builder → e.g. k('title_size') === 'title_size_mobile' | 'title_size_desktop'
const k = (base) => `${base}_${tab.value}`

const weights = [
  { value: '', label: 'Default' },
  { value: '400', label: 'Normal (400)' },
  { value: '500', label: 'Medium (500)' },
  { value: '600', label: 'Semibold (600)' },
  { value: '700', label: 'Bold (700)' },
  { value: '800', label: 'Extrabold (800)' },
  { value: '900', label: 'Black (900)' },
]

// Placeholders show the current design default for the active breakpoint.
const ph = computed(() => tab.value === 'mobile'
  ? { titleSize: '30', descSize: '14', width: '576' }
  : { titleSize: '60', descSize: '16', width: '576' })
</script>
