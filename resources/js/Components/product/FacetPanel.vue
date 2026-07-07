<template>
  <div class="space-y-5">
    <!-- Categories -->
    <section>
      <p class="facet-label">Categories</p>
      <div class="space-y-0.5">
        <button @click="set('category', '')" :class="rowCls(!model.category)" class="facet-row">
          <span class="flex items-center gap-2"><Squares2X2Icon class="w-4 h-4" /> All products</span>
        </button>
        <template v-for="g in categoryTree" :key="g.id">
          <button @click="set('category', g.slug)" :class="rowCls(model.category === g.slug)" class="facet-row font-semibold">
            <span class="flex items-center gap-2 min-w-0">
              <CategoryIcon :icon="g.icon" :slug="g.slug" class="w-4 h-4 shrink-0" />
              <span class="truncate">{{ g.name }}</span>
            </span>
            <span class="facet-count" :class="model.category === g.slug ? 'text-white/60' : 'text-muted'">{{ g.count }}</span>
          </button>
          <div v-if="g.children.length" class="ml-3.5 pl-3 border-l border-border my-0.5 space-y-0.5">
            <button v-for="c in g.children" :key="c.id" @click="set('category', c.slug)" :class="rowCls(model.category === c.slug)" class="facet-row text-[13px]">
              <span class="truncate">{{ c.name }}</span>
              <span class="facet-count" :class="model.category === c.slug ? 'text-white/60' : 'text-muted'">{{ c.count }}</span>
            </button>
          </div>
        </template>
      </div>
    </section>

    <div class="facet-divider"></div>

    <!-- Price -->
    <section>
      <p class="facet-label">Price</p>
      <div class="flex items-center gap-2">
        <div class="relative flex-1">
          <span class="facet-prefix">৳</span>
          <input v-model="model.min_price" type="number" inputmode="numeric" :placeholder="String(priceBounds.min)" class="facet-input" />
        </div>
        <span class="text-muted shrink-0">–</span>
        <div class="relative flex-1">
          <span class="facet-prefix">৳</span>
          <input v-model="model.max_price" type="number" inputmode="numeric" :placeholder="String(priceBounds.max)" class="facet-input" />
        </div>
      </div>
      <button @click="$emit('change')" class="mt-2.5 w-full h-9 rounded-lg bg-purple-50 text-ink text-sm font-semibold hover:bg-purple-100 transition-colors">Apply price</button>
    </section>

    <div class="facet-divider"></div>

    <!-- Availability -->
    <section class="space-y-2.5">
      <p class="facet-label">Availability</p>
      <label class="facet-toggle">
        <input type="checkbox" v-model="model.in_stock" @change="$emit('change')" class="kz-check"> In stock only
      </label>
      <label class="facet-toggle">
        <input type="checkbox" v-model="model.sale" @change="$emit('change')" class="kz-check"> On sale
      </label>
    </section>

    <!-- Tags -->
    <template v-if="availableTags.length">
      <div class="facet-divider"></div>
      <section>
        <p class="facet-label">Features</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="t in availableTags" :key="t" @click="toggleTag(t)"
            :class="model.tags.includes(t) ? 'bg-ink text-white border-ink' : 'bg-surface text-charcoal border-border hover:border-ink'"
            class="px-3 h-8 rounded-full border text-xs font-medium capitalize transition-colors">{{ t }}</button>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { Squares2X2Icon } from '@heroicons/vue/24/outline'
import CategoryIcon from '@/Components/icons/CategoryIcon.vue'

const props = defineProps({
  categories:    { type: Array, default: () => [] },
  categoryTree:  { type: Array, default: () => [] },
  priceBounds:   { type: Object, default: () => ({ min: 0, max: 10000 }) },
  availableTags: { type: Array, default: () => [] },
  model:         { type: Object, required: true },
})
const emit = defineEmits(['change'])

const set = (key, val) => { props.model[key] = val; emit('change') }
const toggleTag = (t) => {
  const i = props.model.tags.indexOf(t)
  if (i === -1) props.model.tags.push(t); else props.model.tags.splice(i, 1)
  emit('change')
}
const rowCls = (active) => active ? 'bg-ink text-white' : 'text-charcoal hover:bg-purple-50'
</script>

<style scoped>
@reference "../../../css/app.css";
.facet-label { @apply text-[13px] font-bold text-charcoal mb-2.5; }
.facet-row { @apply w-full flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg text-sm font-medium transition-colors; }
.facet-count { @apply text-xs tabular-nums shrink-0; }
.facet-divider { @apply h-px bg-border; }
.facet-input { @apply w-full h-9 pr-2.5 rounded-lg border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-ink/10; }
/* own rule — a plain declaration inside the @apply rule above gets dropped at build time */
.facet-input { padding-left: 2rem; }
.facet-prefix { @apply absolute left-2.5 top-1/2 -translate-y-1/2 text-muted text-sm pointer-events-none; }
.facet-toggle { @apply flex items-center gap-2.5 cursor-pointer text-sm text-charcoal hover:text-ink transition-colors; }
.kz-check { @apply w-4 h-4 rounded border-border text-ink; accent-color: #17181B; }
</style>
