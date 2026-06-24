<template>
  <div>
    <div v-for="(g, gi) in groups" :key="gi" :class="gi < groups.length - 1 ? 'mb-4' : ''">
      <p class="text-sm font-semibold text-charcoal mb-2">
        {{ g.name }}<span v-if="modelValue[gi]" class="text-muted font-normal">: {{ modelValue[gi].label || modelValue[gi].value }}</span>
      </p>
      <div class="flex items-center gap-2.5 flex-wrap">
        <!-- Colour swatches -->
        <template v-if="g.type === 'color'">
          <button v-for="(o, oi) in g.options" :key="oi" type="button" @click="pick(gi, o)"
            :style="{ backgroundColor: o.value }"
            :class="modelValue[gi] === o ? 'ring-2 ring-ink ring-offset-2' : 'ring-1 ring-border hover:ring-purple-300'"
            class="w-8 h-8 rounded-full transition-all" :aria-label="o.label || o.value" :title="o.label || o.value"></button>
        </template>
        <!-- Button options (size, material, …) -->
        <template v-else>
          <button v-for="(o, oi) in g.options" :key="oi" type="button" @click="pick(gi, o)"
            :class="modelValue[gi] === o ? 'border-ink bg-ink text-white' : 'border-border text-charcoal hover:border-ink'"
            class="min-w-11 h-10 px-3.5 rounded-xl border-2 text-sm font-semibold transition-all">{{ o.value }}</button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { variationGroups as groupsOf } from '@/utils/variations'

const props = defineProps({
  product: { type: Object, required: true },
  modelValue: { type: Object, default: () => ({}) }, // { groupIndex: option }
})
const emit = defineEmits(['update:modelValue'])

const groups = computed(() => groupsOf(props.product))
const pick = (gi, o) => emit('update:modelValue', { ...props.modelValue, [gi]: o })
</script>
