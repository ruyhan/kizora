<template>
  <div class="md:hidden">
    <!-- ── Options bottom sheet (variations + qty) ── -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="sheet" class="fixed inset-0 z-[82] bg-black/45 backdrop-blur-sm" @click="sheet = false"></div>
      </Transition>
      <Transition name="kz-sheet">
        <div v-if="sheet" class="fixed inset-x-0 bottom-0 z-[83] bg-surface rounded-t-3xl shadow-2xl max-h-[82vh] flex flex-col pb-[max(1rem,env(safe-area-inset-bottom))]">
          <div class="flex items-center justify-between px-5 pt-4 pb-3 shrink-0">
            <h3 class="font-display font-bold text-charcoal">Choose options</h3>
            <button @click="sheet = false" aria-label="Close" class="w-9 h-9 rounded-full hover:bg-[#F1F1F2] flex items-center justify-center text-muted"><XMarkIcon class="w-5 h-5" /></button>
          </div>

          <div class="px-5 overflow-y-auto">
            <!-- Product mini -->
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-border">
              <img :src="image || product.featured_image_url" class="w-16 h-16 rounded-2xl object-cover bg-[#F4F4F5] shrink-0" alt="">
              <div class="min-w-0">
                <p class="font-semibold text-charcoal text-sm truncate">{{ product.name }}</p>
                <Price :price="unitRegular" :final="unitFinal" :off="product.discount_percentage" size="md" class="mt-0.5" />
              </div>
            </div>

            <ProductVariations :product="product" :model-value="selectedMap" @update:model-value="$emit('update:selectedMap', $event)" class="mb-5" />

            <div class="flex items-center justify-between mb-1">
              <span class="text-sm font-semibold text-charcoal">Quantity</span>
              <div class="flex items-center border-2 border-purple-200 rounded-full overflow-hidden">
                <button @click="setQty(qty - 1)" :disabled="qty <= 1" aria-label="Decrease" class="px-4 py-2.5 text-ink hover:bg-purple-50 active:bg-purple-100 font-bold leading-none disabled:opacity-30">−</button>
                <span class="px-3 py-2.5 font-semibold text-charcoal min-w-10 text-center tabular-nums">{{ qty }}</span>
                <button @click="setQty(qty + 1)" :disabled="qty >= effectiveStock" aria-label="Increase" class="px-4 py-2.5 text-ink hover:bg-purple-50 active:bg-purple-100 font-bold leading-none disabled:opacity-30">+</button>
              </div>
            </div>
          </div>

          <div class="px-5 pt-4 shrink-0">
            <Button size="lg" block :loading="adding" :disabled="effectiveStock === 0" @click="addFromSheet">
              <template v-if="added"><CheckCircleIcon class="w-5 h-5" /> Added!</template>
              <template v-else>Add to cart — ৳{{ total }}</template>
            </Button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Sticky bar — teleported to <body> so it isn't trapped under the footer's stacking
         context; sits just below the nav (z-30 < nav z-40) so the Cart stays tappable through the
         notch. Fixed height matches the measured mask; bottom is set flush to the nav in JS. ── -->
    <Teleport to="body">
    <div ref="barRef" class="kz-buybar md:hidden fixed inset-x-0 z-30 bg-surface/95 backdrop-blur px-4 h-[66px] flex items-center"
      style="bottom: calc(4rem + env(safe-area-inset-bottom, 0px))">
      <div class="w-full flex items-center justify-between gap-3">
        <!-- LEFT: price (+ qty when nothing left to choose) -->
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="min-w-0">
            <p class="text-[10px] text-muted uppercase tracking-wide leading-none truncate">{{ chosen && selectedSummary ? selectedSummary : 'Total' }}</p>
            <p class="text-lg font-bold text-ink leading-tight">৳{{ total }}</p>
          </div>
          <div v-if="!needsChoice" class="flex items-center border border-border rounded-xl overflow-hidden shrink-0">
            <button @click="setQty(qty - 1)" :disabled="qty <= 1" aria-label="Decrease" class="px-3 py-2 text-ink active:bg-[#F1F1F2] disabled:opacity-30">−</button>
            <span class="px-1.5 text-sm font-semibold tabular-nums min-w-6 text-center">{{ qty }}</span>
            <button @click="setQty(qty + 1)" :disabled="qty >= effectiveStock" aria-label="Increase" class="px-3 py-2 text-ink active:bg-[#F1F1F2] disabled:opacity-30">+</button>
          </div>
        </div>

        <!-- RIGHT: action -->
        <Button size="md" :loading="adding" :disabled="effectiveStock === 0" @click="onPrimary" class="shrink-0 px-5">
          <template v-if="effectiveStock === 0">Out of stock</template>
          <template v-else-if="needsChoice"><AdjustmentsHorizontalIcon class="w-4 h-4" /> Choose</template>
          <template v-else-if="added"><CheckCircleIcon class="w-4 h-4" /> Added!</template>
          <template v-else>Add to cart</template>
        </Button>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import Button from '@/Components/ui/Button.vue'
import Price from '@/Components/ui/Price.vue'
import ProductVariations from '@/Components/product/detail/ProductVariations.vue'
import { variationGroups, selectionPriceDelta, selectionStock } from '@/utils/variations'
import { XMarkIcon, CheckCircleIcon, AdjustmentsHorizontalIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  product:     { type: Object, required: true },
  qty:         { type: Number, default: 1 },
  selectedMap: { type: Object, default: () => ({}) },
  adding:      { type: Boolean, default: false },
  added:       { type: Boolean, default: false },
})
const emit = defineEmits(['add', 'update:qty', 'update:selectedMap'])

const sheet = ref(false)

const groups = computed(() => variationGroups(props.product))
const hasVariations = computed(() => groups.value.length > 0)
const selectedCount = computed(() => groups.value.filter((g, gi) => props.selectedMap[gi]).length)
const allSelected = computed(() => selectedCount.value === groups.value.length)
const needsChoice = computed(() => hasVariations.value && !allSelected.value)
const chosen = computed(() => hasVariations.value && selectedCount.value > 0)
const selectedSummary = computed(() => groups.value.map((g, gi) => props.selectedMap[gi]?.value).filter(Boolean).join(' · '))
const image = computed(() => Object.values(props.selectedMap).find((o) => o?.image)?.image || null)

// Variation-aware price + stock (options may carry a ± price and their own stock)
const priceDelta     = computed(() => selectionPriceDelta(props.selectedMap))
const unitFinal      = computed(() => Math.max(0, Number(props.product.final_price) + priceDelta.value))
const unitRegular    = computed(() => Math.max(0, Number(props.product.price) + priceDelta.value))
const effectiveStock = computed(() => selectionStock(props.product, props.selectedMap))

const total = computed(() => (unitFinal.value * props.qty).toLocaleString())

const setQty = (v) => emit('update:qty', Math.min(Math.max(1, v), effectiveStock.value || 1))

const onPrimary = () => {
  if (effectiveStock.value === 0) return
  if (needsChoice.value) { sheet.value = true; return }
  emit('add')
}
const addFromSheet = () => emit('add')

// Close the sheet once an add succeeds.
watch(() => props.added, (v) => { if (v) sheet.value = false })

// ── On-device notch: measure the real Cart button + nav so the cut hugs it exactly on every
//    device (the protrusion/flush position vary with iOS safe-area, so static CSS can't match). ──
const barRef = ref(null)

// Build the bar mask. The notch follows the Cart's ACTUAL rounded-corner arc (its border radius,
// offset outward by `gap`), sampled as a polyline so the gap stays uniform even though the Cart
// protrudes less than its corner radius. `cart` is {left,width,top,r} in bar-relative px.
const buildMaskUrl = (W, H, cart, gap, topR) => {
  const n = (v) => Math.round(v * 100) / 100
  const oR = cart.r + gap                       // offset corner radius
  const centerY = cart.top + cart.r             // y of both corner centres (bar-relative)
  const leftCx = cart.left + cart.r
  const rightCx = cart.left + cart.width - cart.r

  // Points along one corner's offset arc, from the flat top (t=0) down to where it meets y=H.
  const arc = (cx, side) => {
    const cosTmax = Math.max(-1, Math.min(1, (centerY - H) / oR))
    const tMax = Math.acos(cosTmax)
    const steps = 10
    const pts = []
    for (let i = 0; i <= steps; i++) {
      const t = (tMax * i) / steps
      pts.push([cx + side * oR * Math.sin(t), centerY - oR * Math.cos(t)])
    }
    return pts // [0] = flat-top tangent, [last] = bottom (y≈H)
  }
  const L = arc(leftCx, -1)
  const R = arc(rightCx, 1)

  // Clockwise: rounded top → right side → bottom → up the right arc → flat top → down the left arc → bottom-left.
  let d = `M0 ${n(topR)} Q0 0 ${n(topR)} 0 L${n(W - topR)} 0 Q${n(W)} 0 ${n(W)} ${n(topR)} L${n(W)} ${n(H)} `
  d += `L${n(R[R.length - 1][0])} ${n(H)} `
  for (let i = R.length - 1; i >= 0; i--) d += `L${n(R[i][0])} ${n(R[i][1])} `
  for (let i = 0; i < L.length; i++) d += `L${n(L[i][0])} ${n(L[i][1])} `
  d += `L0 ${n(H)} Z`

  const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='${n(W)}' height='${n(H)}'><path d='${d}' fill='#000'/></svg>`
  return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`
}

const applyMask = (value) => {
  const el = barRef.value
  if (!el) return
  el.style.setProperty('-webkit-mask', value)
  el.style.setProperty('mask', value)
}

const measure = () => {
  const el = barRef.value
  if (!el) return
  const nav = document.querySelector('nav.fixed.bottom-0')
  const fab = document.querySelector('[data-cart-fab]')
  if (!nav || !fab) return // leave the CSS fallback notch in place

  // 1) sit the bar flush on the nav's top edge (works regardless of nav height / safe-area).
  //    Use the VISUAL viewport height — window.innerHeight is unreliable on iOS Safari
  //    (it lags the collapsing/expanding address bar) and could fling the bar far up the
  //    page. A sanity guard keeps a bad reading from ever repositioning the bar; the CSS
  //    fallback `bottom: calc(4rem + safe-area)` then stays in effect.
  const vh = window.visualViewport?.height ?? window.innerHeight
  const navTop = nav.getBoundingClientRect().top
  const bottom = vh - navTop
  if (bottom > 0 && bottom < vh * 0.5) {
    el.style.bottom = `${bottom}px`
  }

  // 2) measure after positioning + draw a notch that hugs the Cart's real rounded corners (~1px gap)
  const b = el.getBoundingClientRect()
  const c = fab.getBoundingClientRect()
  const W = b.width
  const H = b.height
  const protrusion = b.bottom - c.top
  if (protrusion <= 1) return

  const gap = 1
  const cartR = parseFloat(getComputedStyle(fab).borderTopLeftRadius) || 16
  const cart = {
    left: c.left - b.left,
    width: c.width,
    top: c.top - b.top,
    r: cartR,
  }
  applyMask(`${buildMaskUrl(W, H, cart, gap, 16)} center / 100% 100% no-repeat`)
}

let raf = null
const scheduleMeasure = () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(measure) }
let ro = null
onMounted(() => {
  nextTick(() => { measure(); requestAnimationFrame(measure); setTimeout(measure, 300) })
  window.addEventListener('resize', scheduleMeasure)
  window.addEventListener('orientationchange', scheduleMeasure)
  // iOS Safari fires these as the address bar collapses/expands on scroll — re-align then.
  window.visualViewport?.addEventListener('resize', scheduleMeasure)
  window.visualViewport?.addEventListener('scroll', scheduleMeasure)
  const nav = document.querySelector('nav.fixed.bottom-0')
  if (nav && 'ResizeObserver' in window) { ro = new ResizeObserver(scheduleMeasure); ro.observe(nav, { box: 'border-box' }) }
})
onBeforeUnmount(() => {
  cancelAnimationFrame(raf)
  window.removeEventListener('resize', scheduleMeasure)
  window.removeEventListener('orientationchange', scheduleMeasure)
  window.visualViewport?.removeEventListener('resize', scheduleMeasure)
  window.visualViewport?.removeEventListener('scroll', scheduleMeasure)
  ro?.disconnect()
})
</script>

<style scoped>
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .25s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
.kz-sheet-enter-active, .kz-sheet-leave-active { transition: transform .3s cubic-bezier(.4,0,.2,1); }
.kz-sheet-enter-from, .kz-sheet-leave-to { transform: translateY(100%); }

/* Fixed-size notch that never stretches (the cause of the uneven gap on real iPhones):
   a center 80px-wide SVG holds the rounded notch at a FIXED pixel size, and two solid
   side-fills cover the rest. Combined by the default `add` (union) — no `mask-composite`,
   so it's reliable on iOS Safari and never clips the side buttons. Top corners come from
   `rounded-t-2xl`. Notch = 58px wide × 14px deep, hugging the centred 56px/radius-16 Cart
   button with a ~1px gap on every device width. */
.kz-buybar {
  --kz-notch: url("data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='80'%20height='66'%3E%3Cpath%20d='M0%200%20L80%200%20L80%2066%20L69%2066%20L69%2061%20Q69%2052%2060%2052%20L20%2052%20Q11%2052%2011%2061%20L11%2066%20L0%2066%20Z'%20fill='%23000'/%3E%3C/svg%3E");
  -webkit-mask:
    var(--kz-notch) bottom center / 80px 66px no-repeat,
    linear-gradient(#000, #000) left  center / calc(50% - 30px) 100% no-repeat,
    linear-gradient(#000, #000) right center / calc(50% - 30px) 100% no-repeat;
          mask:
    var(--kz-notch) bottom center / 80px 66px no-repeat,
    linear-gradient(#000, #000) left  center / calc(50% - 30px) 100% no-repeat,
    linear-gradient(#000, #000) right center / calc(50% - 30px) 100% no-repeat;
  filter: drop-shadow(0 -4px 14px rgba(0, 0, 0, 0.10));
}
</style>
