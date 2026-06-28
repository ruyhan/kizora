<template>
  <div class="card group relative flex flex-col overflow-hidden h-full" style="background-color: #f6f6f6;">

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
        <Rating :value="product.average_rating || 0" :count="product.review_count || 0" size="sm" tone="ink" class="shrink-0" />
        <span v-if="product.category?.name" class="hidden sm:inline-flex badge bg-white border border-border text-charcoal text-xs shrink-0">
          {{ product.category?.name }}
        </span>
      </div>

      <!-- Product Name + SKU -->
      <div class="mb-2">
        <Link :href="route('shop.show', product.slug)"
          class="block font-display font-semibold text-charcoal text-[15px] sm:text-base lg:text-lg leading-tight hover:text-primary transition-colors line-clamp-2">
          {{ product.name }}
        </Link>
        <p v-if="product.sku" class="mt-0.5 text-muted text-[13px] sm:text-sm font-display uppercase tracking-wide">
          {{ product.sku }}
        </p>
      </div>

      <!-- Colour variations — click selects & swaps the image instantly -->
      <div v-if="colorGroupData" class="flex items-center gap-2 mb-2.5 flex-wrap">
        <button v-for="(o, oi) in colorGroupData.options" :key="oi" type="button"
          @click.prevent.stop="selectOption(colorGi, o)"
          :style="{ backgroundColor: o.value }"
          :class="selectedMap[colorGi] === o ? 'ring-2 ring-ink ring-offset-2 ring-offset-[#f6f6f6] scale-110' : 'hover:scale-110'"
          class="w-[22px] h-[22px] sm:w-[26px] sm:h-[26px] rounded-full shrink-0 border-2 sm:border-[3px] border-white shadow-[0_2px_6px_rgba(23,24,27,0.25)] transition-all duration-200 cursor-pointer"
          :aria-label="`Colour ${o.label || o.value}`" :title="o.label || o.value"></button>
      </div>

      <!-- Other option groups (size, material, …) — selectable chips, like the colours -->
      <div v-for="g in optionGroups" :key="g._gi" class="flex items-center gap-1 mb-2 flex-wrap">
        <button v-for="(o, oi) in g.options.slice(0, 6)" :key="oi" type="button"
          @click.prevent.stop="selectOption(g._gi, o)"
          :class="selectedMap[g._gi] === o ? 'bg-ink text-white border-ink' : 'bg-white border-border text-charcoal hover:border-ink/40'"
          class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-md border text-[10px] font-semibold leading-none transition-colors cursor-pointer">
          {{ o.value }}
        </button>
        <span v-if="g.options.length > 6" class="text-[10px] text-muted font-medium">+{{ g.options.length - 6 }}</span>
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
import { variationGroups, buildSelectedVariations, variationSignature } from '@/utils/variations'
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

// ── Variations — selectable on the card; the selection is carried into the cart ──
const groups = computed(() => variationGroups(props.product))
const colorGi = computed(() => groups.value.findIndex((g) => g.type === 'color'))
const colorGroupData = computed(() => (colorGi.value >= 0 ? groups.value[colorGi.value] : null))
// Non-colour groups (size, material, …) keep their original group index as `_gi`.
const optionGroups = computed(() =>
  groups.value.map((g, gi) => ({ ...g, _gi: gi })).filter((g) => g.type !== 'color')
)

const selectedMap = ref({})                       // { groupIndex: option }
const selectOption = (gi, o) => { selectedMap.value = { ...selectedMap.value, [gi]: o } }
const selectedVariations = computed(() => buildSelectedVariations(props.product, selectedMap.value))

// Selecting a colour (or any option carrying an image) swaps the card image.
const displayImage = computed(() =>
  Object.values(selectedMap.value).find((o) => o?.image)?.image || props.product.featured_image_url
)

// "Added" state is per-variation: a line matches only when the product AND the
// currently-selected variation combination are the same — so each distinct
// selection can be added separately and shows its own Added state.
const selectedSignature = computed(() => variationSignature(selectedVariations.value))
const cartItem = computed(() =>
  cartStore.items.find(
    (i) => i.product_id === props.product.id && variationSignature(i.variations) === selectedSignature.value,
  ) ?? null,
)
const inCart   = computed(() => !!cartItem.value)
const cartQty  = computed(() => cartItem.value?.quantity ?? 0)
const inWishlist = computed(() => props.wishlistedIds.includes(props.product.id))

const quickView = () => window.dispatchEvent(new CustomEvent('kz-quickview', { detail: props.product }))

onMounted(() => cartStore.ensureLoaded())

const addToCart = async () => {
  if (adding.value || props.product.stock === 0) return
  // Require a choice for every variation group (mirrors the product page).
  if (groups.value.length && selectedVariations.value.length < groups.value.length) {
    const chosen = selectedVariations.value.map((v) => v.name)
    const missing = groups.value.map((g) => g.name).filter((n) => !chosen.includes(n))
    toast.error(`Please select ${missing.join(' & ')}.`)
    return
  }
  adding.value = true
  try {
    await cartStore.add(props.product.id, 1, selectedVariations.value)
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
