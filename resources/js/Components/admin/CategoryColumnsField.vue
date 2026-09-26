<template>
  <div class="space-y-3">
    <!-- Chosen columns, in display order -->
    <div v-if="chosen.length" class="space-y-2">
      <div v-for="(c, i) in chosen" :key="c.value"
        class="flex items-center gap-3 rounded-xl border border-purple-100 bg-purple-50/40 px-3 py-2.5">
        <span class="w-7 h-7 rounded-lg bg-ink text-white text-xs font-bold flex items-center justify-center shrink-0">{{ i + 1 }}</span>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-charcoal truncate">{{ c.label }}</p>
          <p class="text-[11px] text-muted">{{ c.children ? `${c.children} sub-categories` : 'No sub-categories — shows a "Shop all" link' }}</p>
        </div>
        <div class="flex items-center gap-1 shrink-0">
          <button type="button" @click="move(i, -1)" :disabled="i === 0" class="icon-sq" title="Move left"><ChevronUpIcon class="w-4 h-4" /></button>
          <button type="button" @click="move(i, 1)" :disabled="i === chosen.length - 1" class="icon-sq" title="Move right"><ChevronDownIcon class="w-4 h-4" /></button>
          <button type="button" @click="remove(i)" class="icon-sq text-error" title="Remove column"><XMarkIcon class="w-4 h-4" /></button>
        </div>
      </div>
    </div>
    <div v-else class="rounded-xl border border-dashed border-purple-200 px-4 py-3 text-sm text-muted">
      <span class="font-semibold text-charcoal">Automatic mode</span> — no columns added, so the first categories show automatically (count set below).
    </div>

    <!-- Add a column -->
    <div v-if="available.length" class="flex flex-wrap items-center gap-2">
      <span class="text-xs font-semibold text-muted uppercase tracking-wider mr-1">Add column:</span>
      <button v-for="c in available" :key="c.value" type="button" @click="add(c.value)"
        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-purple-100 bg-white text-sm text-charcoal hover:border-ink hover:text-ink transition-colors">
        <PlusIcon class="w-3.5 h-3.5" /> {{ c.label }}
      </button>
    </div>
    <p v-else-if="!options.length" class="text-sm text-muted">No active categories yet.</p>
    <p v-if="missing.length" class="text-xs text-amber-600">
      {{ missing.length }} saved column(s) point to categories that are inactive or deleted and are hidden on the site.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronUpIcon, ChevronDownIcon, XMarkIcon, PlusIcon } from '@heroicons/vue/24/outline'

// Ordered list of top-level category slugs; the mega menu shows them left → right in this order.
const model = defineModel({ type: Array, default: () => [] })
const props = defineProps({ options: { type: Array, default: () => [] } })   // [{ value, label, children }]

const list = computed(() => (Array.isArray(model.value) ? model.value : []))
const byValue = computed(() => Object.fromEntries(props.options.map(o => [o.value, o])))
const chosen = computed(() => list.value.map(v => byValue.value[v]).filter(Boolean))
const missing = computed(() => list.value.filter(v => !byValue.value[v]))
const available = computed(() => props.options.filter(o => !list.value.includes(o.value)))

const add = (v) => { model.value = [...list.value, v] }
const remove = (i) => {
  const v = chosen.value[i].value
  model.value = list.value.filter(x => x !== v)
}
const move = (i, dir) => {
  // Reorder among the visible (valid) entries; stale slugs keep their place at the end.
  const vis = chosen.value.map(c => c.value)
  const j = i + dir
  if (j < 0 || j >= vis.length) return
  ;[vis[i], vis[j]] = [vis[j], vis[i]]
  model.value = [...vis, ...missing.value]
}
</script>

<style scoped>
@reference "../../../css/app.css";
.icon-sq { @apply w-7 h-7 rounded-lg bg-white border border-purple-100 flex items-center justify-center text-muted hover:text-ink disabled:opacity-40 transition-colors; }
</style>
