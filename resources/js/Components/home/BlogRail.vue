<template>
  <section v-if="posts.length" class="py-12 sm:py-16 bg-[#F4F4F5]">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-end justify-between mb-6 gap-3">
        <div>
          <h2 class="section-title">{{ title }}</h2>
          <p v-if="subtitle" class="text-muted mt-1 text-sm sm:text-base">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <div class="hidden lg:flex items-center gap-2">
            <button @click="prev" :disabled="atStart" class="arrow-btn" aria-label="Previous"><ChevronLeftIcon class="w-4 h-4" /></button>
            <button @click="next" :disabled="atEnd" class="arrow-btn" aria-label="Next"><ChevronRightIcon class="w-4 h-4" /></button>
          </div>
          <Link :href="route('blog.index')" class="text-sm font-semibold text-ink hover:text-accent transition-colors whitespace-nowrap">View all →</Link>
        </div>
      </div>

      <Swiper
        :slides-per-view="1.15" :space-between="16" :breakpoints="breakpoints"
        @swiper="onSwiper" @slideChange="onEdges" @resize="onEdges" @breakpoint="onEdges"
      >
        <SwiperSlide v-for="p in posts" :key="p.id" class="h-auto">
          <Link :href="route('blog.show', p.slug)" class="group flex flex-col h-full rounded-2xl overflow-hidden border border-border bg-surface hover:shadow-lg transition-shadow">
            <div class="aspect-video bg-purple-50 overflow-hidden">
              <img v-if="p.featured_image_url" :src="p.featured_image_url" :alt="p.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </div>
            <div class="p-4 flex flex-col flex-1">
              <span v-if="p.category" class="text-xs font-semibold text-primary">{{ p.category.name }}</span>
              <h3 class="font-display font-bold text-charcoal group-hover:text-primary transition-colors line-clamp-2 mt-1">{{ p.title }}</h3>
              <p class="text-muted text-sm mt-1.5 line-clamp-2 flex-1">{{ p.excerpt }}</p>
              <div class="flex items-center gap-2 text-xs text-muted mt-3 pt-3 border-t border-border">
                <span v-if="p.published_at">{{ p.published_at }}</span>
                <span class="ml-auto">{{ p.reading_time }} min read</span>
              </div>
            </div>
          </Link>
        </SwiperSlide>
      </Swiper>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({
  title:    { type: String, default: 'From the blog' },
  subtitle: { type: String, default: '' },
  posts:    { type: Array, default: () => [] },
})

const breakpoints = {
  640:  { slidesPerView: 2, spaceBetween: 20 },
  768:  { slidesPerView: 3, spaceBetween: 20 },
  1024: { slidesPerView: 4, spaceBetween: 24 },
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
         hover:bg-white hover:border-[#D5D5D9] disabled:opacity-30 disabled:cursor-not-allowed transition-all;
}
:deep(.swiper-slide) { height: auto; }
</style>
