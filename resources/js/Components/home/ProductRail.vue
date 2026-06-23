<template>
  <section v-if="products.length" class="py-12 sm:py-16">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-end justify-between mb-6 gap-3">
        <div>
          <div class="flex items-center gap-2.5">
            <h2 class="section-title">{{ title }}</h2>
            <span v-if="badge" class="badge badge-accent">{{ badge }}</span>
          </div>
          <p v-if="subtitle" class="text-muted mt-1 text-sm sm:text-base">{{ subtitle }}</p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <!-- Desktop slide arrows -->
          <div class="hidden lg:flex items-center gap-2">
            <button @click="prev" :disabled="atStart" class="arrow-btn" aria-label="Previous">
              <ChevronLeftIcon class="w-4 h-4" />
            </button>
            <button @click="next" :disabled="atEnd" class="arrow-btn" aria-label="Next">
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>
          <Link v-if="viewAll" :href="viewAll" class="text-sm font-semibold text-ink hover:text-accent transition-colors whitespace-nowrap">View all →</Link>
        </div>
      </div>

      <!-- Mobile: free swipe (peek). Desktop: 4-up carousel via arrows. -->
      <Swiper
        :slides-per-view="1.7" :space-between="16"
        :breakpoints="breakpoints"
        @swiper="onSwiper" @slideChange="onEdges" @resize="onEdges" @breakpoint="onEdges"
      >
        <SwiperSlide v-for="p in products" :key="p.id" class="h-auto">
          <ProductCard :product="p" :wishlisted-ids="wishlistedIds" @quickview="$emit('quickview', $event)" />
        </SwiperSlide>
      </Swiper>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Swiper, SwiperSlide } from 'swiper/vue'
import ProductCard from '@/Components/ProductCard.vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
  badge:    { type: String, default: '' },
  products: { type: Array, default: () => [] },
  viewAll:  { type: String, default: '' },
  tinted:   { type: Boolean, default: false },
  wishlistedIds: { type: Array, default: () => [] },
})
defineEmits(['quickview'])

const breakpoints = {
  640:  { slidesPerView: 2.4, spaceBetween: 20 },
  768:  { slidesPerView: 3,   spaceBetween: 20 },
  1024: { slidesPerView: 4,   spaceBetween: 24 },
}

const swiper  = ref(null)
const atStart = ref(true)
const atEnd   = ref(false)

const onSwiper = (s) => { swiper.value = s; onEdges(s) }
const onEdges  = (s) => { const i = s || swiper.value; if (!i) return; atStart.value = i.isBeginning; atEnd.value = i.isEnd }
const prev = () => swiper.value?.slidePrev()
const next = () => swiper.value?.slideNext()
</script>

<style scoped>
@reference "../../../css/app.css";
.arrow-btn {
  @apply w-9 h-9 rounded-full border border-border bg-surface flex items-center justify-center text-ink
         hover:bg-[#F4F4F5] hover:border-[#D5D5D9] disabled:opacity-30 disabled:cursor-not-allowed transition-all;
}
/* let cards stretch to equal height across the row */
:deep(.swiper-slide) { height: auto; }
</style>
