<template>
  <AccountLayout>
    <Head title="My Wishlist" />

    <div class="space-y-5">

      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">My Wishlist</h1>
        <p class="text-muted text-sm mt-1">Products you've saved for later</p>
      </div>

      <div v-if="wishlists?.length" class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-purple-50">
          <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center">
            <HeartIcon class="w-4.5 h-4.5 text-rose-500" />
          </div>
          <h3 class="font-display font-semibold text-charcoal">Saved Items</h3>
          <span class="ml-auto text-xs text-muted font-medium">{{ wishlists.length }} item{{ wishlists.length !== 1 ? 's' : '' }}</span>
        </div>
        <div class="p-5">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-4">
            <ProductCard
              v-for="item in wishlists"
              :key="item.id"
              :product="item.product"
              :wishlistedIds="wishlistedIds"
            />
          </div>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-sm border border-purple-100/50 px-6 py-20 text-center">
        <div class="w-20 h-20 rounded-2xl bg-rose-100 flex items-center justify-center mx-auto mb-5">
          <HeartIcon class="w-10 h-10 text-rose-400" />
        </div>
        <h2 class="font-display text-xl font-bold text-charcoal mb-2">Nothing saved yet</h2>
        <p class="text-muted text-sm mb-6">Tap the heart on any product to save it here.</p>
        <Link :href="route('shop.index')" class="btn-primary text-sm py-2.5 px-6">Browse Products</Link>
      </div>

    </div>
  </AccountLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import { HeartIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ wishlists: Array })

const wishlistedIds = computed(() => props.wishlists?.map(w => w.product.id) ?? [])
</script>
