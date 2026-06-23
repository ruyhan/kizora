<template>
  <div class="mb-6">
    <div class="flex items-center gap-2 mb-4">
      <Cog6ToothIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Settings</h1>
    </div>
    <div class="border-b border-purple-100 overflow-x-auto scrollbar-hide">
      <nav class="flex gap-1 min-w-max">
        <Link v-for="t in tabs" :key="t.label" :href="t.href"
          :class="t.active ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-charcoal'"
          class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors whitespace-nowrap">
          {{ t.label }}
        </Link>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'

const tabs = computed(() => [
  { label: 'General',           href: route('admin.content.edit', { group: 'settings' }), active: route().current('admin.content.edit', { group: 'settings' }) },
  { label: 'Delivery Settings', href: route('admin.content.edit', { group: 'delivery' }), active: route().current('admin.content.edit', { group: 'delivery' }) },
  { label: 'Product Page',      href: route('admin.content.edit', { group: 'product' }),  active: route().current('admin.content.edit', { group: 'product' }) },
  { label: 'Invoice Settings',  href: route('admin.invoice-settings.index'), active: route().current('admin.invoice-settings*') },
  { label: 'Chatbot Settings',  href: route('admin.chatbot-settings.index'), active: route().current('admin.chatbot-settings*') },
  { label: 'Payment Gateways',  href: route('admin.payments.index'),         active: route().current('admin.payments*') },
  { label: 'Delivery Cities',   href: route('admin.delivery-cities.index'),  active: route().current('admin.delivery-cities*') },
  { label: 'Delivery Areas',    href: route('admin.delivery-areas.index'),   active: route().current('admin.delivery-areas*') },
  { label: 'Security / CAPTCHA', href: route('admin.recaptcha.index'),        active: route().current('admin.recaptcha*') },
])
</script>
