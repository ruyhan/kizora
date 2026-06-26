<template>
  <div class="border-b border-purple-100 mb-6 overflow-x-auto scrollbar-hide">
    <nav class="flex gap-1 min-w-max">
      <Link v-for="t in tabs" :key="t.label" :href="t.href"
        :class="t.active ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-charcoal'"
        class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors whitespace-nowrap">
        {{ t.label }}
      </Link>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const tabs = computed(() => [
  { label: 'SMTP Configuration', href: route('admin.email.smtp'),      active: route().current('admin.email.smtp') },
  { label: 'Brand Settings',     href: route('admin.email.brand'),     active: route().current('admin.email.brand') },
  { label: 'Email Templates',    href: route('admin.email.templates'), active: route().current('admin.email.templates') || route().current('admin.email.templates.edit') },
  { label: 'Email Logs',         href: route('admin.email.logs'),      active: route().current('admin.email.logs') },
])
</script>
