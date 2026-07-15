<template>
  <AppLayout>
    <Head :title="page?.meta_title || 'Terms & Conditions'">
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
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatPolicyBody } from '@/utils/policyContent'

const props = defineProps({ page: Object })

const fallbackSections = [
  { heading: '1. Acceptance of Terms', body: 'By using the Kizora website and services, you agree to these Terms & Conditions. If you do not agree, please do not use our services.' },
  { heading: '2. Orders & Payment', body: 'All orders are subject to availability. We reserve the right to cancel or refuse any order. For online payment methods, payment must be completed before your order is dispatched.' },
  { heading: '3. Delivery', body: 'Delivery times are estimates and may vary based on location and demand. Orders above ৳3,000 qualify for free delivery; otherwise a standard delivery charge applies.' },
  { heading: '4. Returns & Warranty', body: 'Unused products in their original condition and packaging may be returned within 7 days of delivery. Every Kizora product is also covered by a 1-year warranty against manufacturing defects. Normal wear and tear is not covered.' },
  { heading: '5. Contact', body: 'For any queries regarding these terms, please contact us at hello@kizora.com.bd or +880 1730-728954.' },
]

const title       = computed(() => props.page?.title ?? 'Terms & Conditions')
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
