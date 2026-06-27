<template>
  <Teleport to="body">
    <Transition name="rm-fade">
      <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="close"></div>

        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-border overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-border">
            <h3 class="font-display font-semibold text-charcoal">Leave a review</h3>
            <button type="button" @click="close" class="text-muted hover:text-ink transition-colors">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="p-5 space-y-4">
            <p v-if="!reviewable.length" class="text-sm text-muted text-center py-6">
              You've already reviewed everything in this order. Thank you! 🙏
            </p>

            <div v-else class="space-y-4">
              <div v-if="reviewable.length > 1" class="space-y-1.5">
                <label class="text-sm font-medium text-charcoal">Which product?</label>
                <button v-for="it in reviewable" :key="it.id" type="button" @click="selected = it"
                  :class="selected?.id === it.id ? 'border-ink bg-purple-50' : 'border-border hover:border-ink/40'"
                  class="w-full flex items-center gap-2.5 p-2 rounded-xl border text-left transition-colors">
                  <img :src="img(it)" class="w-9 h-9 rounded object-cover bg-purple-50 shrink-0" alt="">
                  <span class="text-sm text-charcoal flex-1 truncate">{{ name(it) }}</span>
                  <CheckCircleIcon v-if="selected?.id === it.id" class="w-4 h-4 text-ink shrink-0" />
                </button>
              </div>

              <div v-if="reviewable.length === 1 && selected" class="flex items-center gap-3">
                <img :src="img(selected)" class="w-12 h-12 rounded-lg object-cover bg-purple-50 shrink-0" alt="">
                <p class="text-sm font-semibold text-charcoal">{{ name(selected) }}</p>
              </div>

              <div v-if="selected">
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Your rating</label>
                <div class="flex gap-1" @mouseleave="hover = 0">
                  <button v-for="n in 5" :key="n" type="button" @click="rating = n" @mouseenter="hover = n" class="p-0.5">
                    <StarSolid v-if="(hover || rating) >= n" class="w-7 h-7 text-amber-400" />
                    <StarOutline v-else class="w-7 h-7 text-gray-300" />
                  </button>
                </div>
                <p v-if="errors.rating" class="text-error text-xs mt-1">{{ errors.rating }}</p>

                <label class="text-sm font-medium text-charcoal mt-4 mb-1.5 block">Your review</label>
                <textarea v-model="comment" rows="4" class="input-field" placeholder="Share your experience with this product…"></textarea>
                <p v-if="errors.comment || errors.review" class="text-error text-xs mt-1">{{ errors.comment || errors.review }}</p>

                <RecaptchaCheckbox form="review" class="mt-3" />
              </div>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-border flex justify-end gap-2">
            <button type="button" @click="close" class="btn-secondary text-sm px-4">Close</button>
            <button v-if="selected" type="button" @click="submit" :disabled="processing"
              class="btn-primary text-sm px-5 disabled:opacity-60">
              {{ processing ? 'Submitting…' : 'Submit review' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'
import { XMarkIcon, CheckCircleIcon, StarIcon as StarOutline } from '@heroicons/vue/24/outline'
import { StarIcon as StarSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
  open: Boolean,
  order: { type: Object, default: () => ({}) },
  preselectId: { type: [Number, String], default: null },
})
const emit = defineEmits(['close', 'submitted'])

const toast = useToast()
const { getToken } = useRecaptcha()
const PLACEHOLDER = '/images/product-placeholder.svg'

// Items that can still be reviewed (have a product + not yet reviewed)
const reviewable = computed(() => (props.order?.items || []).filter((i) => i.slug && !i.reviewed))
const name = (i) => i?.name || i?.product_name || 'Product'
const img = (i) => i?.image || i?.product_image || PLACEHOLDER

const selected = ref(null)
const rating = ref(0)
const hover = ref(0)
const comment = ref('')
const processing = ref(false)
const errors = ref({})

watch(() => props.open, (o) => {
  if (o) {
    selected.value = reviewable.value.find((i) => i.id === props.preselectId) || reviewable.value[0] || null
    rating.value = 0
    hover.value = 0
    comment.value = ''
    errors.value = {}
  }
})

const close = () => emit('close')

const submit = async () => {
  errors.value = {}
  if (!selected.value) return
  if (!rating.value) { errors.value = { rating: 'Please select a star rating.' }; return }
  if (comment.value.trim().length < 5) { errors.value = { comment: 'Please write at least 5 characters.' }; return }

  processing.value = true
  let token = null
  try {
    token = await getToken('review')
  } catch {
    errors.value = { review: 'Please complete the verification.' }
    processing.value = false
    return
  }

  router.post(route('review.store', selected.value.slug), {
    rating: rating.value,
    comment: comment.value,
    recaptcha_token: token,
  }, {
    preserveScroll: true,
    onSuccess: () => { toast.success('Thank you! Your review has been submitted.'); emit('submitted'); emit('close') },
    onError: (e) => { errors.value = e },
    onFinish: () => { processing.value = false },
  })
}
</script>

<style scoped>
.rm-fade-enter-active, .rm-fade-leave-active { transition: opacity .2s ease; }
.rm-fade-enter-from, .rm-fade-leave-to { opacity: 0; }
</style>
