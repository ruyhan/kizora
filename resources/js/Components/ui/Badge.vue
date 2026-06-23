<template>
  <span :class="['inline-flex items-center gap-1 rounded-full font-semibold leading-none', sizeCls, variantCls]">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'neutral' }, // neutral | accent | success | warning | error | info
  size:    { type: String, default: 'md' },        // sm | md
})

const sizeCls = computed(() => (props.size === 'sm' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-xs'))

const variants = {
  neutral: 'bg-[#F1F1F2] text-[#3C3D42]',
  accent:  'bg-accent text-white',
  success: 'bg-[#D1FAE5] text-[#065F46]',
  warning: 'bg-[#FEF3C7] text-[#92400E]',
  error:   'bg-[#FEE2E2] text-[#991B1B]',
  info:    'bg-[#E7E7EA] text-[#3C3D42]',
}
const variantCls = computed(() => variants[props.variant] ?? variants.neutral)
</script>
