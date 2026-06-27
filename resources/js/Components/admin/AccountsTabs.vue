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

const c = (n) => route().current(n)
const tabs = computed(() => [
  { label: 'Dashboard',       href: route('admin.accounts.dashboard'),       active: c('admin.accounts.dashboard') },
  { label: 'Transactions',    href: route('admin.accounts.transactions'),    active: c('admin.accounts.transactions') },
  { label: 'Monthly Summary', href: route('admin.accounts.monthly'),         active: c('admin.accounts.monthly') },
  { label: 'Partner Capital', href: route('admin.accounts.partner-capital'), active: c('admin.accounts.partner-capital') },
  { label: 'Cash & Bank',     href: route('admin.accounts.cash-bank'),       active: c('admin.accounts.cash-bank') },
  { label: 'Account Ledger',  href: route('admin.accounts.ledger'),          active: c('admin.accounts.ledger') },
  { label: 'Settings',        href: route('admin.accounts.settings'),        active: c('admin.accounts.settings') },
  { label: 'Import Data',     href: route('admin.accounts.import'),          active: c('admin.accounts.import') },
])
</script>
