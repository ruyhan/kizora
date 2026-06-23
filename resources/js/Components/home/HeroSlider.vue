<template>
  <section v-if="banners.length" class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5 sm:pt-7">
    <Swiper
      :modules="modules"
      :loop="banners.length > 1"
      :autoplay="autoplayCfg"
      :pagination="{ clickable: true }"
      :speed="600"
      class="kz-hero rounded-2xl overflow-hidden"
    >
      <SwiperSlide v-for="(b, i) in banners" :key="i">
        <div class="relative h-[300px] sm:h-[380px] lg:h-[440px] flex items-center overflow-hidden" :class="toneClass(b.tone)">
          <template v-if="b.image || b.mobile_image">
            <picture class="absolute inset-0">
              <source media="(max-width: 639px)" :srcset="b.mobile_image || b.image">
              <img :src="b.image || b.mobile_image" :alt="b.title" class="w-full h-full object-cover">
            </picture>
            <span v-if="overlayStyle(b)" class="absolute inset-0" :style="overlayStyle(b)"></span>
          </template>
          <span class="pointer-events-none absolute -top-16 -right-10 w-72 h-72 rounded-full opacity-25 blur-3xl" :class="blobClass(b.tone)"></span>
          <span class="pointer-events-none absolute -bottom-20 -left-10 w-72 h-72 rounded-full opacity-20 blur-3xl bg-white"></span>
          <div class="relative z-10 px-7 sm:px-12 lg:px-16 max-w-xl">
            <p v-if="b.eyebrow" class="text-[11px] sm:text-xs font-semibold uppercase tracking-[0.18em] mb-3" :class="b.tone === 'accent' ? 'text-white/90' : 'text-accent'">{{ b.eyebrow }}</p>
            <h2 class="font-display font-extrabold leading-[1.02] text-white text-3xl sm:text-5xl lg:text-6xl mb-3">{{ b.title }}</h2>
            <p v-if="b.subtitle" class="text-white/80 text-sm sm:text-base max-w-md mb-6">{{ b.subtitle }}</p>
            <Link v-if="b.cta_label" :href="b.cta_link || '/shop'" class="inline-flex items-center gap-2 h-11 px-6 rounded-xl bg-white text-ink font-semibold text-sm hover:-translate-y-px hover:shadow-lg transition-all">
              {{ b.cta_label }} <ArrowRightIcon class="w-4 h-4" />
            </Link>
          </div>
        </div>
      </SwiperSlide>
    </Swiper>
  </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Pagination } from 'swiper/modules'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ banners: { type: Array, default: () => [] } })

const modules = [Autoplay, Pagination]
const reduced = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
const autoplayCfg = reduced || props.banners.length < 2 ? false : { delay: 5500, disableOnInteraction: false, pauseOnMouseEnter: true }

const toneClass = (tone) => ({ ink: 'bg-ink', slate: 'bg-[#26272C]', accent: 'bg-accent' }[tone] ?? 'bg-ink')
const blobClass = (tone) => (tone === 'accent' ? 'bg-white' : 'bg-accent')

// Per-banner image overlay (admin-controlled): solid | gradient | none, with opacity.
const overlayStyle = (b) => {
  const type = b.overlay_type || 'solid'
  if (type === 'none') return null
  const c1 = b.overlay_color || '#17181B'
  const c2 = b.overlay_color2 || '#17181B'
  const opacity = (b.overlay_opacity ?? 55) / 100
  let background = c1
  if (type === 'gradient') {
    const angle = Number.isFinite(+b.overlay_angle) && b.overlay_angle !== '' ? +b.overlay_angle : 135
    const s1 = Number.isFinite(+b.overlay_stop1) && b.overlay_stop1 !== '' ? +b.overlay_stop1 : 0
    const s2 = Number.isFinite(+b.overlay_stop2) && b.overlay_stop2 !== '' ? +b.overlay_stop2 : 100
    background = `linear-gradient(${angle}deg, ${c1} ${s1}%, ${c2} ${s2}%)`
  }
  return { background, opacity }
}
</script>

<style scoped>
.kz-hero :deep(.swiper-pagination-bullet) { background: rgba(255,255,255,0.5); opacity: 1; width: 8px; height: 8px; transition: width .25s ease, background .25s ease; }
.kz-hero :deep(.swiper-pagination-bullet-active) { background: #fff !important; width: 22px; border-radius: 9999px; }
</style>
