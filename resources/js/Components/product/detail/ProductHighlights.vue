<template>
  <div v-if="usps?.length" class="grid grid-cols-2 gap-2.5">
    <div v-for="(u, i) in usps" :key="i"
      class="usp-card min-w-0 rounded-xl bg-accent-50 border border-accent-100/70 px-3.5 py-2.5 hover:bg-white hover:border-accent-100 hover:shadow-sm hover:shadow-accent-50 hover:-translate-y-0.5 transition-all duration-200"
      :style="{ animationDelay: (i * 55) + 'ms' }">
      <p class="font-semibold text-charcoal text-[13px] leading-snug break-words">{{ u.title }}</p>
      <p v-if="u.text" class="text-muted text-[11px] mt-0.5 leading-snug break-words">{{ u.text }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ usps: { type: Array, default: () => [] } })
</script>

<style scoped>
@keyframes uspIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
.usp-card {
  opacity: 0;
  animation: uspIn .45s cubic-bezier(.16, 1, .3, 1) forwards;
}
@media (prefers-reduced-motion: reduce) {
  .usp-card { opacity: 1; animation: none; }
}
</style>
