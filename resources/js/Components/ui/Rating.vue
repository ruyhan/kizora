<template>
  <div class="inline-flex items-center gap-1" role="img" :aria-label="`Rated ${value} out of 5`">
    <div class="flex" :class="gap">
      <StarIcon
        v-for="i in 5" :key="i"
        :class="[star, i <= Math.round(value) ? 'text-warning' : 'text-[#D8D8DC]']"
      />
    </div>
    <span v-if="showValue" class="text-muted leading-none" :class="textCls">
      {{ Number(value).toFixed(1) }}<span v-if="count != null" class="text-muted/70"> ({{ count }})</span>
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { StarIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  value:     { type: Number, default: 0 },
  count:     { type: [Number, null], default: null },
  size:      { type: String, default: 'sm' }, // sm | md
  showValue: { type: Boolean, default: true },
})

const star    = computed(() => (props.size === 'md' ? 'w-4 h-4' : 'w-3.5 h-3.5'))
const gap     = computed(() => (props.size === 'md' ? 'gap-0.5' : 'gap-px'))
const textCls = computed(() => (props.size === 'md' ? 'text-sm' : 'text-xs'))
</script>
