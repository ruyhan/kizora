<template>
  <div class="mb-6">
    <div class="flex items-center gap-2 mb-4">
      <ChatBubbleLeftRightIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">AI Chatbot</h1>
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
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()
const tabs = computed(() => [
  { label: 'Chatbot Orders', href: route('admin.chatbot.orders'),        active: route().current('admin.chatbot.orders'),        show: can('chatbot.orders.view') },
  { label: 'Analytics',      href: route('admin.chatbot.analytics'),     active: route().current('admin.chatbot.analytics'),     show: can('chatbot.analytics.view') },
  { label: 'Conversations',  href: route('admin.chatbot.conversations'), active: route().current('admin.chatbot.conversations'), show: can('chatbot.analytics.view') },
].filter((t) => t.show))
</script>
