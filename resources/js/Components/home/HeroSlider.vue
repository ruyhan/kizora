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
          <span class="pointer-events-none absolute -bottom-20 -left-10 w-72 h-72 rounded-full opacity-20 blur-3xl bg-white"></span>
          <div class="relative z-10 px-7 sm:px-12 lg:px-16 kz-hero-content" :style="contentVars(b)">
            <p v-if="b.eyebrow" class="text-[11px] sm:text-xs font-semibold uppercase tracking-[0.18em] mb-3" :class="b.tone === 'accent' ? 'text-white/90' : 'text-accent'" :style="colorStyle(b.eyebrow_color)">{{ b.eyebrow }}</p>
            <h2 class="font-display leading-[1.02] text-white mb-3 kz-hero-title" :style="titleVars(b)">{{ b.title }}</h2>
            <p v-if="b.subtitle" class="text-white/80 mb-6 kz-hero-desc" :style="descVars(b)">{{ b.subtitle }}</p>
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

// Admin-controlled per-element text colour — overrides the default when set; empty keeps default.
const colorStyle = (color) => (color ? { color } : null)

// Typography controls — emitted as CSS custom properties; a responsive @media block
// in <style> applies mobile/desktop values (falling back to the design default when unset).
const pxVar = (v) => { const n = Number(v); return Number.isFinite(n) && n > 0 ? `${n}px` : null }
const clean = (o) => Object.fromEntries(Object.entries(o).filter(([, v]) => v != null && v !== ''))

const titleVars = (b) => clean({
  color: b.title_color || null,
  '--ts-m': pxVar(b.title_size_mobile),  '--ts-d': pxVar(b.title_size_desktop),
  '--tw-m': b.title_weight_mobile || null, '--tw-d': b.title_weight_desktop || null,
})
const descVars = (b) => clean({
  color: b.subtitle_color || null,
  '--ds-m': pxVar(b.desc_size_mobile),  '--ds-d': pxVar(b.desc_size_desktop),
  '--dw-m': b.desc_weight_mobile || null, '--dw-d': b.desc_weight_desktop || null,
})
const contentVars = (b) => clean({
  '--cw-m': pxVar(b.content_width_mobile), '--cw-d': pxVar(b.content_width_desktop),
})

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

/* Admin-controlled hero typography. CSS vars fall back to the design defaults
   (text-3xl/5xl/6xl title, text-sm/base subtitle, max-w-xl content) when unset. */
.kz-hero-content { max-width: var(--cw-m, 36rem); }
.kz-hero-title   { font-size: var(--ts-m, 1.875rem); font-weight: var(--tw-m, 800); }
.kz-hero-desc    { font-size: var(--ds-m, 0.875rem); font-weight: var(--dw-m, 400); }
@media (min-width: 640px) {
  .kz-hero-content { max-width: var(--cw-d, 36rem); }
  .kz-hero-title   { font-size: var(--ts-d, 3rem);  font-weight: var(--tw-d, 800); }
  .kz-hero-desc    { font-size: var(--ds-d, 1rem);  font-weight: var(--dw-d, 400); }
}
@media (min-width: 1024px) {
  .kz-hero-title   { font-size: var(--ts-d, 3.75rem); }
}
</style>
