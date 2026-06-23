<template>
  <AppLayout>
    <Head title="FAQ" />

    <div class="py-16 text-center mb-16">
      <h1 class="font-display text-4xl md:text-5xl font-bold text-charcoal mb-4">Frequently asked questions</h1>
      <p class="text-muted text-lg">Everything you need to know about shopping with Kizora.</p>
    </div>

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
      <div class="space-y-3">
        <div
          v-for="(faq, i) in faqList" :key="faq.id ?? i"
          class="faq-card"
          :class="{ 'faq-card-open': openIndex === i }"
        >
          <button
            @click="openIndex = openIndex === i ? null : i"
            class="w-full flex items-center gap-4 p-5 text-left group"
          >
            <span class="faq-number shrink-0" :class="openIndex === i ? 'faq-number-active' : ''">
              {{ String(i + 1).padStart(2, '0') }}
            </span>
            <span
              class="flex-1 font-semibold text-sm sm:text-base leading-snug transition-colors duration-200"
              :class="openIndex === i ? 'text-primary' : 'text-charcoal group-hover:text-primary'"
            >
              {{ faq.question }}
            </span>
            <span
              class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300"
              :class="openIndex === i ? 'bg-primary text-white' : 'bg-purple-100 text-primary'"
            >
              <ChevronDownIcon
                class="w-3.5 h-3.5 transition-transform duration-300"
                :class="openIndex === i ? 'rotate-180' : ''"
              />
            </span>
          </button>

          <div class="accordion-body" :class="{ 'accordion-body-open': openIndex === i }">
            <div class="overflow-hidden">
              <p class="px-5 pb-5 pl-17 text-muted text-sm leading-relaxed">
                {{ faq.answer }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ faqs: Array })

const openIndex = ref(0)

const fallback = [
  { question: 'What does Kizora sell?', answer: 'Kizora specialises in everyday-carry gear: backpacks, laptop backpacks, school and kids’ trolley bags, water bottles, thermos flasks, and accessories.' },
  { question: 'Do you offer delivery?', answer: 'Yes, we deliver nationwide across Bangladesh. A standard delivery charge applies, and orders above ৳3,000 ship free.' },
  { question: 'What payment methods do you accept?', answer: 'We accept Cash on Delivery (COD) as well as bKash, Nagad and card payments via SSLCommerz at checkout.' },
  { question: 'Is there a warranty on Kizora products?', answer: 'Yes — every Kizora product comes with a 1-year warranty against manufacturing defects in materials and workmanship.' },
  { question: 'What is your return policy?', answer: 'Unused items in original condition and packaging can be returned within 7 days of delivery. See our Terms for full details.' },
  { question: 'Are your water bottles and flasks leak-proof?', answer: 'Our bottles are designed to be leak-proof, and our flasks use double-wall vacuum insulation that keeps drinks hot or cold for hours.' },
  { question: 'Do you offer bulk or wholesale orders?', answer: 'Yes. We offer bulk pricing and optional custom branding for offices, schools and events — reach out via our Contact page with your requirements and our team will help.' },
  { question: 'How do I track my order?', answer: 'After placing your order you’ll receive an order number. Use our Track Order page any time to check its current status.' },
]

const faqList = computed(() => (props.faqs?.length ? props.faqs : fallback))
</script>

<style scoped>
@reference "../../css/app.css";

.faq-card {
  @apply bg-white rounded-2xl border border-purple-100/60 shadow-sm overflow-hidden;
  border-left: 3px solid transparent;
  transition: border-left-color 0.2s ease, box-shadow 0.2s ease;
}
.faq-card-open {
  @apply shadow-md;
  border-left-color: #17181B;
}
.faq-number {
  @apply w-8 h-8 rounded-xl bg-purple-50 text-primary text-xs font-bold flex items-center justify-center shrink-0 transition-all duration-200;
}
.faq-number-active {
  @apply bg-primary text-white;
}
.accordion-body {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}
.accordion-body-open {
  grid-template-rows: 1fr;
}
</style>
