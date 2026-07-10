<template>
  <footer class="relative z-[1] bg-charcoal text-white pb-[calc(5rem+env(safe-area-inset-bottom,0px))] md:pb-0">
    <div class="gradient-purple h-1"></div>

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-16">
      <div class="grid grid-cols-2 gap-x-6 gap-y-8 lg:grid-cols-4 lg:gap-10">

        <!-- Brand — full width on mobile -->
        <div class="col-span-2 lg:col-span-1">
          <div class="mb-4">
            <img
              :src="darkLogo"
              alt="Kizora"
              class="h-8 lg:h-9 w-auto object-contain"
              :style="darkInvert ? 'filter: brightness(0) invert(1); opacity: 0.95;' : 'opacity: 0.95;'"
            >
          </div>
          <p class="text-gray-400 text-sm leading-relaxed mb-5 line-clamp-3 lg:line-clamp-none">
            {{ footer.about }}
          </p>
          <div v-if="socials.length" class="flex flex-wrap gap-3">
            <a v-for="(s, i) in socials" :key="i" :href="s.url" target="_blank" rel="noopener"
              class="social-icon" :aria-label="s.platform">
              <SocialIcon :platform="s.platform" class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- Quick Links — half width on mobile -->
        <div class="col-span-1 lg:col-span-1">
          <h4 class="font-display text-sm lg:text-base font-semibold text-white mb-4 tracking-wide">Quick Links</h4>
          <ul class="space-y-2.5">
            <li v-for="(l, i) in footerLinks" :key="i"><Link :href="l.url" class="footer-link">{{ l.label }}</Link></li>
          </ul>
        </div>

        <!-- Categories — half width on mobile -->
        <div class="col-span-1 lg:col-span-1">
          <h4 class="font-display text-sm lg:text-base font-semibold text-white mb-4 tracking-wide">Categories</h4>
          <ul class="space-y-2.5">
            <li v-for="c in categoryGroups" :key="c.id"><Link :href="route('shop.index', { category: c.slug })" class="footer-link">{{ c.name }}</Link></li>
          </ul>
        </div>

        <!-- Contact — full width on mobile -->
        <div class="col-span-2 lg:col-span-1">
          <h4 class="font-display text-sm lg:text-base font-semibold text-white mb-4 tracking-wide">Contact Us</h4>
          <ul class="space-y-3">
            <li v-if="site.address" class="flex gap-3 text-gray-400 text-sm">
              <MapPinIcon class="w-5 h-5 text-accent shrink-0 mt-0.5" />
              <span class="leading-relaxed">{{ site.address }}</span>
            </li>
            <li v-if="site.phone_1 || site.phone_2" class="flex gap-3 text-gray-400 text-sm items-start">
              <PhoneIcon class="w-5 h-5 text-accent shrink-0 mt-0.5" />
              <div>
                <a v-if="site.phone_1" :href="telLink(site.phone_1)" class="block hover:text-white transition-colors">{{ site.phone_1 }}</a>
                <a v-if="site.phone_2" :href="telLink(site.phone_2)" class="block hover:text-white transition-colors mt-1">{{ site.phone_2 }}</a>
              </div>
            </li>
            <li v-if="site.email" class="flex gap-3 text-gray-400 text-sm items-start">
              <EnvelopeIcon class="w-5 h-5 text-accent shrink-0 mt-0.5" />
              <a :href="`mailto:${site.email}`" class="hover:text-white transition-colors break-all">{{ site.email }}</a>
            </li>
            <li v-if="site.opening_hours" class="flex gap-3 text-gray-400 text-sm items-center">
              <ClockIcon class="w-5 h-5 text-accent shrink-0" />
              <span>{{ site.opening_hours }}</span>
            </li>
          </ul>
        </div>

      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-gray-700/50">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 md:py-6">

        <!-- Mobile layout: centered stack -->
        <div class="flex flex-col items-center gap-2 md:hidden">
          <p class="text-gray-500 text-xs text-center">
            {{ copyrightText }}
          </p>
          <p class="text-purple-400 text-xs text-center">
            {{ footer.tagline }}
          </p>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <template v-for="(l, i) in legalLinks" :key="i">
              <Link :href="l.url" class="hover:text-white transition-colors">{{ l.label }}</Link>
              <span v-if="i < legalLinks.length - 1">·</span>
            </template>
          </div>
        </div>

        <!-- Desktop layout: left-right -->
        <div class="hidden md:flex items-center justify-between gap-4">
          <p class="text-gray-500 text-sm">
            {{ copyrightText }} ·
            <span class="text-purple-400">{{ footer.tagline }}</span>
          </p>
          <div class="flex gap-6 text-sm">
            <Link v-for="(l, i) in legalLinks" :key="i" :href="l.url" class="text-gray-500 hover:text-white transition-colors">{{ l.label }}</Link>
          </div>
        </div>

      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { MapPinIcon, PhoneIcon, EnvelopeIcon, ClockIcon } from '@heroicons/vue/24/outline'
import { useBrandLogo } from '@/composables/useBrandLogo'
import SocialIcon from '@/Components/SocialIcon.vue'

const { dark: darkLogo, darkInvert } = useBrandLogo()
const page = usePage()
const site = computed(() => page.props.site || {})
const socials = computed(() => (site.value.social_links || []).filter((s) => s && s.url && s.platform))
const footer = computed(() => page.props.footer || {})
const categoryGroups = computed(() => {
  const tree = page.props.categoryTree || []
  const chosen = Array.isArray(footer.value.footer_categories) ? footer.value.footer_categories : []
  return chosen.length ? tree.filter((c) => chosen.includes(c.slug)) : tree
})

// Bottom bar: copyright (with {year} placeholder) + legal links — editable in admin.
const copyrightText = computed(() => {
  const year = new Date().getFullYear()
  const tpl = footer.value.copyright || `© {year} ${site.value.site_name || 'Kizora'}. All rights reserved.`
  // Prefer the {year} token; if a literal year was typed instead, refresh the
  // last 20xx in the string so it never goes stale (also handles "2020–2026").
  return tpl.includes('{year}')
    ? tpl.replace(/\{year\}/g, year)
    : tpl.replace(/20\d{2}(?![\s\S]*20\d{2})/, year)
})
const legalLinks = computed(() => footer.value.legal_links?.length ? footer.value.legal_links : [
  { label: 'Privacy', url: '/privacy' },
  { label: 'Terms', url: '/terms' },
  { label: 'FAQ', url: '/faq' },
])
const footerLinks = computed(() => footer.value.links?.length ? footer.value.links : [
  { label: 'Home', url: '/' },
  { label: 'Shop', url: '/shop' },
  { label: 'About Us', url: '/about' },
  { label: 'Blog', url: '/blog' },
  { label: 'Contact', url: '/contact' },
  { label: 'Track Order', url: '/track-order' },
])
const telLink = (p) => 'tel:' + String(p || '').replace(/[^+\d]/g, '')
</script>

<style scoped>
@reference "../../css/app.css";
.footer-link {
  @apply text-gray-400 text-xs lg:text-sm hover:text-white transition-all duration-200 hover:translate-x-1 inline-block;
}
.social-icon {
  @apply w-9 h-9 rounded-xl bg-gray-800 hover:bg-primary flex items-center justify-center text-gray-400 hover:text-white transition-all duration-200 hover:scale-105;
}
</style>
