<template>
  <div v-if="links && links.length > 3" class="flex flex-wrap justify-center gap-1.5 p-5 border-t border-purple-50">
    <template v-for="link in links" :key="link.label">
      <Link v-if="link.url" :href="link.url" preserve-scroll
        :class="link.active ? 'bg-ink text-white' : 'bg-white text-charcoal hover:bg-purple-50'"
        class="min-w-9 px-3 py-2 rounded-xl text-sm font-medium border border-purple-100 transition-colors text-center"
        v-html="link.label" />
      <span v-else class="min-w-9 px-3 py-2 rounded-xl text-sm text-muted/50 border border-transparent text-center" v-html="link.label" />
    </template>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({ links: { type: Array, default: () => [] } })
</script>
