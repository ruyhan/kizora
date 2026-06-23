<template>
  <div>
    <label class="text-sm font-medium text-charcoal mb-1.5 flex items-center gap-1.5">
      {{ label }}
      <span v-if="sensitive" class="text-[10px] font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">encrypted</span>
    </label>
    <slot />
    <p v-if="hint" class="text-xs text-muted mt-1">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  label:     { type: String,  required: true },
  field:     { type: String,  default: '' },
  sensitive: { type: Boolean, default: false },
  hint:      { type: String,  default: '' },
})
</script>
