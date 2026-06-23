<template>
  <div v-if="variations && variations.length" class="flex flex-wrap items-center gap-x-3 gap-y-1" :class="dense ? 'mt-0.5' : 'mt-1'">
    <span v-for="(v, i) in variations" :key="i" class="inline-flex items-center gap-1 text-xs text-muted">
      <span class="font-medium text-charcoal/70">{{ v.name }}:</span>
      <span v-if="v.type === 'color'" class="w-3 h-3 rounded-full border border-border shrink-0" :style="{ backgroundColor: v.value }"></span>
      <span class="text-charcoal/90">{{ v.label || v.value }}</span>
    </span>
  </div>
</template>

<script setup>
defineProps({
  variations: { type: Array, default: () => [] },
  dense: { type: Boolean, default: false },
})
</script>
