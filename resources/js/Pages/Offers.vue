<template>
  <AppLayout>
    <Head title="Offers & Deals">
      <meta name="description" content="All current offers — grab our best deals while they last." head-key="description">
    </Head>

    <!-- Hero strip -->
    <div class="bg-ink text-white">
      <div class="site-container py-10 sm:py-14 text-center">
        <p class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.2em] text-white/70 mb-3">
          <FireIcon class="w-4 h-4" /> Limited-time deals
        </p>
        <h1 class="font-display text-3xl sm:text-5xl font-bold mb-3">Offers &amp; Deals</h1>
        <p class="text-white/70 max-w-xl mx-auto text-sm sm:text-base">
          Every product below is on sale right now — prices go back up when the sale ends.
        </p>
      </div>
    </div>

    <div class="site-container py-10 sm:py-14">
      <!-- Deals grid -->
      <div v-if="products.data?.length">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
          <div v-for="p in products.data" :key="p.id" class="relative">
            <ProductCard :product="p" />
            <p v-if="p.sale_ends_at" class="mt-1.5 text-center text-[11px] text-muted">
              <ClockIcon class="w-3 h-3 inline -mt-0.5" /> Ends {{ p.sale_ends_at }}
            </p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="products.last_page > 1" class="flex justify-center gap-2 mt-10">
          <template v-for="link in products.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" preserve-scroll
              :class="link.active ? 'bg-ink text-white' : 'bg-white text-charcoal hover:bg-purple-50'"
              class="px-4 py-2 rounded-xl text-sm font-medium border border-border transition-colors"
              v-html="link.label" />
          </template>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-16">
        <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-4">
          <TagIcon class="w-8 h-8 text-primary/40" />
        </div>
        <h2 class="font-display text-xl font-bold text-charcoal mb-2">No active offers right now</h2>
        <p class="text-muted text-sm mb-6">Check back soon — new deals are added regularly.</p>
        <Link :href="route('shop.index')" class="btn-primary px-6 py-3 text-sm">Browse the shop</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import { FireIcon, TagIcon, ClockIcon } from '@heroicons/vue/24/outline'

defineProps({
  products: Object, // paginator of on-sale products
})
</script>
