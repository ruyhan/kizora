<template>
  <div class="flex items-center gap-2 flex-wrap">
    <input type="color" :value="hex" @input="onHex($event.target.value)"
      class="h-9 w-12 rounded border border-purple-100 bg-white p-0.5 cursor-pointer shrink-0">
    <input type="text" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
      class="input-field text-sm font-mono w-36" placeholder="#17181B">
    <div class="flex items-center gap-1.5">
      <span class="text-[11px] text-muted">opacity</span>
      <input type="range" min="0" max="100" :value="alphaPct" @input="onAlpha($event.target.value)" class="w-20" style="accent-color:#17181B">
      <span class="text-xs text-muted tabular-nums w-9 text-right">{{ alphaPct }}%</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ modelValue: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue'])

function hexToRgb (h) {
  let s = (h || '').replace('#', '')
  if (s.length === 3) s = s.split('').map((c) => c + c).join('')
  const n = parseInt(s.slice(0, 6) || '17181B', 16)
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 }
}
function rgbToHex (r, g, b) {
  return '#' + [r, g, b].map((x) => Math.max(0, Math.min(255, x | 0)).toString(16).padStart(2, '0')).join('')
}
function parse (v) {
  const s = (v || '').trim()
  if (!s) return { hex: '#17181B', alpha: 1 }
  if (s[0] === '#') {
    const raw = s.replace('#', '')
    const hex = '#' + raw.slice(0, 6).padEnd(6, '0')
    const alpha = raw.length >= 8 ? parseInt(raw.slice(6, 8), 16) / 255 : 1
    return { hex, alpha }
  }
  const m = s.match(/rgba?\(([^)]+)\)/i)
  if (m) {
    const p = m[1].split(',').map((x) => x.trim())
    const a = p[3] !== undefined ? parseFloat(p[3]) : 1
    return { hex: rgbToHex(+p[0], +p[1], +p[2]), alpha: isNaN(a) ? 1 : a }
  }
  return { hex: '#17181B', alpha: 1 }
}
function compose (hex, alpha) {
  if (alpha >= 1) return hex
  const { r, g, b } = hexToRgb(hex)
  return `rgba(${r}, ${g}, ${b}, ${Math.round(alpha * 100) / 100})`
}

const parsed = computed(() => parse(props.modelValue))
const hex = computed(() => parsed.value.hex)
const alphaPct = computed(() => Math.round(parsed.value.alpha * 100))

const onHex = (h) => emit('update:modelValue', compose(h, parsed.value.alpha))
const onAlpha = (p) => emit('update:modelValue', compose(parsed.value.hex, Number(p) / 100))
</script>
