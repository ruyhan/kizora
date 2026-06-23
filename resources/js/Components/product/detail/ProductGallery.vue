<template>
  <div class="min-w-0">
    <!-- Main image -->
    <button type="button" @click="zoomOpen = true"
      class="relative block w-full aspect-square rounded-3xl overflow-hidden bg-purple-50 cursor-zoom-in group">
      <img :src="active" :alt="product.name" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
      <span class="absolute bottom-3 right-3 w-9 h-9 rounded-full bg-surface/90 border border-border flex items-center justify-center text-ink opacity-0 group-hover:opacity-100 transition-opacity">
        <MagnifyingGlassPlusIcon class="w-4 h-4" />
      </span>
    </button>

    <!-- Thumbnails (below the main image). p-1.5 gives the active ring room so it isn't clipped by overflow. -->
    <div v-if="allImages.length > 1" class="flex gap-2.5 overflow-x-auto scrollbar-hide mt-3 p-1.5">
      <button v-for="(img, i) in allImages" :key="i" @click="active = img" type="button"
        :class="active === img ? 'ring-2 ring-ink ring-offset-2' : 'ring-1 ring-border hover:ring-purple-300'"
        class="w-16 h-16 sm:w-[72px] sm:h-[72px] rounded-xl overflow-hidden shrink-0 transition-all duration-150 bg-purple-50">
        <img :src="img" :alt="product.name" class="w-full h-full object-cover" loading="lazy">
      </button>
    </div>

    <!-- Zoom lightbox -->
    <Modal :open="zoomOpen" @update:open="zoomOpen = $event" max-width="sm:max-w-3xl">
      <img :src="active" :alt="product.name" class="w-full h-auto object-contain bg-[#F4F4F5]">
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '@/Components/ui/Modal.vue'
import { MagnifyingGlassPlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  product:       { type: Object, required: true },
  overrideImage: { type: String, default: null },
})

const allImages = computed(() => [
  props.product.featured_image_url,
  ...(props.product.images || []).map((i) => i.image_url),
].filter(Boolean))

const active = ref(allImages.value[0])
const zoomOpen = ref(false)

// When a variation is selected, show its image as the main image.
watch(() => props.overrideImage, (img) => { if (img) active.value = img })
</script>
