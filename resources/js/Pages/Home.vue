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

    <!-- New arrivals (deferred) -->
    <Deferred data="newArrivals">
      <template #fallback><RailSkeleton :title="page.new_title" :subtitle="page.new_subtitle" /></template>
      <ProductRail :title="page.new_title" badge="New" :subtitle="page.new_subtitle"
        :products="newArrivals || []" :view-all="route('shop.index', { sort: 'latest' })" />
    </Deferred>

    <!-- Bestsellers grid -->
    <section v-if="featuredProducts.length" class="py-12 sm:py-16 bg-white">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-7">
          <div>
            <h2 class="section-title">{{ page.best_title }}</h2>
            <p class="text-muted mt-1 text-sm sm:text-base">{{ page.best_subtitle }}</p>
          </div>
          <Link :href="route('shop.index')" class="hidden sm:inline-flex text-sm font-semibold text-ink hover:text-accent transition-colors">View all →</Link>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          <ProductCard v-for="p in featuredProducts" :key="p.id" :product="p" />
        </div>
      </div>
    </section>

    <!-- On sale (deferred) -->
    <Deferred data="onSale">
      <template #fallback><RailSkeleton :title="page.sale_title" :subtitle="page.sale_subtitle" tinted /></template>
      <ProductRail v-if="onSale && onSale.length" :title="page.sale_title" badge="Save" :subtitle="page.sale_subtitle"
        :products="onSale" :view-all="route('shop.index', { sale: 1 })" />
    </Deferred>

    <!-- More to explore (deferred) -->
    <Deferred data="popularProducts">
      <template #fallback><RailSkeleton :title="page.more_title" :subtitle="page.more_subtitle" /></template>
      <ProductRail :title="page.more_title" :subtitle="page.more_subtitle"
        :products="popularProducts || []" :view-all="route('shop.index')" />
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
import RailSkeleton from '@/Components/home/RailSkeleton.vue'
import { TruckIcon, ShieldCheckIcon, ArrowPathIcon, CreditCardIcon, SparklesIcon } from '@heroicons/vue/24/outline'

defineProps({
  page:             { type: Object, default: () => ({}) },
  categories:       { type: Array, default: () => [] },
  featuredProducts: { type: Array, default: () => [] },
  newArrivals:      { type: Array, default: () => [] },
  onSale:           { type: Array, default: () => [] },
  popularProducts:  { type: Array, default: () => [] },
})

const icons = { truck: TruckIcon, shield: ShieldCheckIcon, returns: ArrowPathIcon, card: CreditCardIcon, spark: SparklesIcon }
const trustIcon = (k) => icons[k] ?? SparklesIcon
</script>
