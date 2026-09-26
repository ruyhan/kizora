<template>
  <div class="mb-6">
    <div class="flex items-center gap-2 mb-1">
      <CreditCardIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Privilege Members</h1>
    </div>
    <p class="text-muted text-sm mb-4">Review membership requests, manage the Privilege Card design and reward points.</p>
    <div class="border-b border-purple-100 overflow-x-auto scrollbar-hide">
      <nav class="flex gap-1 min-w-max">
        <Link v-for="t in tabs" :key="t.label" :href="t.href"
          :class="t.active ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-charcoal'"
          class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors whitespace-nowrap inline-flex items-center gap-2">
          {{ t.label }}
          <span v-if="t.badge" class="min-w-5 h-5 px-1.5 rounded-full bg-accent text-white text-[11px] font-bold inline-flex items-center justify-center">{{ t.badge }}</span>
        </Link>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { CreditCardIcon } from '@heroicons/vue/24/outline'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()
const tabs = computed(() => [
  { label: 'Requests',      href: route('admin.privilege.index'),    active: route().current('admin.privilege.index'),    show: can('membership.view'), badge: usePage().props.pendingMemberships },
  { label: 'Card Settings', href: route('admin.privilege.settings'), active: route().current('admin.privilege.settings*'), show: can('membership.settings') },
  { label: 'Point Management', href: route('admin.privilege.points'), active: route().current('admin.privilege.points*'), show: can('membership.settings') },
].filter((t) => t.show))
</script>
