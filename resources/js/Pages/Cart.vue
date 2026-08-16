<template>
  <AppLayout>
    <Head title="Shopping Cart" />

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="font-display text-3xl md:text-4xl font-bold text-charcoal mb-8">Shopping Cart</h1>

      <div v-if="cart.items?.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-24 h-24 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-6">
          <ShoppingCartIcon class="w-12 h-12 text-primary/50" />
        </div>
        <h2 class="font-display text-2xl font-bold text-charcoal mb-3">Your cart is empty</h2>
        <p class="text-muted mb-8">Looks like you haven't added anything yet.</p>
        <Link :href="route('shop.index')" class="btn-primary">Start Shopping</Link>
      </div>

      <div v-else class="grid lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2 space-y-3 sm:space-y-4">
          <div v-for="item in cart.items" :key="item.id"
            class="card p-3 sm:p-5">
            <div class="flex gap-3 sm:gap-5 items-start">
              <img :src="item.image" :alt="item.name"
                class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-xl sm:rounded-2xl shrink-0">
              <div class="flex-1 min-w-0">
                <span class="badge badge-purple mb-1 text-[10px] sm:text-xs">{{ item.category }}</span>
                <h3 class="font-semibold text-charcoal text-sm sm:text-base leading-tight line-clamp-2">{{ item.name }}</h3>
                <VariationTags :variations="item.variations" />
                <p class="text-primary font-bold text-sm mt-0.5">৳{{ formatPrice(item.price) }} each</p>

                <!-- Quantity + subtotal row -->
                <div class="flex items-center justify-between mt-2 sm:mt-3 gap-2 flex-wrap">
                  <div class="flex items-center border-2 border-purple-100 rounded-full overflow-hidden">
                    <button @click="updateQty(item, -1)"
                      class="px-2.5 sm:px-3 py-1.5 sm:py-2 text-primary hover:bg-purple-50 active:bg-purple-100 transition-colors font-bold text-sm">−</button>
                    <span class="px-2.5 sm:px-3 py-1.5 sm:py-2 font-semibold text-charcoal min-w-10 text-center text-sm">{{ item.quantity }}</span>
                    <button @click="updateQty(item, 1)"
                      :disabled="item.quantity >= item.stock"
                      class="px-2.5 sm:px-3 py-1.5 sm:py-2 text-primary hover:bg-purple-50 active:bg-purple-100 transition-colors font-bold text-sm disabled:opacity-50">+</button>
                  </div>
                  <div class="flex items-center gap-3 ml-auto">
                    <p class="font-bold text-charcoal text-sm sm:text-base">৳{{ formatPrice(item.price * item.quantity) }}</p>
                    <button @click="removeItem(item.id)"
                      class="text-error hover:text-red-700 active:scale-90 transition-all text-xs flex items-center gap-1">
                      <TrashIcon class="w-3.5 h-3.5" />
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="card p-6 sticky top-24">
            <h3 class="font-display font-semibold text-charcoal text-xl mb-6">Order Summary</h3>

            <!-- Coupon -->
            <form @submit.prevent="applyCoupon" class="mb-6">
              <label class="text-sm font-medium text-charcoal mb-2 block">Coupon Code</label>
              <div class="flex gap-2">
                <input v-model="couponCode" type="text" placeholder="Enter code" class="input-field flex-1 text-sm">
                <button type="submit" :disabled="applyingCoupon" class="btn-primary text-sm px-4 py-2.5">Apply</button>
              </div>
              <p v-if="couponError" class="text-error text-xs mt-2">{{ couponError }}</p>
            </form>

            <!-- Summary -->
            <div class="space-y-3 mb-6">
              <div class="flex justify-between text-sm">
                <span class="text-muted">Subtotal ({{ cart.count }} items)</span>
                <span class="font-medium text-charcoal">৳{{ formatPrice(cart.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-muted">Delivery Charge</span>
                <span v-if="del.isManual(cart.subtotal)" class="text-success font-medium">To be added</span>
                <span v-else :class="cart.delivery_charge === 0 ? 'text-success font-semibold' : 'text-charcoal font-medium'">
                  {{ cart.delivery_charge === 0 ? 'FREE' : '৳' + formatPrice(cart.delivery_charge) }}
                </span>
              </div>
              <div v-if="cart.discount > 0" class="flex justify-between text-sm">
                <span class="text-success">Discount</span>
                <span class="text-success font-semibold">-৳{{ formatPrice(cart.discount) }}</span>
              </div>
              <hr class="border-purple-100">
              <div class="flex justify-between font-bold text-lg">
                <span class="text-charcoal">Total</span>
                <span class="text-primary">৳{{ formatPrice(cart.total) }}</span>
              </div>
            </div>

            <p v-if="del.isManual(cart.subtotal)" class="text-xs text-success font-medium mb-4 text-center">
              {{ del.locationMessage }}
            </p>
            <template v-else-if="del.message">
              <p v-if="del.qualifiesFree(cart.subtotal)" class="text-xs text-success mb-4 text-center font-semibold flex items-center justify-center gap-1">
                <SparklesIcon class="w-3.5 h-3.5" /> {{ del.isFreeForAll ? 'Free delivery on all orders!' : 'You qualify for free delivery!' }}
              </p>
              <p v-else class="text-xs text-muted mb-4 text-center">
                Add <span class="font-semibold text-charcoal">৳{{ formatPrice(del.amountToFree(cart.subtotal)) }}</span> more for free delivery
              </p>
            </template>
            <p v-else class="text-xs text-muted mb-4 text-center">
              Final delivery charge is calculated at checkout based on your location.
            </p>

            <Link :href="route('checkout.index')" class="btn-primary w-full text-center block text-lg py-4">
              Proceed to Checkout
            </Link>

            <Link :href="route('shop.index')" class="text-center text-sm text-muted hover:text-primary transition-colors mt-4 block">
              Continue Shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCartStore } from '@/stores/cart'
import { useDelivery } from '@/composables/useDelivery'
import VariationTags from '@/Components/ui/VariationTags.vue'
import { ShoppingCartIcon, TrashIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ cart: Object })
const toast = useToast()
const cartStore = useCartStore()
const del = useDelivery()
const couponCode = ref('')
const couponError = ref('')
const applyingCoupon = ref(false)

const formatPrice = (p) => Number(p || 0).toLocaleString()

const updateQty = (item, delta) => {
  const newQty = item.quantity + delta
  if (newQty < 1) return removeItem(item.id)
  router.patch(route('cart.update', item.id), { quantity: newQty }, {
    preserveScroll: true,
    onSuccess: () => cartStore.fetch(),
  })
}

const removeItem = (id) => {
  router.delete(route('cart.remove', id), {
    preserveScroll: true,
    onSuccess: () => cartStore.fetch(),
  })
}

const applyCoupon = () => {
  applyingCoupon.value = true
  couponError.value = ''
  router.post(route('cart.coupon'), { code: couponCode.value }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Coupon applied!')
      applyingCoupon.value = false
    },
    onError: (errors) => {
      couponError.value = errors.coupon
      applyingCoupon.value = false
    },
  })
}
</script>
