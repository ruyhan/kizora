<template>
  <div class="flex items-baseline gap-x-2 gap-y-1 flex-wrap">
    <span class="font-display font-bold text-ink" :class="finalCls">{{ currency }}{{ fmt(shownFinal) }}</span>
    <span v-if="onSale" class="text-muted line-through" :class="compareCls">{{ currency }}{{ fmt(price) }}</span>
    <!-- Approved Privilege Member (logged in): their discount is already taken off -->
    <span v-if="memberActive" class="member-chip" :class="size === 'sm' ? 'text-[9px] px-1.5 py-0.5' : 'text-[11px] px-2.5 py-1'"
      :title="`Privilege Member price — ${percent}% off`">
      Member −{{ percent }}%
    </span>
    <span v-else-if="onSale && off" class="badge badge-accent" :class="size === 'sm' ? 'text-[10px] px-2 py-0.5' : ''">{{ off }}% OFF</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useMemberPrice } from '@/composables/useMemberPrice'

const props = defineProps({
  price:    { type: [Number, String], default: 0 },   // regular / compare-at
  final:    { type: [Number, String], default: 0 },   // price to pay
  off:      { type: Number, default: 0 },              // discount percentage
  size:     { type: String, default: 'md' },           // sm | md | lg
  currency: { type: String, default: '৳' },
  member:   { type: Boolean, default: true },          // apply the logged-in member's discount
})

const { percent, isMember, apply } = useMemberPrice()
const memberActive = computed(() => props.member && isMember.value && Number(props.final) > 0)
const shownFinal = computed(() => memberActive.value ? apply(props.final) : Number(props.final))

const onSale = computed(() => Number(props.price) > shownFinal.value)
const fmt = (v) => Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 })

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

<style scoped>
.member-chip {
  align-self: center;
  border-radius: 9999px;
  font-weight: 700;
  letter-spacing: 0.02em;
  white-space: nowrap;
  color: #141414;
  background: linear-gradient(135deg, #b8955f 0%, #ebcda2 50%, #c9a770 100%);
}
</style>
