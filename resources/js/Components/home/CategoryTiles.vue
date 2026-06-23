<template>
  <section v-if="categories.length" class="py-12 sm:py-16">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-end justify-between mb-7">
        <div>
          <h2 class="section-title">Shop by category</h2>
          <p class="text-muted mt-1 text-sm sm:text-base">Find the right carry for school, work, sport &amp; travel.</p>
        </div>
        <Link :href="route('shop.index')" class="hidden sm:inline-flex text-sm font-semibold text-ink hover:text-accent transition-colors">All products →</Link>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-5">
        <Link v-for="c in categories" :key="c.id" :href="route('shop.index', { category: c.slug })" prefetch
          class="group relative overflow-hidden flex flex-col justify-between rounded-3xl border border-border bg-surface p-5 sm:p-7 min-h-36 sm:min-h-48 hover:border-ink hover:-translate-y-1 hover:shadow-xl hover:shadow-ink/5 transition-all duration-200">
          <CategoryIcon :slug="c.slug" :icon="c.icon" class="absolute -bottom-7 -right-5 w-28 h-28 sm:w-36 sm:h-36 text-ink opacity-[0.05] group-hover:opacity-[0.09] group-hover:scale-110 transition-all duration-300 pointer-events-none" />
          <span class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-[#F4F4F5] group-hover:bg-ink flex items-center justify-center overflow-hidden transition-colors duration-200">
            <img v-if="c.image_url" :src="c.image_url" :alt="c.name" class="w-full h-full object-cover" loading="lazy">
            <CategoryIcon v-else :slug="c.slug" :icon="c.icon" class="w-6 h-6 sm:w-7 sm:h-7 text-ink group-hover:text-white transition-colors duration-200" />
          </span>
          <div class="relative mt-5">
            <span class="block font-display font-bold text-base sm:text-xl text-charcoal leading-tight">{{ c.name }}</span>
            <span class="text-xs sm:text-sm text-muted">{{ c.products_count }} items</span>
          </div>
          <span class="absolute top-5 right-5 w-8 h-8 rounded-full bg-[#F4F4F5] group-hover:bg-ink flex items-center justify-center transition-colors duration-200">
            <ArrowRightIcon class="w-4 h-4 text-ink group-hover:text-white transition-colors duration-200" />
          </span>
        </Link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import CategoryIcon from '@/Components/icons/CategoryIcon.vue'

defineProps({ categories: { type: Array, default: () => [] } })
</script>
