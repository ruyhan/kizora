<template>
  <ProductRail v-if="list.length" title="Recently viewed" :products="list" tinted />
</template>

<script setup>
import { computed } from 'vue'
import ProductRail from '@/Components/home/ProductRail.vue'
import { useRecentlyViewed } from '@/composables/useRecentlyViewed'

const props = defineProps({ excludeSlug: { type: String, default: '' } })
const { items } = useRecentlyViewed()
const list = computed(() => items.value.filter((p) => p.slug !== props.excludeSlug))
</script>
