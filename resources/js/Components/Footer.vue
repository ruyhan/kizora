<template>
  <footer class="relative z-[1] bg-charcoal text-white pb-20 md:pb-0">
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
          <div class="flex gap-3">
            <a :href="site.facebook || '#'" target="_blank" rel="noopener" class="social-icon" aria-label="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
            <a :href="site.instagram || '#'" target="_blank" rel="noopener" class="social-icon" aria-label="Instagram">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
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
            <li class="flex gap-3 text-gray-400 text-sm">
              <MapPinIcon class="w-5 h-5 text-accent shrink-0 mt-0.5" />
              <span class="leading-relaxed">{{ site.address }}</span>
            </li>
            <li class="flex gap-3 text-gray-400 text-sm items-start">
              <PhoneIcon class="w-5 h-5 text-accent shrink-0 mt-0.5" />
              <div>
                <a v-if="site.phone_1" :href="telLink(site.phone_1)" class="block hover:text-white transition-colors">{{ site.phone_1 }}</a>
                <a v-if="site.email" :href="`mailto:${site.email}`" class="block hover:text-white transition-colors mt-1">{{ site.email }}</a>
              </div>
            </li>
            <li class="flex gap-3 text-gray-400 text-sm items-center">
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
            © {{ new Date().getFullYear() }} {{ site.site_name }}
          </p>
          <p class="text-purple-400 text-xs text-center">
            {{ footer.tagline }}
          </p>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <Link :href="route('privacy')" class="hover:text-white transition-colors">Privacy</Link>
            <span>·</span>
            <Link :href="route('terms')" class="hover:text-white transition-colors">Terms</Link>
            <span>·</span>
            <Link :href="route('faq')" class="hover:text-white transition-colors">FAQ</Link>
          </div>
        </div>

        <!-- Desktop layout: left-right -->
        <div class="hidden md:flex items-center justify-between gap-4">
          <p class="text-gray-500 text-sm">
            © {{ new Date().getFullYear() }} {{ site.site_name }}. All rights reserved. ·
            <span class="text-purple-400">{{ footer.tagline }}</span>
          </p>
          <div class="flex gap-6 text-sm">
            <Link :href="route('privacy')" class="text-gray-500 hover:text-white transition-colors">Privacy</Link>
            <Link :href="route('terms')" class="text-gray-500 hover:text-white transition-colors">Terms</Link>
            <Link :href="route('faq')" class="text-gray-500 hover:text-white transition-colors">FAQ</Link>
          </div>
        </div>

      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { MapPinIcon, PhoneIcon, ClockIcon } from '@heroicons/vue/24/outline'
import { useBrandLogo } from '@/composables/useBrandLogo'

const { dark: darkLogo, darkInvert } = useBrandLogo()
const page = usePage()
const site = computed(() => page.props.site || {})
const footer = computed(() => page.props.footer || {})
const categoryGroups = computed(() => page.props.categoryTree || [])
const footerLinks = computed(() => footer.value.links?.length ? footer.value.links : [
  { label: 'Home', url: '/' },
  { label: 'Shop', url: '/shop' },
  { label: 'About Us', url: '/about' },
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
