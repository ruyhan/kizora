<template>
  <AppLayout>
    <Head :title="product.name" />

    <div class="site-container py-8 sm:py-12">

      <!-- Breadcrumb -->
      <nav class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm text-muted mb-6 sm:mb-8 overflow-hidden">
        <Link :href="route('home')" class="hover:text-ink transition-colors shrink-0">Home</Link>
        <span class="shrink-0">›</span>
        <Link :href="route('shop.index')" class="hover:text-ink transition-colors shrink-0">Shop</Link>
        <span class="shrink-0 hidden sm:inline">›</span>
        <Link :href="route('shop.index', { category: product.category.slug })" class="hover:text-ink transition-colors shrink-0 hidden sm:inline">{{ product.category.name }}</Link>
        <span class="shrink-0">›</span>
        <span class="text-charcoal font-medium truncate">{{ product.name }}</span>
      </nav>

      <!-- Top: gallery + sticky buy box -->
      <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start mb-12 sm:mb-16">
        <div class="lg:col-span-7 min-w-0">
          <ProductGallery :product="product" :override-image="variationImage" />
        </div>
        <div class="lg:col-span-5 min-w-0 lg:sticky lg:top-28 self-start">
          <ProductBuyBox
            :product="product" :availability="availability"
            v-model:qty="qty" :adding="adding" :added="added" :in-wishlist="inWishlist"
            @add="addToCart" @toggle-wishlist="toggleWishlist" @go-reviews="scrollTo('reviews')"
            @select-variation="variationImage = $event" @variation-change="selectedVariations = $event" />
        </div>
      </div>

      <!-- Sections -->
      <div class="space-y-14 sm:space-y-20 pt-10 sm:pt-12">
        <section v-if="descriptionParagraphs.length && showDescription" id="details" class="scroll-mt-40">
          <SectionHeading :title="ps.description_title || 'Description'" />
          <div class="card p-6 sm:p-8 text-muted leading-relaxed text-sm sm:text-base space-y-4 w-full">
            <p v-for="(para, i) in descriptionParagraphs" :key="i">{{ para }}</p>
          </div>
        </section>

        <section v-if="showReviews" id="reviews" class="scroll-mt-40">
          <ProductReviews :product="product" />
        </section>
      </div>

      <!-- Related -->
      <div v-if="related?.length && showRelated" class="mt-16 sm:mt-24">
        <SectionHeading :title="ps.related_title || 'You may also like'" />
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
          <ProductCard v-for="p in related" :key="p.id" :product="p" />
        </div>
      </div>

      <!-- clearance for mobile sticky bar + bottom nav -->
      <div class="h-24 md:hidden"></div>
    </div>

    <!-- Mobile sticky add-to-cart -->
    <div class="md:hidden fixed inset-x-0 bottom-16 z-40 bg-surface/95 backdrop-blur border-t border-border px-4 py-3">
      <div class="flex items-center gap-2.5">
        <div class="shrink-0">
          <p class="text-[10px] text-muted leading-none uppercase tracking-wide">Total</p>
          <p class="text-lg font-bold text-ink leading-tight">৳{{ (product.final_price * qty).toLocaleString() }}</p>
        </div>
        <div class="flex items-center border border-border rounded-xl overflow-hidden shrink-0">
          <button @click="qty > 1 ? qty-- : null" aria-label="Decrease" class="px-3 py-2 text-ink active:bg-[#F1F1F2] disabled:opacity-30" :disabled="qty <= 1">−</button>
          <span class="px-1.5 text-sm font-semibold tabular-nums min-w-6 text-center">{{ qty }}</span>
          <button @click="qty < product.stock ? qty++ : null" aria-label="Increase" class="px-3 py-2 text-ink active:bg-[#F1F1F2] disabled:opacity-30" :disabled="qty >= product.stock">+</button>
        </div>
        <Button size="md" :loading="adding" :disabled="product.stock === 0" @click="addToCart" class="flex-1">
          {{ product.stock === 0 ? 'Out of stock' : (added ? 'Added!' : 'Add to cart') }}
        </Button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import SectionHeading from '@/Components/ui/SectionHeading.vue'
import Button from '@/Components/ui/Button.vue'
import ProductGallery from '@/Components/product/detail/ProductGallery.vue'
import ProductBuyBox from '@/Components/product/detail/ProductBuyBox.vue'
import ProductReviews from '@/Components/product/detail/ProductReviews.vue'
import { variationGroups } from '@/utils/variations'
import { useRecentlyViewed } from '@/composables/useRecentlyViewed'
import { useAnalytics } from '@/composables/useAnalytics'
import { useCartStore } from '@/stores/cart'

const props = defineProps({
  product:      Object,
  related:      Array,
  availability: { type: Object, default: null },
})

const toast     = useToast()
const cartStore = useCartStore()

// ── Variation selection ──
const variationImage = ref(null)              // selected colour image (gallery swap)
const selectedVariations = ref([])            // [{ name, type, value, label, image }]
const requiredGroups = computed(() => variationGroups(props.product))

// ── Cart / quantity state ──
const qty    = ref(1)
const adding = ref(false)
const added  = ref(false)
let addedTimer = null

const addToCart = async () => {
  if (adding.value || props.product.stock === 0) return

  // Require a choice for every variation group before adding.
  if (requiredGroups.value.length && selectedVariations.value.length < requiredGroups.value.length) {
    const chosen = selectedVariations.value.map((v) => v.name)
    const missing = requiredGroups.value.map((g) => g.name).filter((n) => !chosen.includes(n))
    toast.error(`Please select ${missing.join(' & ')}.`)
    return
  }

  adding.value = true
  try {
    await cartStore.add(props.product.id, qty.value, selectedVariations.value)
    toast.success(`${props.product.name} added to cart`)
    added.value = true
    clearTimeout(addedTimer)
    addedTimer = setTimeout(() => { added.value = false }, 1800)
  } catch {
    toast.error('Could not add to cart.')
  } finally {
    adding.value = false
  }
}

// ── Wishlist ──
const inWishlist = ref(false)
const toggleWishlist = () => {
  router.post(route('wishlist.toggle'), { product_id: props.product.id }, {
    preserveScroll: true,
    onSuccess: () => {
      inWishlist.value = !inWishlist.value
      toast.success(inWishlist.value ? 'Added to wishlist!' : 'Removed from wishlist.')
    },
  })
}

// ── Content + product settings (Admin → Settings → Product Page) ──
const descriptionParagraphs = computed(() =>
  (props.product.description || '').split(/\n+/).map((p) => p.trim()).filter(Boolean)
)

const ps = computed(() => usePage().props.productSettings || {})
const showDescription = computed(() => ps.value.show_description !== false)
const showReviews     = computed(() => ps.value.show_reviews !== false)
const showRelated     = computed(() => ps.value.show_related !== false)

const scrollTo = (id) => {
  const el = document.getElementById(id)
  if (!el) return
  window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 150, behavior: 'smooth' })
}

// ── Recently viewed + analytics + JSON-LD ──
const { track } = useRecentlyViewed()
let jsonLdEl = null
onMounted(() => {
  track(props.product)
  useAnalytics().push('view_item', { item_id: props.product.id, item_name: props.product.name, price: props.product.final_price })
  const p = props.product
  const graph = [
    {
      '@context': 'https://schema.org', '@type': 'Product',
      name: p.name, image: [p.featured_image_url], description: p.short_description || p.description, sku: p.sku,
      brand: { '@type': 'Brand', name: 'Kizora' },
      offers: {
        '@type': 'Offer', priceCurrency: 'BDT', price: p.final_price,
        availability: p.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        url: window.location.href,
      },
      ...(p.review_count ? { aggregateRating: { '@type': 'AggregateRating', ratingValue: p.average_rating, reviewCount: p.review_count } } : {}),
    },
    {
      '@context': 'https://schema.org', '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Shop', item: route('shop.index') },
        { '@type': 'ListItem', position: 2, name: p.category?.name, item: route('shop.index', { category: p.category?.slug }) },
        { '@type': 'ListItem', position: 3, name: p.name },
      ],
    },
  ]
  jsonLdEl = document.createElement('script')
  jsonLdEl.type = 'application/ld+json'
  jsonLdEl.text = JSON.stringify(graph)
  document.head.appendChild(jsonLdEl)
})
onUnmounted(() => {
  clearTimeout(addedTimer)
  if (jsonLdEl) { jsonLdEl.remove(); jsonLdEl = null }
})
</script>
