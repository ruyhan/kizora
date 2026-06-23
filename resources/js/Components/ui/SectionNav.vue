<template>
  <nav class="-mx-4 sm:mx-0 border-b border-border">
    <div class="flex gap-1.5 overflow-x-auto scrollbar-hide px-4 sm:px-1 py-2.5">
      <button v-for="s in sections" :key="s.id" type="button" @click="go(s.id)"
        :class="active === s.id ? 'bg-ink text-white' : 'text-muted hover:bg-[#F4F4F5] hover:text-ink'"
        class="shrink-0 inline-flex items-center gap-1.5 px-4 h-9 rounded-full text-sm font-semibold transition-colors">
        {{ s.label }}
        <span v-if="s.count != null" :class="active === s.id ? 'text-white/70' : 'text-muted/70'" class="text-xs">{{ s.count }}</span>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  sections: { type: Array, required: true }, // [{ id, label, count? }]
  offset:   { type: Number, default: 150 },  // sticky header + nav height
})

const active = ref(props.sections[0]?.id ?? '')
let observer = null

const go = (id) => {
  const el = document.getElementById(id)
  if (!el) return
  const y = el.getBoundingClientRect().top + window.scrollY - props.offset
  window.scrollTo({ top: y, behavior: 'smooth' })
}

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => { if (e.isIntersecting) active.value = e.target.id })
    },
    { rootMargin: `-${props.offset}px 0px -55% 0px`, threshold: 0 },
  )
  props.sections.forEach((s) => {
    const el = document.getElementById(s.id)
    if (el) observer.observe(el)
  })
})

onUnmounted(() => observer?.disconnect())
</script>
