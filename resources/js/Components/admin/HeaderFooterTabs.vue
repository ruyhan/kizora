<template>
  <div class="mb-6">
    <div class="flex items-center gap-2 mb-4">
      <RectangleGroupIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Header &amp; Footer</h1>
    </div>
    <div class="border-b border-purple-100 overflow-x-auto scrollbar-hide">
      <nav class="flex gap-1 min-w-max">
        <Link v-for="t in tabs" :key="t.group" :href="route('admin.content.edit', { group: t.group })"
          :class="route().current('admin.content.edit', { group: t.group }) ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-charcoal'"
          class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors whitespace-nowrap">
          {{ t.label }}
        </Link>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { RectangleGroupIcon } from '@heroicons/vue/24/outline'

// Content groups edited from Admin → Header & Footer (see HEADER_FOOTER_GROUPS in AdminSidebar).
const tabs = [
  { label: 'Header & Menu', group: 'header' },
  { label: 'Mega Menu',     group: 'megamenu' },
  { label: 'Footer',        group: 'footer' },
]
</script>
