<template>
  <div class="flex items-end justify-between gap-3 mb-6 sm:mb-7">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-[11px] font-bold uppercase tracking-[0.14em] text-accent mb-1.5">{{ eyebrow }}</p>
      <h2 class="section-title">{{ title }}</h2>
      <p v-if="subtitle" class="text-muted mt-1 text-sm sm:text-base">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.action" class="shrink-0">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  eyebrow:  { type: String, default: '' },
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>
