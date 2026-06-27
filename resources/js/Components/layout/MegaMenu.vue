<template>
  <div
    class="absolute left-0 right-0 top-full pt-3"
    role="region"
    aria-label="Product categories"
    @mouseenter="$emit('hoverin')"
    @mouseleave="$emit('hoverout')"
  >
    <!-- Same boxed container as the hero slider: max-w-7xl + matching side padding -->
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-surface border border-border rounded-2xl shadow-xl shadow-ink/5 overflow-hidden">
        <div class="p-6 sm:p-8">
      <div class="grid grid-cols-2 gap-x-8 gap-y-6 kz-mega-grid" :style="gridStyle">
        <!-- Category columns -->
        <div v-for="group in groups" :key="group.id" class="min-w-0">
          <!-- Column header (links to the parent category) -->
          <Link :href="route('shop.index', { category: group.slug })" @click="$emit('navigate')"
            class="group/h flex items-center gap-2.5 pb-2.5 mb-2 border-b border-border">
            <span class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-ink group-hover/h:bg-ink group-hover/h:text-white transition-colors shrink-0">
              <CategoryIcon :icon="group.icon" :slug="group.slug" class="w-4 h-4" />
            </span>
            <span class="font-display font-bold text-charcoal text-sm group-hover/h:text-accent transition-colors truncate">{{ group.name }}</span>
          </Link>

          <!-- Sub-categories -->
          <ul class="space-y-0.5">
            <li v-for="item in group.children" :key="item.slug">
              <Link :href="route('shop.index', { category: item.slug })" @click="$emit('navigate')"
                class="flex items-center justify-between gap-2 px-2.5 py-1.5 -mx-2.5 rounded-lg text-sm text-muted hover:bg-purple-50 hover:text-ink transition-colors">
                <span class="truncate">{{ item.name }}</span>
                <span class="text-xs text-muted/60 shrink-0">{{ item.count }}</span>
              </Link>
            </li>
            <li v-if="!group.children.length">
              <Link :href="route('shop.index', { category: group.slug })" @click="$emit('navigate')"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 -mx-2.5 rounded-lg text-sm font-medium text-accent hover:gap-2 transition-all">
                Shop all <ArrowRightIcon class="w-3.5 h-3.5" />
              </Link>
            </li>
          </ul>
        </div>

        <!-- Promo tile (fills remaining column) -->
        <Link
          :href="promo.link"
          class="relative overflow-hidden rounded-2xl bg-ink text-white p-6 flex flex-col justify-end min-h-44 group"
          @click="$emit('navigate')"
        >
          <span class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-accent/25 blur-2xl"></span>
          <p class="text-[11px] font-semibold uppercase tracking-widest text-accent relative">{{ promo.eyebrow }}</p>
          <p class="font-display text-xl font-bold leading-tight mt-1 relative">{{ promo.title }}</p>
          <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold relative group-hover:gap-2.5 transition-all">
            {{ promo.cta }} <ArrowRightIcon class="w-4 h-4" />
          </span>
        </Link>
      </div>

      <!-- Footer row: quick links -->
      <div class="flex items-center gap-5 mt-6 pt-4 border-t border-border text-sm">
        <Link :href="route('shop.index')" @click="$emit('navigate')" class="font-semibold text-ink hover:text-accent transition-colors inline-flex items-center gap-1">
          Browse all products <ArrowRightIcon class="w-3.5 h-3.5" />
        </Link>
        <Link :href="route('shop.index', { sale: 1 })" @click="$emit('navigate')" class="text-muted hover:text-accent transition-colors">On sale</Link>
      </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import CategoryIcon from '@/Components/icons/CategoryIcon.vue'
import { megaMenuPromo } from '@/config/categories'

defineEmits(['navigate', 'hoverin', 'hoverout'])

const page = usePage()
// Category columns — controlled in Admin → Pages & Content → Mega Menu.
// If specific categories are chosen, show those (in Categories sort order);
// otherwise auto-show the first N (mega_columns).
const groups = computed(() => {
  const mm = page.props.megamenu || {}
  const tree = page.props.categoryTree || []
  const chosen = Array.isArray(mm.categories) ? mm.categories : []
  if (chosen.length) {
    return tree.filter((c) => chosen.includes(c.slug))
  }
  const n = Number(mm.mega_columns) || 3
  return tree.slice(0, Math.max(1, n))
})
// columns = category columns + 1 promo tile, on lg+ (mobile stays 2-col)
const gridStyle = computed(() => ({
  '--kz-cols': groups.value.length + 1,
}))
const promo = computed(() => {
  const mm = page.props.megamenu || {}
  return {
    eyebrow: mm.promo_eyebrow || megaMenuPromo.eyebrow,
    title:   mm.promo_title   || megaMenuPromo.title,
    cta:     mm.promo_cta     || megaMenuPromo.cta,
    link:    mm.promo_link    || '/shop',
  }
})
</script>

<style scoped>
@media (min-width: 1024px) {
  .kz-mega-grid { grid-template-columns: repeat(var(--kz-cols, 4), minmax(0, 1fr)); }
}
</style>
