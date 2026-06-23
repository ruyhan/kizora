<template>
  <div class="kz-skeleton" :class="rounded" :style="style" aria-hidden="true"></div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  width:   { type: String, default: '100%' },
  height:  { type: String, default: '1rem' },
  rounded: { type: String, default: 'rounded-lg' },
})

const style = computed(() => ({ width: props.width, height: props.height }))
</script>

<style scoped>
.kz-skeleton {
  position: relative;
  overflow: hidden;
  background: #ECECEE;
}
.kz-skeleton::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.65), transparent);
  animation: kz-shimmer 1.4s infinite;
}
@keyframes kz-shimmer { 100% { transform: translateX(100%); } }
@media (prefers-reduced-motion: reduce) { .kz-skeleton::after { animation: none; } }
</style>
