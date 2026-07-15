<template>
  <AppLayout>
    <Head :title="seoTitle(page?.meta_title, 'Privacy Policy')">
      <meta v-if="page?.meta_description" name="description" :content="page.meta_description" head-key="description" />
    </Head>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
      <div class="max-w-4xl">
        <h1 class="font-display text-3xl sm:text-4xl font-bold text-charcoal mb-3">{{ title }}</h1>
        <p class="text-muted text-sm mb-10">Last updated: {{ lastUpdated }}</p>
        <div class="space-y-9">
          <section v-for="(section, i) in sections" :key="i">
            <h2 v-if="section.heading" class="font-display text-xl sm:text-2xl font-semibold text-charcoal mb-3 scroll-mt-24">{{ section.heading }}</h2>
            <div class="policy-body prose prose-purple max-w-none" v-html="formatPolicyBody(section.body)"></div>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { seoTitle } from '@/utils/seoTitle'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatPolicyBody } from '@/utils/policyContent'

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

<style scoped>
@reference "../../css/app.css";
/* Clean, readable long-form legal typography */
.policy-body :deep(p) { @apply text-muted leading-relaxed my-3; }
.policy-body :deep(ul) { @apply my-3 space-y-1.5 list-disc pl-5; }
.policy-body :deep(li) { @apply text-muted leading-relaxed pl-1; }
.policy-body :deep(li)::marker { color: #9b7cf0; }
.policy-body :deep(a) { @apply text-primary underline; }
.policy-body :deep(strong) { @apply text-charcoal font-semibold; }
</style>
