<template>
  <div class="card p-5 sm:p-6">
    <!-- Category -->
    <Link :href="route('shop.index', { category: product.category.slug })"
      class="badge badge-purple mb-3 hover:bg-[#E7E7EA] transition-colors">{{ product.category.name }}</Link>

    <h1 class="font-display text-2xl sm:text-3xl font-bold text-charcoal leading-tight mb-2.5">{{ product.name }}</h1>

    <!-- Rating -->
    <button v-if="reviewCount" type="button" @click="$emit('go-reviews')" class="inline-flex items-center gap-2 mb-4 group">
      <Rating :value="product.average_rating" :count="reviewCount" size="md" />
      <span class="text-xs text-primary group-hover:underline">See reviews</span>
    </button>

    <Price :price="unitRegular" :final="unitFinal" :off="product.discount_percentage" size="lg" class="mb-4" />

    <p class="text-muted leading-relaxed mb-4">{{ product.short_description }}</p>

    <!-- Highlight cards (key benefits, right under the summary) -->
    <ProductHighlights v-if="product.usps?.length && showHighlights" :usps="product.usps" class="mb-5" />

    <!-- Variations (desktop; on mobile the sticky bar's options sheet handles these) -->
    <div v-if="hasVariations" class="hidden md:block mb-5">
      <ProductVariations :product="product" :model-value="selected" @update:model-value="$emit('update:selected', $event)" />
    </div>

    <!-- Delivery availability -->
    <div v-if="availability" class="mb-5">
      <div v-if="availability.status === 'available'"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-green-50 border border-green-200 text-success text-sm font-medium">
        <CheckCircleIcon class="w-4 h-4 shrink-0" />
        Available in your city<span v-if="availability.city_name" class="text-success/70"> · {{ availability.city_name }}</span>
      </div>
      <div v-else-if="availability.status === 'unavailable'"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 border border-red-200 text-error text-sm font-medium">
        <ExclamationTriangleIcon class="w-4 h-4 shrink-0" />
        Not available in your city yet
      </div>
      <Link v-else :href="route('account.profile')"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-purple-50 border border-purple-100 text-ink text-sm font-medium hover:bg-purple-100 transition-colors">
        <MapPinIcon class="w-4 h-4 shrink-0" />
        Select your delivery city to confirm availability
      </Link>
    </div>

    <!-- Stock + SKU (stock reflects the selected variation when it has its own counter) -->
    <div class="flex items-center gap-4 text-sm mb-5">
      <span class="inline-flex items-center gap-1.5 font-medium" :class="effectiveStock > 0 ? 'text-success' : 'text-error'">
        <CheckCircleIcon v-if="effectiveStock > 0" class="w-4 h-4" />
        <ExclamationTriangleIcon v-else class="w-4 h-4" />
        {{ effectiveStock > 0 ? `In stock (${effectiveStock})` : (product.stock > 0 ? 'Out of stock for this option' : 'Out of stock') }}
      </span>
      <span v-if="product.sku" class="text-muted">SKU: {{ product.sku }}</span>
    </div>

    <!-- Quantity + Add to cart (desktop; mobile uses the sticky buy bar) -->
    <div class="hidden md:flex items-center gap-2.5 sm:gap-3 mb-4">
      <div class="flex items-center border-2 border-purple-200 rounded-full overflow-hidden shrink-0">
        <button @click="setQty(qty - 1)" :disabled="qty <= 1" aria-label="Decrease quantity"
          class="px-3.5 py-3 text-ink hover:bg-purple-50 active:bg-purple-100 transition-colors font-bold text-lg leading-none disabled:opacity-30">−</button>
        <span class="px-2 sm:px-4 py-3 font-semibold text-charcoal min-w-10 text-center tabular-nums">{{ qty }}</span>
        <button @click="setQty(qty + 1)" :disabled="qty >= effectiveStock" aria-label="Increase quantity"
          class="px-3.5 py-3 text-ink hover:bg-purple-50 active:bg-purple-100 transition-colors font-bold text-lg leading-none disabled:opacity-30">+</button>
      </div>
      <Button size="lg" block :loading="adding" :disabled="effectiveStock === 0" @click="$emit('add')" class="flex-1 min-w-0">
        <template v-if="added"><CheckCircleIcon class="w-5 h-5" /> Added!</template>
        <template v-else-if="effectiveStock === 0">Out of Stock</template>
        <template v-else><span class="truncate">Add to Cart — ৳{{ total }}</span></template>
      </Button>
    </div>

    <!-- Wishlist + share -->
    <div class="flex items-center gap-5 mb-5">
      <button v-if="auth?.user" @click="$emit('toggle-wishlist')"
        class="inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-error transition-colors">
        <HeartIconSolid v-if="inWishlist" class="w-5 h-5 text-error" />
        <HeartIcon v-else class="w-5 h-5" />
        {{ inWishlist ? 'Saved' : 'Save' }}
      </button>
      <button @click="share" class="inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-ink transition-colors">
        <ShareIcon class="w-5 h-5" /> Share
      </button>
    </div>

    <!-- Assurance -->
    <div class="grid grid-cols-3 gap-2 pt-5 border-t border-purple-100">
      <div v-for="a in assurances" :key="a.label" class="flex flex-col items-center text-center gap-1.5">
        <span class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
          <component :is="a.icon" class="w-5 h-5 text-ink" />
        </span>
        <span class="text-[11px] font-semibold text-charcoal leading-tight">{{ a.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import Rating from '@/Components/ui/Rating.vue'
import Price from '@/Components/ui/Price.vue'
import Button from '@/Components/ui/Button.vue'
import ProductHighlights from '@/Components/product/detail/ProductHighlights.vue'
import ProductVariations from '@/Components/product/detail/ProductVariations.vue'
import { variationGroups as variationGroupsOf, selectionPriceDelta, selectionStock } from '@/utils/variations'
import {
  CheckCircleIcon, ExclamationTriangleIcon, MapPinIcon, HeartIcon, ShareIcon,
} from '@heroicons/vue/24/outline'
import { HeartIcon as HeartIconSolid } from '@heroicons/vue/24/solid'
import { resolveBadgeIcon } from '@/composables/useBadgeIcons'

const props = defineProps({
  product:     { type: Object, required: true },
  availability:{ type: Object, default: null },
  qty:         { type: Number, default: 1 },
  adding:      { type: Boolean, default: false },
  added:       { type: Boolean, default: false },
  inWishlist:  { type: Boolean, default: false },
  selected:    { type: Object, default: () => ({}) }, // { groupIndex: option } — owned by the page
})
const emit = defineEmits(['add', 'toggle-wishlist', 'update:qty', 'go-reviews', 'update:selected'])

const auth  = usePage().props.auth
const toast = useToast()
const ps = computed(() => usePage().props.productSettings || {})
const showHighlights = computed(() => ps.value.show_highlights !== false)

const hasVariations = computed(() => variationGroupsOf(props.product).length > 0)

const reviewCount = computed(() => props.product.review_count ?? props.product.reviews?.length ?? 0)

// Variation-aware price + stock (options may carry a ± price and their own stock)
const priceDelta     = computed(() => selectionPriceDelta(props.selected))
const unitFinal      = computed(() => Math.max(0, Number(props.product.final_price) + priceDelta.value))
const unitRegular    = computed(() => Math.max(0, Number(props.product.price) + priceDelta.value))
const effectiveStock = computed(() => selectionStock(props.product, props.selected))

const total = computed(() => (unitFinal.value * props.qty).toLocaleString())

const setQty = (v) => {
  const n = Math.min(Math.max(1, v), effectiveStock.value || 1)
  emit('update:qty', n)
}

const DEFAULT_ASSURANCES = [
  { label: 'Fast delivery', icon: 'truck' },
  { label: '1-year warranty', icon: 'shield' },
  { label: '7-day returns', icon: 'returns' },
]
const assurances = computed(() =>
  (ps.value.assurances?.length ? ps.value.assurances : DEFAULT_ASSURANCES)
    .map((a) => ({ label: a.label, icon: resolveBadgeIcon(a.icon) }))
)

const share = async () => {
  const url = window.location.href
  if (navigator.share) {
    try { await navigator.share({ title: props.product.name, url }) } catch { /* cancelled */ }
  } else {
    try { await navigator.clipboard.writeText(url); toast.success('Link copied to clipboard') } catch { /* noop */ }
  }
}
</script>
