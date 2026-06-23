<template>
  <div>
    <!-- Header: title, average rating below, actions -->
    <div class="flex items-start justify-between gap-3 mb-6">
      <div>
        <h2 class="section-title">{{ reviewsTitle }}</h2>
        <Rating v-if="product.reviews?.length" :value="product.average_rating" :count="product.reviews.length" size="md" class="mt-2" />
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <div v-if="product.reviews && product.reviews.length > 1" class="hidden lg:flex items-center gap-2">
          <button @click="prev" :disabled="atStart" class="rev-arrow" aria-label="Previous reviews"><ChevronLeftIcon class="w-4 h-4" /></button>
          <button @click="next" :disabled="atEnd" class="rev-arrow" aria-label="Next reviews"><ChevronRightIcon class="w-4 h-4" /></button>
        </div>
        <Button variant="secondary" size="sm" @click="showForm = true">{{ writeReviewLabel }}</Button>
      </div>
    </div>

    <!-- Reviews slider (desktop arrows · mobile swipe) -->
    <Swiper v-if="product.reviews?.length"
      :slides-per-view="1.7" :space-between="16" :breakpoints="breakpoints"
      @swiper="onSwiper" @slideChange="onEdges" @resize="onEdges" @breakpoint="onEdges">
      <SwiperSlide v-for="review in product.reviews" :key="review.id" class="h-auto">
        <div class="card p-4 h-full flex flex-col">
          <div class="flex items-center gap-2.5 mb-2">
            <div class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-white text-xs font-bold shrink-0">
              {{ review.user?.name?.[0]?.toUpperCase() || '?' }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-charcoal text-sm truncate leading-tight">{{ review.user?.name || 'Customer' }}</p>
              <p class="text-[11px] text-muted/80 mt-0.5">{{ review.created_at }}</p>
            </div>
          </div>
          <Rating :value="review.rating" :show-value="false" size="sm" class="mb-2" />
          <p class="text-muted text-sm leading-relaxed" :class="expanded.includes(review.id) ? '' : 'line-clamp-4'">{{ review.comment }}</p>
          <button v-if="review.comment && review.comment.length > 140" @click="toggle(review.id)"
            class="text-primary text-xs font-medium hover:underline self-start mt-1.5">
            {{ expanded.includes(review.id) ? 'Show less' : 'Read more' }}
          </button>
        </div>
      </SwiperSlide>
    </Swiper>

    <!-- Empty state -->
    <div v-else class="rounded-2xl border border-border bg-white/70 p-8 text-center">
      <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
        <StarIcon class="w-7 h-7 text-primary/40" />
      </div>
      <p class="font-semibold text-charcoal mb-1">No reviews yet</p>
      <p class="text-muted text-sm mb-4">Be the first to review this product.</p>
      <Button variant="secondary" size="sm" @click="showForm = true">{{ writeReviewLabel }}</Button>
    </div>

    <!-- Write-review modal -->
    <Modal :open="showForm" @update:open="showForm = $event" :title="writeReviewLabel" max-width="sm:max-w-lg">
      <ReviewForm :product="product" @submitted="showForm = false" />
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Swiper, SwiperSlide } from 'swiper/vue'
import Rating from '@/Components/ui/Rating.vue'
import Button from '@/Components/ui/Button.vue'
import Modal from '@/Components/ui/Modal.vue'
import ReviewForm from '@/Components/product/detail/ReviewForm.vue'
import { ChevronLeftIcon, ChevronRightIcon, StarIcon } from '@heroicons/vue/24/outline'

defineProps({ product: { type: Object, required: true } })

const ps = computed(() => usePage().props.productSettings || {})
const reviewsTitle = computed(() => ps.value.reviews_title || 'Customer reviews')
const writeReviewLabel = computed(() => ps.value.write_review_label || 'Write a review')

const showForm = ref(false)

const breakpoints = {
  640:  { slidesPerView: 2, spaceBetween: 16 },
  1024: { slidesPerView: 3, spaceBetween: 20 },
}
const swiper  = ref(null)
const atStart = ref(true)
const atEnd   = ref(false)
const onSwiper = (s) => { swiper.value = s; onEdges(s) }
const onEdges  = (s) => { const i = s || swiper.value; if (!i) return; atStart.value = i.isBeginning; atEnd.value = i.isEnd }
const prev = () => swiper.value?.slidePrev()
const next = () => swiper.value?.slideNext()

const expanded = ref([])
const toggle = (id) => {
  const i = expanded.value.indexOf(id)
  if (i === -1) expanded.value.push(id)
  else expanded.value.splice(i, 1)
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.rev-arrow {
  @apply w-9 h-9 rounded-full border border-border bg-surface flex items-center justify-center text-ink
         hover:bg-[#F4F4F5] hover:border-[#D5D5D9] disabled:opacity-30 disabled:cursor-not-allowed transition-all;
}
:deep(.swiper-slide) { height: auto; }
</style>
