<template>
  <div class="card group relative flex flex-col overflow-hidden h-full">

    <!-- Discount Badge -->
    <div v-if="product.discount_percentage > 0"
      class="absolute top-2 left-2 z-10 badge badge-error font-bold text-[10px] sm:text-xs shadow-sm">
      -{{ product.discount_percentage }}%
    </div>

    <!-- Wishlist Button -->
    <button v-if="auth?.user" @click.prevent="toggleWishlist"
      class="absolute top-2 right-2 z-10 w-7 h-7 sm:w-9 sm:h-9 rounded-full bg-white/90 backdrop-blur-sm shadow-md flex items-center justify-center transition-all duration-200 active:scale-90"
      :class="inWishlist ? 'text-rose-500 bg-rose-50' : 'text-gray-400 hover:text-rose-500 hover:bg-rose-50'">
      <HeartSolidIcon v-if="inWishlist" class="w-3 h-3 sm:w-4 sm:h-4" />
      <HeartIcon v-else class="w-3 h-3 sm:w-4 sm:h-4" />
    </button>

    <!-- Product Image -->
    <div class="relative overflow-hidden aspect-square bg-purple-50 shrink-0">
      <Link :href="route('shop.show', product.slug)" prefetch class="block w-full h-full">
        <img
          :src="displayImage || '/images/product-placeholder.svg'"
          :alt="product.name"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          loading="lazy"
        >
      </Link>
      <!-- Quick view (desktop hover) -->
      <button @click.prevent.stop="quickView"
        class="hidden md:flex absolute inset-x-2 bottom-2 h-9 items-center justify-center gap-1.5 rounded-lg bg-surface/95 backdrop-blur text-ink text-xs font-semibold border border-border opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-200">
        <EyeIcon class="w-4 h-4" /> Quick view
      </button>
    </div>

    <!-- Product Info -->
    <div class="p-2.5 sm:p-4 flex flex-col flex-1">

      <!-- Rating (left) + category (right) on a single row -->
      <div class="flex items-center justify-between gap-2 mb-2">
        <Rating :value="product.average_rating || 0" :count="product.review_count || 0" size="sm" class="shrink-0" />
        <span v-if="product.category?.name" class="hidden sm:inline-flex badge badge-purple text-xs shrink-0">
          {{ product.category?.name }}
        </span>
      </div>

      <!-- Product Name -->
      <Link :href="route('shop.show', product.slug)"
        class="font-display font-semibold text-charcoal text-[13px] sm:text-base lg:text-lg leading-tight hover:text-primary transition-colors mb-2 line-clamp-2">
        {{ product.name }}
      </Link>

      <!-- Colour variations — click swaps the image instantly -->
      <div v-if="colorOptions.length" class="flex items-center gap-1.5 mb-2 flex-wrap">
        <button v-for="(v, i) in colorOptions" :key="i" type="button" @click.prevent.stop="activeVariation = v"
          :style="{ backgroundColor: v.value }"
          :class="activeVariation === v ? 'ring-2 ring-ink ring-offset-1' : 'ring-1 ring-border hover:ring-purple-300'"
          class="w-5 h-5 rounded-full shrink-0 transition-all" :aria-label="`Colour ${v.value}`" :title="v.value"></button>
      </div>

      <!-- Price + Cart Action — single row (price left, button right) -->
      <div class="mt-auto pt-2 sm:pt-3 border-t border-purple-50 flex items-center justify-between gap-2">

        <!-- Price (discount badge already shown on the image) -->
        <Price :price="product.price" :final="product.final_price" :off="0" size="sm" class="min-w-0" />

        <!-- ── Cart Action — fixed height h-9/h-10 prevents layout shift ── -->
        <Transition name="cart-action" mode="out-in">

          <!-- IN CART: simple "Added" state (quantity managed in the cart) -->
          <div v-if="inCart" key="in-cart"
            class="shrink-0 flex items-center justify-center gap-1.5 h-9 sm:h-10 px-3 sm:px-4 rounded-full border-2 border-green-200 bg-green-50 text-success font-semibold text-[11px] sm:text-sm">
            <CheckCircleIcon class="w-4 h-4 shrink-0" />
            <span class="hidden sm:inline">Added</span>
          </div>

          <!-- NOT IN CART: Add to Cart / Loading / Sold Out (icon-only on mobile) -->
          <button v-else key="add-btn"
            @click.prevent="addToCart"
            :disabled="product.stock === 0 || adding"
            aria-label="Add to cart"
            class="shrink-0 btn-primary px-3 sm:px-4 text-[11px] sm:text-sm
                   disabled:opacity-50 disabled:cursor-not-allowed
                   flex items-center justify-center gap-1 sm:gap-1.5
                   active:scale-95 transition-transform h-9 sm:h-10">

            <span v-if="adding" class="flex items-center gap-1.5">
              <span class="w-3 h-3 border-2 border-white/70 border-t-white rounded-full animate-spin shrink-0 block"></span>
              <span class="hidden sm:inline">Adding…</span>
            </span>
            <span v-else-if="product.stock === 0">Sold Out</span>
            <span v-else class="flex items-center gap-1 sm:gap-1.5">
              <ShoppingBagIcon class="w-4 h-4 sm:w-3.5 sm:h-3.5 shrink-0" />
              <span class="hidden sm:inline">Add to Cart</span>
            </span>

          </button>

        </Transition>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart'
import Price from '@/Components/ui/Price.vue'
import Rating from '@/Components/ui/Rating.vue'
import { colorGroup } from '@/utils/variations'
import {
  HeartIcon, ShoppingBagIcon, MinusIcon, PlusIcon, CheckCircleIcon, EyeIcon,
} from '@heroicons/vue/24/outline'
import { HeartIcon as HeartSolidIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  product:       { type: Object, required: true },
  wishlistedIds: { type: Array,  default: () => [] },
})

const page      = usePage()
const auth      = page.props.auth
const toast     = useToast()
const cartStore = useCartStore()

const adding     = ref(false)
const decreasing = ref(false)
const increasing = ref(false)

// ── Variations (colour) — instant client-side image swap ──
const colorOptions = computed(() => colorGroup(props.product)?.options || [])
const activeVariation = ref(null)
const displayImage = computed(() => activeVariation.value?.image || props.product.featured_image_url)

const cartItem = computed(() => cartStore.getItemByProductId(props.product.id))
const inCart   = computed(() => !!cartItem.value)
const cartQty  = computed(() => cartItem.value?.quantity ?? 0)
const inWishlist = computed(() => props.wishlistedIds.includes(props.product.id))

const quickView = () => window.dispatchEvent(new CustomEvent('kz-quickview', { detail: props.product }))

onMounted(() => cartStore.ensureLoaded())

const addToCart = async () => {
  if (adding.value || props.product.stock === 0) return
  adding.value = true
  try {
    await cartStore.add(props.product.id, 1)
    toast.success(`${props.product.name} added to cart`)
  } catch {
    toast.error('Could not add to cart. Please try again.')
  } finally {
    adding.value = false
  }
}

const decrease = async () => {
  if (decreasing.value || !cartItem.value) return
  decreasing.value = true
  try {
    if (cartQty.value <= 1) {
      await cartStore.removeItem(cartItem.value.id)
      toast.success('Removed from cart')
    } else {
      await cartStore.updateItem(cartItem.value.id, cartQty.value - 1)
    }
  } catch {
    toast.error('Could not update cart.')
  } finally {
    decreasing.value = false
  }
}

const increase = async () => {
  if (increasing.value || !cartItem.value || cartQty.value >= props.product.stock) return
  increasing.value = true
  try {
    await cartStore.updateItem(cartItem.value.id, cartQty.value + 1)
  } catch {
    toast.error('Could not update cart.')
  } finally {
    increasing.value = false
  }
}

const toggleWishlist = () => {
  router.post(route('wishlist.toggle'), { product_id: props.product.id }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(inWishlist.value ? 'Removed from wishlist' : 'Added to wishlist!')
    },
  })
}
</script>

<style scoped>
.cart-action-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.cart-action-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.cart-action-enter-from   { opacity: 0; transform: scale(0.96) translateY(4px); }
.cart-action-enter-to     { opacity: 1; transform: scale(1) translateY(0); }
.cart-action-leave-from   { opacity: 1; transform: scale(1) translateY(0); }
.cart-action-leave-to     { opacity: 0; transform: scale(0.96) translateY(-4px); }
</style>
