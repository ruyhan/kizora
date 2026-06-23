<template>
  <component
    :is="tag"
    :href="href"
    :type="tag === 'button' ? type : undefined"
    :disabled="isDisabled"
    :class="classes"
    :aria-busy="loading || undefined"
  >
    <span v-if="loading" class="kz-spinner" aria-hidden="true"></span>
    <slot name="icon-left" />
    <slot />
    <slot name="icon-right" />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary | secondary | ghost | accent | danger
  size:    { type: String, default: 'md' },       // sm | md | lg
  href:    { type: String, default: null },        // renders an Inertia <Link>
  type:    { type: String, default: 'button' },
  loading: { type: Boolean, default: false },
  disabled:{ type: Boolean, default: false },
  block:   { type: Boolean, default: false },
})

const tag = computed(() => (props.href ? Link : 'button'))
const isDisabled = computed(() => props.disabled || props.loading)

const base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-all duration-200 select-none ' +
  'focus-visible:outline-none disabled:opacity-50 disabled:pointer-events-none active:scale-[0.98]'

const sizes = {
  sm: 'h-9 px-3.5 text-xs',
  md: 'h-11 px-5 text-sm',       // 44px touch target
  lg: 'h-12 px-6 text-base',
}

const variants = {
  primary:   'bg-ink text-white hover:bg-[#2A2C31] hover:-translate-y-px focus-visible:ring-2 focus-visible:ring-ink/30 focus-visible:ring-offset-2',
  secondary: 'bg-surface text-ink border border-border hover:bg-[#F4F4F5] hover:border-[#D5D5D9] focus-visible:ring-2 focus-visible:ring-ink/20',
  ghost:     'bg-transparent text-ink hover:bg-[#F1F1F2] focus-visible:ring-2 focus-visible:ring-ink/20',
  accent:    'bg-accent text-white hover:bg-accent-600 hover:-translate-y-px focus-visible:ring-2 focus-visible:ring-accent/40 focus-visible:ring-offset-2',
  danger:    'bg-transparent text-error border border-[#F3C6C6] hover:bg-error/5 focus-visible:ring-2 focus-visible:ring-error/30',
}

const classes = computed(() => [
  base,
  sizes[props.size] ?? sizes.md,
  variants[props.variant] ?? variants.primary,
  props.block ? 'w-full' : '',
])
</script>

<style scoped>
.kz-spinner {
  width: 1rem;
  height: 1rem;
  border-radius: 9999px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  opacity: 0.85;
  animation: kz-spin 0.6s linear infinite;
  flex-shrink: 0;
}
@keyframes kz-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .kz-spinner { animation-duration: 1.4s; } }
</style>
