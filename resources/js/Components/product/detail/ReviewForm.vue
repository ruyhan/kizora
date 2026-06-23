<template>
  <div class="p-5 sm:p-6">
    <!-- Not logged in -->
    <div v-if="!auth?.user" class="py-6 text-center">
      <p class="text-muted mb-4">Please sign in to submit a review.</p>
      <Button :href="route('login')" size="sm">Sign In</Button>
    </div>

    <!-- Already reviewed -->
    <div v-else-if="product.user_has_reviewed" class="py-6 text-center">
      <div class="flex items-center justify-center gap-2 text-success mb-2">
        <CheckCircleIcon class="w-5 h-5" />
        <span class="font-semibold">Review submitted</span>
      </div>
      <p class="text-muted text-sm">You have already reviewed this product. Thank you!</p>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="submit" class="space-y-5">
      <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-xl">
        <div class="w-9 h-9 rounded-full bg-ink flex items-center justify-center text-white text-sm font-bold shrink-0">{{ auth.user.name[0]?.toUpperCase() }}</div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-charcoal truncate">{{ auth.user.name }}</p>
          <p class="text-xs text-muted truncate">{{ auth.user.email }}</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-charcoal mb-2">Your rating <span class="text-error">*</span></label>
        <div class="flex gap-1.5" role="group" aria-label="Star rating">
          <button v-for="star in 5" :key="star" type="button"
            @click="form.rating = star" @mouseenter="hover = star" @mouseleave="hover = 0"
            :aria-label="`${star} star${star > 1 ? 's' : ''}`"
            class="transition-transform duration-100 active:scale-90 focus:outline-none">
            <StarIconSolid :class="star <= (hover || form.rating) ? 'text-warning' : 'text-gray-200'"
              class="w-8 h-8 sm:w-9 sm:h-9 transition-colors duration-100" />
          </button>
        </div>
        <p v-if="errors.rating" class="text-error text-xs mt-1.5">{{ errors.rating }}</p>
      </div>

      <div>
        <label class="block text-sm font-semibold text-charcoal mb-1.5">Your review <span class="text-error">*</span></label>
        <textarea v-model="form.comment" rows="4" placeholder="Share your experience with this product…"
          class="input-field resize-none" :class="{ 'border-error': errors.comment }"></textarea>
        <div class="flex items-center justify-between mt-1">
          <p v-if="errors.comment" class="text-error text-xs">{{ errors.comment }}</p>
          <p class="text-xs text-muted ml-auto">{{ form.comment.length }}/1000</p>
        </div>
      </div>

      <RecaptchaCheckbox form="review" />

      <Button type="submit" :loading="submitting" size="md">Submit review</Button>
      <p v-if="errors.review" class="text-error text-sm">{{ errors.review }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import Button from '@/Components/ui/Button.vue'
import RecaptchaCheckbox from '@/Components/RecaptchaCheckbox.vue'
import { useRecaptcha } from '@/composables/useRecaptcha'
import { CheckCircleIcon } from '@heroicons/vue/24/outline'
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({ product: { type: Object, required: true } })
const emit = defineEmits(['submitted'])

const auth  = usePage().props.auth
const toast = useToast()
const { getToken } = useRecaptcha()

const hover      = ref(0)
const submitting = ref(false)
const form       = ref({ rating: 0, comment: '' })
const errors     = ref({})

const submit = () => {
  errors.value = {}
  if (!form.value.rating) { errors.value.rating = 'Please select a star rating.'; return }
  const c = form.value.comment.trim()
  if (!c || c.length < 5) { errors.value.comment = 'Please write at least 5 characters.'; return }
  if (form.value.comment.length > 1000) { errors.value.comment = 'Review must be under 1000 characters.'; return }

  submitting.value = true
  getToken('review').then((token) => {
    router.post(route('review.store', props.product.slug), {
      rating: form.value.rating, comment: c, recaptcha_token: token,
    }, {
      preserveScroll: true,
      onSuccess: () => { toast.success('Review submitted successfully!'); form.value = { rating: 0, comment: '' }; hover.value = 0; emit('submitted') },
      onError: (e) => { errors.value = e; if (e.review) toast.error(e.review) },
      onFinish: () => { submitting.value = false },
    })
  }).catch(() => { errors.value = { review: 'Please complete the verification.' }; submitting.value = false })
}
</script>
