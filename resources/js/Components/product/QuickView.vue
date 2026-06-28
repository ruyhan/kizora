<template>
  <Modal :open="open" @update:open="open = $event" max-width="sm:max-w-3xl">
    <div v-if="product" class="grid sm:grid-cols-2">
      <!-- Image -->
      <Link :href="route('shop.show', product.slug)" class="block bg-[#F4F4F5] aspect-square sm:aspect-auto" @click="open = false">
        <img :src="displayImage" :alt="product.name" class="w-full h-full object-cover" />
      </Link>

      <!-- Details -->
      <div class="p-5 sm:p-6 flex flex-col">
        <p class="text-xs text-muted mb-1">{{ product.category?.name }}</p>
        <h3 class="font-display text-xl font-bold text-ink leading-tight mb-2">{{ product.name }}</h3>

        <div v-if="product.review_count" class="mb-3">
          <Rating :value="Number(product.average_rating)" :count="product.review_count" size="sm" />
        </div>

        <div class="flex items-baseline gap-2 mb-4">
          <span class="text-2xl font-bold text-ink">৳{{ money(product.final_price) }}</span>
          <span v-if="product.discount_price" class="text-sm text-muted line-through">৳{{ money(product.price) }}</span>
          <span v-if="product.discount_percentage > 0" class="badge badge-accent">-{{ product.discount_percentage }}%</span>
        </div>

        <p v-if="product.short_description" class="text-sm text-muted leading-relaxed mb-4 line-clamp-3">{{ product.short_description }}</p>

        <!-- Highlight cards (USP) — same as the detail page -->
        <ProductHighlights v-if="product.usps?.length && showHighlights" :usps="product.usps" class="mb-4" />

        <!-- Variations -->
        <div v-for="(g, gi) in variationGroups" :key="gi" class="mb-3">
          <p class="text-xs font-semibold text-charcoal mb-1.5">
            {{ g.name }}<span v-if="selected[gi]" class="text-muted font-normal">: {{ selected[gi].label || selected[gi].value }}</span>
          </p>
          <div class="flex items-center gap-2 flex-wrap">
            <template v-if="g.type === 'color'">
              <button v-for="(o, oi) in g.options" :key="oi" type="button" @click="selectOption(gi, o)"
                :style="{ backgroundColor: o.value }"
                :class="selected[gi] === o ? 'ring-2 ring-ink ring-offset-2 scale-110' : 'hover:scale-110'"
                class="w-[26px] h-[26px] rounded-full shrink-0 border-[3px] border-white shadow-[0_2px_6px_rgba(23,24,27,0.25)] transition-all duration-200 cursor-pointer" :title="o.label || o.value"></button>
            </template>
            <template v-else>
              <button v-for="(o, oi) in g.options" :key="oi" type="button" @click="selectOption(gi, o)"
                :class="selected[gi] === o ? 'border-ink bg-ink text-white' : 'border-border text-charcoal hover:border-ink'"
                class="min-w-8 h-8 px-2.5 rounded-lg border-2 text-xs font-semibold transition-all">{{ o.value }}</button>
            </template>
          </div>
        </div>

        <div v-if="product.tags?.length" class="flex flex-wrap gap-1.5 mb-5">
          <span v-for="t in product.tags.slice(0, 4)" :key="t" class="badge badge-purple capitalize">{{ t }}</span>
        </div>

        <div class="mt-auto space-y-3">
          <div class="flex items-center gap-3">
            <div class="flex items-center border border-border rounded-xl overflow-hidden">
              <button @click="qty > 1 ? qty-- : null" class="px-3 py-2 text-ink active:bg-[#F1F1F2]">−</button>
              <span class="px-2 text-sm font-semibold tabular-nums min-w-7 text-center">{{ qty }}</span>
              <button @click="qty < product.stock ? qty++ : null" class="px-3 py-2 text-ink active:bg-[#F1F1F2]">+</button>
            </div>
            <Button class="flex-1" :loading="adding" :disabled="product.stock === 0" @click="add">
              <template #icon-left><ShoppingBagIcon v-if="!adding" class="w-4 h-4" /></template>
              {{ product.stock === 0 ? 'Sold out' : 'Add to cart' }}
            </Button>
          </div>
          <Link :href="route('shop.show', product.slug)" @click="open = false" class="block text-center text-sm font-semibold text-ink hover:text-accent transition-colors">
            View full details →
          </Link>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'
import Rating from '@/Components/ui/Rating.vue'
import ProductHighlights from '@/Components/product/detail/ProductHighlights.vue'
import { variationGroups as variationGroupsOf } from '@/utils/variations'
import { ShoppingBagIcon } from '@heroicons/vue/24/outline'

const open = ref(false)
const product = ref(null)
const qty = ref(1)
const adding = ref(false)
const toast = useToast()
const cart = useCartStore()

const money = (v) => Number(v ?? 0).toLocaleString()
const showHighlights = computed(() => usePage().props.productSettings?.show_highlights !== false)

// ── Variations ──
const variationGroups = computed(() => variationGroupsOf(product.value))
const selected = ref({})
const displayImage = ref(null)
const buildSelected = () => variationGroups.value
  .map((g, gi) => (selected.value[gi]
    ? { name: g.name, type: g.type, value: selected.value[gi].value, label: selected.value[gi].label || selected.value[gi].value, image: selected.value[gi].image || null }
    : null))
  .filter(Boolean)
const selectOption = (gi, o) => {
  selected.value = { ...selected.value, [gi]: o }
  if (o.image) displayImage.value = o.image
}

const onOpen = (e) => {
  product.value = e.detail
  qty.value = 1
  selected.value = {}
  displayImage.value = e.detail?.featured_image_url
  open.value = true
}
onMounted(() => window.addEventListener('kz-quickview', onOpen))
onUnmounted(() => window.removeEventListener('kz-quickview', onOpen))

const add = async () => {
  if (adding.value || !product.value || product.value.stock === 0) return

  // Require a choice for every variation group.
  const groups = variationGroups.value
  const chosen = buildSelected()
  if (groups.length && chosen.length < groups.length) {
    const missing = groups.map((g) => g.name).filter((n) => !chosen.some((c) => c.name === n))
    toast.error(`Please select ${missing.join(' & ')}.`)
    return
  }

  adding.value = true
  try {
    await cart.add(product.value.id, qty.value, chosen)
    toast.success(`${product.value.name} added to cart`)
    open.value = false
  } catch { toast.error('Could not add to cart.') }
  finally { adding.value = false }
}
</script>
