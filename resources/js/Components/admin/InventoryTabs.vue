<template>
  <div class="mb-6">
    <div class="flex items-center gap-2 mb-4">
      <CubeIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Inventory</h1>
    </div>
    <div class="border-b border-purple-100 overflow-x-auto scrollbar-hide">
      <nav class="flex gap-1 min-w-max">
        <Link v-for="t in tabs" :key="t.label" :href="t.href"
          :class="t.active ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-charcoal'"
          class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors whitespace-nowrap">{{ t.label }}</Link>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { CubeIcon } from '@heroicons/vue/24/outline'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()
const tabs = computed(() => [
  { label: 'Overview',       href: route('admin.inventory.index'),     active: route().current('admin.inventory.index'),     show: true },
  { label: 'Inventory Logs', href: route('admin.inventory.logs'),      active: route().current('admin.inventory.logs'),      show: can('inventory.logs') },
  { label: 'Analytics',      href: route('admin.inventory.analytics'), active: route().current('admin.inventory.analytics'), show: can('inventory.analytics') },
].filter((t) => t.show))
</script>
