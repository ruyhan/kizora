<template>
  <AppLayout>
    <Head :title="page.meta_title || 'Contact Us'">
      <meta v-if="page.meta_description" name="description" :content="page.meta_description" head-key="description" />
    </Head>

    <!-- Hero -->
    <div class="py-16 text-center">
      <div class="max-w-4xl mx-auto px-4">
        <div class="w-16 h-16 rounded-2xl gradient-purple flex items-center justify-center mx-auto mb-5 shadow-lg shadow-purple-300">
          <ChatBubbleLeftRightIcon class="w-8 h-8 text-white" />
        </div>
        <h1 class="font-display text-4xl md:text-5xl font-bold text-charcoal mb-4">{{ page.title }}</h1>
        <p class="text-muted text-lg max-w-xl mx-auto">{{ page.subtitle }}</p>
      </div>
    </div>

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="grid md:grid-cols-2 gap-12">

        <!-- Contact Form -->
        <div class="card p-8 relative overflow-hidden">
          <!-- Instant thank-you, shown over the form frame -->
          <Transition name="thanks">
            <div v-if="sent" class="absolute inset-0 z-10 bg-white flex flex-col items-center justify-center text-center px-8">
              <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mb-4">
                <CheckCircleIcon class="w-10 h-10 text-success" />
              </div>
              <h2 class="font-display text-2xl font-semibold text-charcoal mb-2">Thank you!</h2>
              <p class="text-muted leading-relaxed max-w-sm">Your message has been sent successfully. Our team will get back to you shortly.</p>
              <button type="button" @click="resetForm" class="btn-secondary mt-6 px-5 py-2.5 text-sm">Send another message</button>
            </div>
          </Transition>

          <h2 class="font-display text-2xl font-semibold text-charcoal mb-6">Send a Message</h2>
          <form @submit.prevent="submit" class="space-y-5">
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Your Name *</label>
                <input v-model="form.name" type="text" class="input-field" placeholder="Full name" required>
                <p v-if="errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Phone</label>
                <PhoneInput v-model="form.phone" :input-class="errors.phone ? 'input-field border-error' : 'input-field'" placeholder="Phone number" />
                <p v-if="errors.phone" class="text-error text-xs mt-1">{{ errors.phone }}</p>
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address *</label>
              <input v-model="form.email" type="email" class="input-field" placeholder="your@email.com" required>
              <p v-if="errors.email" class="text-error text-xs mt-1">{{ errors.email }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Subject</label>
              <input v-model="form.subject" type="text" class="input-field" placeholder="How can we help?">
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Message *</label>
              <textarea v-model="form.message" rows="5" class="input-field" placeholder="Your message..." required></textarea>
              <p v-if="errors.message" class="text-error text-xs mt-1">{{ errors.message }}</p>
            </div>
            <RecaptchaCheckbox form="contact" />
            <button type="submit" :disabled="processing" class="btn-primary w-full py-3.5 flex items-center justify-center gap-2">
              <PaperAirplaneIcon v-if="!processing" class="w-4 h-4" />
              <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
              {{ processing ? 'Sending…' : 'Send Message' }}
            </button>
          </form>
        </div>

        <!-- Contact Info -->
        <div class="space-y-5">
          <div v-for="info in contactInfo" :key="info.label"
            class="flex gap-4 p-6 card items-start hover:shadow-md transition-shadow duration-200">
            <div class="w-12 h-12 rounded-2xl gradient-purple flex items-center justify-center shrink-0 shadow-md">
              <component :is="info.icon" class="w-6 h-6 text-white" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-charcoal mb-1">{{ info.label }}</p>
              <p class="text-muted text-sm leading-relaxed" v-html="info.value"></p>
              <a v-if="info.href" :href="info.href"
                class="inline-flex items-center gap-1.5 text-primary text-sm hover:underline mt-2 font-medium">
                {{ info.action }}
                <ArrowRightIcon class="w-3.5 h-3.5" />
              </a>
            </div>
          </div>

          <template v-if="showMap">
            <!-- Embedded Google Map (from admin) -->
            <div v-if="mapSrc" class="rounded-3xl overflow-hidden bg-purple-50 aspect-video border border-purple-100 shadow-sm">
              <iframe :src="mapSrc" title="Our location" class="w-full h-full block" style="border:0"
                loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen></iframe>
            </div>
            <!-- Location image fallback (from Media Library) -->
            <a v-else-if="page.map_image" :href="site.map_url || 'https://maps.google.com'" target="_blank" rel="noopener"
              class="block rounded-3xl overflow-hidden bg-purple-50 aspect-video group">
              <img :src="page.map_image" alt="Our location" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
            </a>
            <!-- Placeholder when nothing is configured -->
            <div v-else class="rounded-3xl overflow-hidden bg-purple-50 aspect-video flex items-center justify-center border-2 border-dashed border-purple-200">
              <div class="text-center p-8">
                <div class="w-16 h-16 rounded-2xl gradient-purple flex items-center justify-center mx-auto mb-4 shadow-md">
                  <MapPinIcon class="w-8 h-8 text-white" />
                </div>
                <p class="font-semibold text-charcoal mb-1">Find us here</p>
                <p class="text-muted text-sm mb-3">{{ site.address }}</p>
                <a :href="site.map_url || 'https://maps.google.com'" target="_blank"
                  class="inline-flex items-center gap-1.5 text-primary text-sm font-medium hover:underline">
                  Open Google Maps
                  <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                </a>
              </div>
            </div>
          </template>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import PhoneInput from '@/Components/ui/PhoneInput.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'
import {
  ChatBubbleLeftRightIcon,
  MapPinIcon,
  PhoneIcon,
  EnvelopeIcon,
  ClockIcon,
  PaperAirplaneIcon,
  ArrowPathIcon,
  ArrowRightIcon,
  ArrowTopRightOnSquareIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ page: { type: Object, default: () => ({}) } })

const sitePage = usePage()
const site = computed(() => sitePage.props.site || {})
const page = computed(() => props.page || {})

const form = ref({ name: '', email: '', phone: '', subject: '', message: '' })
const errors = ref({})
const processing = ref(false)
const sent = ref(false)

const resetForm = () => {
  form.value = { name: '', email: '', phone: '', subject: '', message: '' }
  errors.value = {}
  sent.value = false
}

const { getToken } = useRecaptcha()

const submit = async () => {
  processing.value = true
  errors.value = {}

  let token = null
  try {
    token = await getToken('contact')
  } catch {
    errors.value = { message: 'Please complete the verification.' }
    processing.value = false
    return
  }

  router.post(route('contact.send'), { ...form.value, recaptcha_token: token }, {
    onSuccess: () => {
      form.value = { name: '', email: '', phone: '', subject: '', message: '' }
      processing.value = false
      sent.value = true // instant thank-you over the form
    },
    onError: (errs) => { errors.value = errs; processing.value = false },
  })
}

const telLink = (p) => 'tel:' + String(p || '').replace(/[^+\d]/g, '')

// "Call us" shows both numbers, each as its own tel: link, on separate lines.
const phoneLines = computed(() =>
  [site.value.phone_1, site.value.phone_2]
    .filter(Boolean)
    .map(p => `<a href="${telLink(p)}" class="hover:text-primary transition-colors">${p}</a>`)
    .join('<br>')
)

// Each card is shown only when its admin toggle is on AND the underlying
// Brand & Contact detail has a value — empty content removes the card (and icon).
const contactInfo = computed(() => [
  { icon: MapPinIcon,   label: 'Visit us', value: site.value.address,       href: site.value.map_url || 'https://maps.google.com', action: 'Get directions', on: page.value.show_visit !== false },
  { icon: PhoneIcon,    label: 'Call us',  value: phoneLines.value,         href: telLink(site.value.phone_1 || site.value.phone_2), action: 'Call now', on: page.value.show_call !== false },
  { icon: EnvelopeIcon, label: 'Email us', value: site.value.email,         href: site.value.email ? `mailto:${site.value.email}` : null, action: 'Send email', on: page.value.show_email !== false },
  { icon: ClockIcon,    label: 'Opening hours', value: site.value.opening_hours, href: null, action: null, on: page.value.show_hours !== false },
].filter(i => i.on && i.value))

// Embedded map. Admin can paste either the bare embed URL or the whole
// <iframe …> snippet from Google Maps → Share → Embed; we extract the src.
const showMap = computed(() => page.value.show_map !== false)
const mapSrc = computed(() => {
  const raw = String(page.value.map_embed || '').trim()
  if (!raw) return ''
  const m = raw.match(/src=["']([^"']+)["']/i)
  return m ? m[1] : raw
})
</script>

<style scoped>
.thanks-enter-active { transition: opacity .3s ease, transform .3s ease; }
.thanks-enter-from { opacity: 0; transform: scale(.97); }
</style>
