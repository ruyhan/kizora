<template>
  <div class="flex items-baseline gap-2 flex-wrap">
    <span class="font-display font-bold text-ink" :class="finalCls">{{ currency }}{{ fmt(final) }}</span>
    <span v-if="onSale" class="text-muted line-through" :class="compareCls">{{ currency }}{{ fmt(price) }}</span>
    <span v-if="onSale && off" class="badge badge-accent" :class="size === 'sm' ? 'text-[10px] px-2 py-0.5' : ''">{{ off }}% OFF</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  price:    { type: [Number, String], default: 0 },   // regular / compare-at
  final:    { type: [Number, String], default: 0 },   // price to pay
  off:      { type: Number, default: 0 },              // discount percentage
  size:     { type: String, default: 'md' },           // sm | md | lg
  currency: { type: String, default: '৳' },
})

const onSale = computed(() => Number(props.price) > Number(props.final))
const fmt = (v) => Number(v).toLocaleString()

const finalCls = computed(() => ({
  sm: 'text-base',
  md: 'text-2xl',
  lg: 'text-3xl sm:text-4xl',
}[props.size] ?? 'text-2xl'))

const compareCls = computed(() => ({
  sm: 'text-xs',
  md: 'text-base',
  lg: 'text-lg',
}[props.size] ?? 'text-base'))
</script>
