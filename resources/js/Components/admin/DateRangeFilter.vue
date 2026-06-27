<template>
  <div ref="root" class="relative inline-block text-left">
    <button type="button" @click="open = !open"
      class="inline-flex items-center gap-2 bg-white border border-purple-100 rounded-xl px-3.5 py-2 text-sm font-medium text-charcoal hover:border-ink/30 transition-colors">
      <CalendarDaysIcon class="w-4 h-4 text-muted" />
      <span class="whitespace-nowrap">{{ label }}</span>
      <ChevronDownIcon class="w-4 h-4 text-muted transition-transform" :class="open ? 'rotate-180' : ''" />
    </button>

    <Transition name="dr-pop">
      <div v-if="open" class="absolute right-0 z-40 mt-2 w-64 origin-top-right rounded-2xl bg-white border border-purple-100 shadow-xl p-2">
        <button v-for="p in presets" :key="p.value" type="button" @click="pickPreset(p.value)"
          :class="(filters?.range === p.value && filters?.range !== 'custom') ? 'bg-purple-50 text-ink font-semibold' : 'text-charcoal hover:bg-purple-50/60'"
          class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors">
          {{ p.label }}
        </button>

        <div class="mt-2 pt-2 border-t border-purple-50">
          <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted">Custom range</p>
          <div class="px-2 space-y-2">
            <label class="block">
              <span class="text-[11px] text-muted">From</span>
              <input v-model="from" type="date" class="input-field h-9 text-sm">
            </label>
            <label class="block">
              <span class="text-[11px] text-muted">To</span>
              <input v-model="to" type="date" class="input-field h-9 text-sm">
            </label>
            <button type="button" @click="applyCustom" :disabled="!from && !to"
              class="btn-primary w-full text-sm py-2 disabled:opacity-50">Apply</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { CalendarDaysIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  routeName: { type: String, required: true },
  filters: { type: Object, default: () => ({}) },
  params: { type: Object, default: () => ({}) }, // other query params to preserve (status, search…)
})

const open = ref(false)
const root = ref(null)
const from = ref(props.filters?.from || '')
const to = ref(props.filters?.to || '')

const label = computed(() => props.filters?.label || 'Last 30 days')

const presets = [
  { value: 'today', label: 'Today' },
  { value: '7', label: 'Last 7 days' },
  { value: '30', label: 'Last 30 days' },
  { value: '90', label: 'Last 90 days' },
  { value: '365', label: 'Last 12 months' },
  { value: 'this_month', label: 'This month' },
  { value: 'last_month', label: 'Last month' },
  { value: 'this_year', label: 'This year' },
  { value: 'all', label: 'All time' },
]

const go = (range) => {
  // Drop any stale range/from/to from preserved params, then apply the new ones.
  const { range: _r, from: _f, to: _t, ...rest } = props.params
  router.get(route(props.routeName), { ...rest, ...range }, { preserveState: true, preserveScroll: true, replace: true })
  open.value = false
}
const pickPreset = (v) => go({ range: v })
const applyCustom = () => { if (from.value || to.value) go({ from: from.value, to: to.value }) }

const onClickOutside = (e) => { if (open.value && root.value && !root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>

<style scoped>
.dr-pop-enter-active { transition: opacity .15s ease, transform .15s cubic-bezier(.16,1,.3,1); }
.dr-pop-leave-active { transition: opacity .1s ease; }
.dr-pop-enter-from, .dr-pop-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
