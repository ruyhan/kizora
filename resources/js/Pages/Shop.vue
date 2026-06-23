<template>
  <AppLayout>
    <Head title="Shop" />

    <!-- Header -->
    <div class="relative py-10 text-center overflow-hidden border-b border-border" :class="page.header_image ? 'bg-ink' : ''">
      <template v-if="page.header_image">
        <img :src="page.header_image" class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
        <span class="absolute inset-0 bg-ink/50"></span>
      </template>
      <div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-3xl md:text-4xl font-bold mb-2" :class="page.header_image ? 'text-white' : 'text-ink'">{{ headingText }}</h1>
        <p :class="page.header_image ? 'text-white/80' : 'text-muted'">{{ page.subtitle }}</p>
      </div>
    </div>

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
      <!-- Toolbar -->
      <div class="flex items-center gap-3 mb-4">
        <button @click="filtersOpen = true" class="lg:hidden inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-border bg-surface text-sm font-semibold text-ink">
          <FunnelIcon class="w-4 h-4" /> Filters
          <span v-if="activeChips.length" class="w-5 h-5 rounded-full bg-accent text-white text-[10px] flex items-center justify-center">{{ activeChips.length }}</span>
        </button>
        <p class="text-sm text-muted hidden sm:block">{{ products.total }} {{ products.total === 1 ? 'product' : 'products' }}</p>
        <div class="ml-auto flex items-center gap-2">
          <label class="text-sm text-muted hidden sm:inline">Sort</label>
          <SelectMenu :model-value="local.sort" @update:model-value="v => { local.sort = v; apply() }"
            :options="sortOptions" trigger-class="text-sm py-2" class="w-44" />
        </div>
      </div>

      <!-- Active filter chips -->
      <div v-if="activeChips.length" class="flex flex-wrap items-center gap-2 mb-5">
        <button v-for="chip in activeChips" :key="chip.key" @click="chip.clear()"
          class="inline-flex items-center gap-1.5 h-8 pl-3 pr-2 rounded-full bg-ink text-white text-xs font-medium">
          {{ chip.label }} <XMarkIcon class="w-3.5 h-3.5" />
        </button>
        <button @click="clearAll" class="text-xs font-semibold text-accent hover:underline ml-1">Clear all</button>
      </div>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Desktop sidebar -->
        <aside class="hidden lg:block w-72 shrink-0">
          <div class="sticky top-28 card p-5 max-h-[calc(100vh-8rem)] overflow-y-auto scrollbar-slim">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-border">
              <h2 class="font-display font-bold text-charcoal flex items-center gap-2">
                <FunnelIcon class="w-4 h-4" /> Filters
              </h2>
              <button v-if="activeChips.length" @click="clearAll" class="text-xs font-semibold text-accent hover:underline">Clear all</button>
            </div>
            <FacetPanel v-bind="facetProps" @change="apply" />
          </div>
        </aside>

        <!-- Grid -->
        <div class="flex-1 min-w-0">
          <div v-if="products.data?.length" class="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <ProductCard v-for="p in products.data" :key="p.id" :product="p" @quickview="$emit('quickview', $event)" />
          </div>
          <div v-else class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-full bg-[#F1F1F2] flex items-center justify-center mb-4">
              <MagnifyingGlassIcon class="w-8 h-8 text-muted" />
            </div>
            <h3 class="font-display text-xl font-semibold text-ink mb-2">No products found</h3>
            <p class="text-muted mb-6">Try adjusting your filters or search.</p>
            <button @click="clearAll" class="btn-primary">Clear filters</button>
          </div>

          <!-- Pagination -->
          <div v-if="products.last_page > 1" class="flex justify-center flex-wrap gap-2 mt-10">
            <template v-for="link in products.links" :key="link.label">
              <Link v-if="link.url" :href="link.url" preserve-scroll
                :class="link.active ? 'bg-ink text-white' : 'bg-surface text-ink hover:bg-[#F4F4F5]'"
                class="px-4 py-2 rounded-xl text-sm font-medium border border-border" v-html="link.label" />
              <span v-else class="px-4 py-2 rounded-xl text-sm text-muted border border-border" v-html="link.label" />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile filter drawer -->
    <Drawer :open="filtersOpen" @update:open="filtersOpen = $event" side="left" title="Filters">
      <div class="p-5"><FacetPanel v-bind="facetProps" @change="apply" /></div>
      <template #footer>
        <div class="p-4 flex gap-3">
          <button @click="clearAll" class="btn-secondary flex-1">Clear</button>
          <button @click="filtersOpen = false" class="btn-primary flex-1">Show {{ products.total }}</button>
        </div>
      </template>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import FacetPanel from '@/Components/product/FacetPanel.vue'
import Drawer from '@/Components/ui/Drawer.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { FunnelIcon, XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const sortOptions = [
  { value: 'latest', label: 'Newest' },
  { value: 'best_selling', label: 'Best selling' },
  { value: 'rating', label: 'Top rated' },
  { value: 'price_asc', label: 'Price: Low → High' },
  { value: 'price_desc', label: 'Price: High → Low' },
]

const props = defineProps({
  page:          { type: Object, default: () => ({}) },
  products:      Object,
  categories:    Array,
  priceBounds:   { type: Object, default: () => ({ min: 0, max: 10000 }) },
  availableTags: { type: Array, default: () => [] },
  filters:       Object,
})
defineEmits(['quickview'])

const filtersOpen = ref(false)

const local = reactive({
  category:  props.filters?.category || '',
  search:    props.filters?.search || '',
  sort:      props.filters?.sort || 'latest',
  min_price: props.filters?.min_price || '',
  max_price: props.filters?.max_price || '',
  rating:    props.filters?.rating || '',
  in_stock:  !!props.filters?.in_stock,
  sale:      !!props.filters?.sale,
  tags:      Array.isArray(props.filters?.tags) ? [...props.filters.tags] : [],
})

const headingText = computed(() => props.categories?.find(c => c.slug === local.category)?.name || props.page?.title || 'Shop all')

const categoryTree = computed(() => usePage().props.categoryTree || [])

const facetProps = computed(() => ({
  categories: props.categories,
  categoryTree: categoryTree.value,
  priceBounds: props.priceBounds,
  availableTags: props.availableTags,
  model: local,
}))

const apply = () => {
  const params = { ...local }
  Object.keys(params).forEach(k => {
    const v = params[k]
    if (v === '' || v === false || (Array.isArray(v) && v.length === 0)) delete params[k]
  })
  router.get(route('shop.index'), params, { preserveScroll: true, preserveState: true, replace: true })
}

const activeChips = computed(() => {
  const chips = []
  if (local.category) chips.push({ key: 'cat', label: headingText.value, clear: () => { local.category = ''; apply() } })
  if (local.sale) chips.push({ key: 'sale', label: 'On sale', clear: () => { local.sale = false; apply() } })
  if (local.in_stock) chips.push({ key: 'stock', label: 'In stock', clear: () => { local.in_stock = false; apply() } })
  if (local.rating) chips.push({ key: 'rating', label: `${local.rating}★ & up`, clear: () => { local.rating = ''; apply() } })
  if (local.min_price || local.max_price) chips.push({ key: 'price', label: `৳${local.min_price || 0}–${local.max_price || '∞'}`, clear: () => { local.min_price = ''; local.max_price = ''; apply() } })
  local.tags.forEach(t => chips.push({ key: 'tag-' + t, label: t, clear: () => { local.tags = local.tags.filter(x => x !== t); apply() } }))
  return chips
})

const clearAll = () => {
  Object.assign(local, { category: '', search: '', sort: 'latest', min_price: '', max_price: '', rating: '', in_stock: false, sale: false, tags: [] })
  router.get(route('shop.index'), {}, { preserveScroll: true })
}
</script>
