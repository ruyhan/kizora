<template>
  <div ref="root" class="relative">
    <button
      type="button"
      :disabled="disabled"
      @click="toggle"
      @keydown="onKey"
      aria-haspopup="listbox"
      :aria-expanded="open"
      :class="[triggerClass, error ? 'border-error' : '', disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer']"
      class="input-field w-full flex items-center justify-between gap-2 text-left">
      <span class="truncate" :class="selectedLabel ? 'text-charcoal' : 'text-muted'">{{ selectedLabel || placeholder }}</span>
      <ChevronDownIcon class="w-4 h-4 text-muted shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" />
    </button>

    <Teleport to="body">
      <Transition name="kz-dd">
        <div
          v-if="open"
          ref="panel"
          role="listbox"
          :style="panelStyle"
          class="fixed z-[120] rounded-xl bg-white border border-purple-100 shadow-xl shadow-ink/10 overflow-hidden">
          <ul class="max-h-64 overflow-y-auto py-1.5">
            <li
              v-for="(opt, i) in options" :key="String(opt.value)"
              role="option" :aria-selected="opt.value === modelValue"
              @click="select(opt)" @mousemove="activeIndex = i"
              :class="[
                'flex items-center justify-between gap-2 px-3.5 py-2.5 text-sm cursor-pointer transition-colors',
                i === activeIndex ? 'bg-purple-50 text-primary' : 'text-charcoal',
              ]">
              <span :class="['truncate', opt.value === modelValue ? 'font-semibold text-primary' : 'font-medium']">{{ opt.label }}</span>
              <CheckIcon v-if="opt.value === modelValue" class="w-4 h-4 text-primary shrink-0" />
            </li>
            <li v-if="!options.length" class="px-3.5 py-3 text-sm text-muted text-center">No options</li>
          </ul>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onBeforeUnmount } from 'vue'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue:   { type: [String, Number, Boolean, null], default: null },
  options:      { type: Array, default: () => [] }, // [{ value, label }]
  placeholder:  { type: String, default: 'Select…' },
  disabled:     { type: Boolean, default: false },
  error:        { type: Boolean, default: false },
  triggerClass: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const root = ref(null)
const panel = ref(null)
const open = ref(false)
const activeIndex = ref(-1)
const panelStyle = ref({})

const selectedLabel = computed(() => props.options.find(o => o.value === props.modelValue)?.label ?? '')

const position = () => {
  const btn = root.value?.querySelector('button')
  if (!btn) return
  const r = btn.getBoundingClientRect()
  const below = window.innerHeight - r.bottom
  const openUp = below < 280 && r.top > below
  panelStyle.value = {
    left: `${r.left}px`,
    width: `${r.width}px`,
    ...(openUp ? { bottom: `${window.innerHeight - r.top + 6}px` } : { top: `${r.bottom + 6}px` }),
  }
}

const openMenu = async () => {
  if (props.disabled) return
  open.value = true
  activeIndex.value = Math.max(0, props.options.findIndex(o => o.value === props.modelValue))
  await nextTick()
  position()
  window.addEventListener('scroll', position, true)
  window.addEventListener('resize', close)
  document.addEventListener('mousedown', onDocClick)
}
const close = () => {
  if (!open.value) return
  open.value = false
  window.removeEventListener('scroll', position, true)
  window.removeEventListener('resize', close)
  document.removeEventListener('mousedown', onDocClick)
}
const toggle = () => (open.value ? close() : openMenu())
const onDocClick = (e) => { if (!root.value?.contains(e.target) && !panel.value?.contains(e.target)) close() }

const select = (opt) => { emit('update:modelValue', opt.value); close() }

const onKey = (e) => {
  if (!open.value) {
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); openMenu() }
    return
  }
  if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex.value = Math.min(props.options.length - 1, activeIndex.value + 1) }
  else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex.value = Math.max(0, activeIndex.value - 1) }
  else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); const o = props.options[activeIndex.value]; if (o) select(o) }
  else if (e.key === 'Escape' || e.key === 'Tab') { close() }
}

onBeforeUnmount(close)
</script>

<style scoped>
.kz-dd-enter-active { transition: opacity .15s ease, transform .15s ease; }
.kz-dd-leave-active { transition: opacity .1s ease; }
.kz-dd-enter-from { opacity: 0; transform: translateY(-4px); }
.kz-dd-leave-to { opacity: 0; }
</style>
