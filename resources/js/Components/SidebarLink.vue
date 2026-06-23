<template>
  <Link :href="href"
    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
    :class="active
      ? 'bg-accent text-white shadow-lg shadow-accent/20'
      : 'text-gray-400 hover:text-white hover:bg-white/10'">
    <slot name="icon" />
    <span class="flex-1 truncate"><slot /></span>
    <span v-if="badge > 0"
      :class="active ? 'bg-white text-accent' : 'bg-accent text-white'"
      class="shrink-0 text-[10px] font-bold px-1.5 min-w-[18px] h-[18px] rounded-full inline-flex items-center justify-center">
      {{ badge > 99 ? '99+' : badge }}
    </span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({
  href: String,
  active: { type: Boolean, default: false },
  badge: { type: Number, default: 0 },
})
</script>
