<template>
  <AppLayout>
    <Head title="Privacy Policy" />
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <h1 class="font-display text-4xl font-bold text-charcoal mb-4">{{ title }}</h1>
      <p class="text-muted mb-10">Last updated: {{ lastUpdated }}</p>
      <div class="prose prose-purple max-w-none space-y-6 text-muted leading-relaxed">
        <section v-for="(section, i) in sections" :key="i">
          <h2 class="font-display text-2xl font-semibold text-charcoal mb-3">{{ section.heading }}</h2>
          <p>{{ section.body }}</p>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ page: Object })

const fallbackSections = [
  { heading: '1. Information We Collect', body: 'When you use the Kizora website, we collect information including your name, email address, phone number, and delivery address when you place an order or create an account.' },
  { heading: '2. How We Use Your Information', body: 'We use your information to process orders, provide customer support, send order updates, and improve our services. We do not sell your personal information to third parties.' },
  { heading: '3. Data Security', body: 'We implement appropriate security measures to protect your personal information. All sensitive data is encrypted and stored securely.' },
  { heading: '4. Contact Us', body: 'If you have any questions about our Privacy Policy, please contact us at hello@kizora.com.bd.' },
]

const title       = computed(() => props.page?.title ?? 'Privacy Policy')
const lastUpdated = computed(() => props.page?.last_updated ?? 'June 2026')
const sections    = computed(() => props.page?.sections?.length ? props.page.sections : fallbackSections)
</script>
