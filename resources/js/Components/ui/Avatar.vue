<template>
  <div :class="sizeClass" class="rounded-full overflow-hidden shrink-0 flex items-center justify-center font-bold text-white"
    :style="src ? '' : `background:${bg}`">
    <img v-if="src" :src="src" :alt="name" class="w-full h-full object-cover">
    <span v-else :class="textClass">{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, default: '' },
  src: { type: String, default: null },
  size: { type: String, default: 'md' }, // sm | md | lg
})

const initials = computed(() =>
  (props.name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]?.toUpperCase()).join('')
)

const palette = ['#6366F1', '#0EA5E9', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6']
const bg = computed(() => {
  let h = 0
  for (const c of (props.name || '?')) h = c.charCodeAt(0) + ((h << 5) - h)
  return palette[Math.abs(h) % palette.length]
})

const sizeClass = computed(() => ({ sm: 'w-8 h-8', md: 'w-10 h-10', lg: 'w-14 h-14' }[props.size]))
const textClass = computed(() => ({ sm: 'text-xs', md: 'text-sm', lg: 'text-lg' }[props.size]))
</script>
