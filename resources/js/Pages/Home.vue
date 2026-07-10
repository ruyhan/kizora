<template>
  <AppLayout>
    <Head title="Carry your world" />

    <!-- Hero box slider -->
    <HeroSlider :banners="page.hero || []" />

    <!-- Slim trust strip -->
    <div v-if="page.trust?.length" class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
      <div class="grid grid-cols-2 lg:grid-cols-4 rounded-2xl border border-border overflow-hidden">
        <div v-for="(t, i) in page.trust" :key="i"
          class="flex items-center gap-3 px-4 py-4 border-border"
          :class="[
            i % 2 === 1 ? 'border-l' : '',
            i >= 2 ? 'border-t' : '',
            'lg:border-t-0',
            i % 4 !== 0 ? 'lg:border-l' : '',
          ]">
          <component :is="trustIcon(t.icon)" class="w-5 h-5 text-ink shrink-0" />
          <div class="min-w-0">
            <p class="text-xs sm:text-sm font-semibold text-ink leading-tight">{{ t.title }}</p>
            <p class="text-[11px] text-muted truncate">{{ t.desc }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Category tiles -->
    <CategoryTiles :categories="categories" />

    <!-- Product sections — admin-managed (Pages & Content → Home page), rendered in admin order.
         Headings paint immediately via sectionHeads; products stream in (deferred). -->
    <Deferred data="sections">
      <template #fallback>
        <RailSkeleton v-for="(h, i) in sectionHeads" :key="i" :title="h.title" :subtitle="h.subtitle" />
      </template>
      <template v-for="(s, i) in sections || []" :key="i">
        <!-- Grid layout -->
        <section v-if="s.layout === 'grid' && s.products.length" class="py-12 sm:py-16 bg-white">
          <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-7">
              <div>
                <div class="flex items-center gap-2.5">
                  <h2 class="section-title">{{ s.title }}</h2>
                  <span v-if="s.badge" class="badge badge-accent">{{ s.badge }}</span>
                </div>
                <p v-if="s.subtitle" class="text-muted mt-1 text-sm sm:text-base">{{ s.subtitle }}</p>
              </div>
              <Link :href="s.view_all" class="hidden sm:inline-flex text-sm font-semibold text-ink hover:text-accent transition-colors">View all →</Link>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
              <ProductCard v-for="p in s.products" :key="p.id" :product="p" />
            </div>
          </div>
        </section>
        <!-- Carousel rail layout (hides itself when the section has no products) -->
        <ProductRail v-else-if="s.layout !== 'grid'" :title="s.title" :badge="s.badge" :subtitle="s.subtitle"
          :products="s.products" :view-all="s.view_all" />
      </template>
    </Deferred>

    <!-- Blog slider — bottom of the page, just above the footer (admin-managed, deferred) -->
    <Deferred v-if="page.blog_enabled" data="blogPosts">
      <template #fallback><RailSkeleton :title="page.blog_title" :subtitle="page.blog_subtitle" tinted /></template>
      <BlogRail :title="page.blog_title" :subtitle="page.blog_subtitle" :posts="blogPosts || []" />
    </Deferred>
  </AppLayout>
</template>

<script setup>
import { Head, Link, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import HeroSlider from '@/Components/home/HeroSlider.vue'
import CategoryTiles from '@/Components/home/CategoryTiles.vue'
import ProductRail from '@/Components/home/ProductRail.vue'
import BlogRail from '@/Components/home/BlogRail.vue'
import RailSkeleton from '@/Components/home/RailSkeleton.vue'
import { TruckIcon, ShieldCheckIcon, ArrowPathIcon, CreditCardIcon, SparklesIcon } from '@heroicons/vue/24/outline'

defineProps({
  page:         { type: Object, default: () => ({}) },
  categories:   { type: Array, default: () => [] },
  sectionHeads: { type: Array, default: () => [] },
  sections:     { type: Array, default: () => [] },
  blogPosts:    { type: Array, default: () => [] },
})

const icons = { truck: TruckIcon, shield: ShieldCheckIcon, returns: ArrowPathIcon, card: CreditCardIcon, spark: SparklesIcon }
const trustIcon = (k) => icons[k] ?? SparklesIcon
</script>
