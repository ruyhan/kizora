<template>
  <!-- Overlay -->
  <transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 bg-black/50 z-40 backdrop-blur-sm" @click="close"></div>
  </transition>

  <!-- Drawer -->
  <transition name="slide-right">
    <div v-if="isOpen"
      class="fixed right-0 top-0 h-full w-full max-w-md bg-white z-50 flex flex-col shadow-2xl">

      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-purple-100 gradient-soft">
        <div class="flex items-center gap-3">
          <ShoppingBagIcon class="w-5 h-5 text-primary" />
          <h2 class="font-display text-xl font-semibold text-charcoal">
            Your Cart
            <span v-if="cartStore.count > 0" class="ml-2 badge badge-purple">{{ cartStore.count }}</span>
          </h2>
        </div>
        <button @click="close"
          class="p-2 rounded-full hover:bg-purple-100 text-muted hover:text-charcoal transition-all duration-200">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Free delivery banner (admin-controlled) -->
      <div v-if="cartStore.items?.length && del.message" class="px-6 pt-4">
        <p v-if="del.qualifiesFree(cartStore.subtotal)" class="text-xs font-semibold text-success flex items-center gap-1.5 bg-green-50 border border-green-100 rounded-xl px-3 py-2">
          <TruckIcon class="w-4 h-4 shrink-0" /> {{ del.isFreeForAll ? 'Free delivery on all orders!' : 'Free delivery unlocked!' }}
        </p>
        <p v-else class="text-xs text-charcoal bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
          Add <span class="font-bold text-ink">৳{{ del.amountToFree(cartStore.subtotal).toLocaleString() }}</span> more for <span class="font-semibold">free delivery</span>
        </p>
      </div>

      <!-- Items -->
      <div class="flex-1 overflow-y-auto px-6 py-4">

        <!-- Loading -->
        <div v-if="cartStore.fetching && !cartStore.loaded" class="flex items-center justify-center h-40">
          <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="!cartStore.items?.length"
          class="flex flex-col items-center justify-center h-48 text-center">
          <div class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center mb-4">
            <ShoppingBagIcon class="w-8 h-8 text-primary/40" />
          </div>
          <p class="text-charcoal font-semibold mb-1">Your cart is empty</p>
          <p class="text-muted text-sm mb-4">Add some products to get started</p>
          <Link :href="route('shop.index')" @click="close" class="btn-primary text-sm">
            Browse Shop
          </Link>
        </div>

        <!-- Items List -->
        <div v-else class="space-y-4">
          <div v-for="item in cartStore.items" :key="item.id"
            class="flex gap-4 p-4 bg-purple-50/50 rounded-2xl items-start group">
            <img :src="item.image" :alt="item.name"
              class="w-16 h-16 object-cover rounded-xl shrink-0">

            <!-- Middle: name / category / price / qty -->
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-charcoal text-sm leading-tight truncate">{{ item.name }}</p>
              <p class="text-xs text-muted mt-0.5">{{ item.category }}</p>
              <VariationTags :variations="item.variations" dense />
              <p class="text-primary font-bold mt-1">৳{{ Number(item.price).toFixed(2) }}</p>

              <!-- Quantity Controls -->
              <div class="flex items-center gap-2 mt-2">
                <button @click="updateQty(item, -1)"
                  :disabled="updatingId === item.id"
                  class="w-7 h-7 rounded-full bg-white border border-purple-200 text-primary hover:bg-primary hover:text-white transition-all duration-150 flex items-center justify-center disabled:opacity-40 active:scale-90">
                  <MinusIcon class="w-3 h-3" />
                </button>
                <span class="text-sm font-semibold text-charcoal w-5 text-center tabular-nums">{{ item.quantity }}</span>
                <button @click="updateQty(item, 1)"
                  :disabled="updatingId === item.id || item.quantity >= item.stock"
                  class="w-7 h-7 rounded-full bg-white border border-purple-200 text-primary hover:bg-primary hover:text-white transition-all duration-150 flex items-center justify-center disabled:opacity-40 active:scale-90">
                  <PlusIcon class="w-3 h-3" />
                </button>
              </div>
            </div>

            <!-- Right: subtotal (top) + remove (bottom) -->
            <div class="flex flex-col items-end justify-between self-stretch shrink-0">
              <p class="font-bold text-charcoal text-sm">৳{{ (item.price * item.quantity).toFixed(2) }}</p>
              <button @click="removeItem(item.id)"
                :disabled="removingId === item.id"
                class="p-1.5 rounded-lg text-gray-300 hover:text-rose-500 hover:bg-rose-50 transition-all duration-150 disabled:opacity-40"
                aria-label="Remove item">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>

          </div>
        </div>

      </div>

      <!-- Footer Summary -->
      <div v-if="cartStore.items?.length" class="px-6 py-5 border-t border-purple-100 bg-white">
        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-sm text-muted">
            <span>Subtotal</span>
            <span>৳{{ cartStore.subtotal?.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-sm text-muted">
            <span>Delivery</span>
            <span :class="cartStore.delivery_charge === 0 ? 'text-success font-semibold' : ''">
              {{ cartStore.delivery_charge === 0 ? 'FREE' : '৳' + cartStore.delivery_charge }}
            </span>
          </div>
          <div v-if="cartStore.discount > 0" class="flex justify-between text-sm">
            <span class="text-success">Discount</span>
            <span class="text-success font-semibold">-৳{{ cartStore.discount?.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-bold text-charcoal pt-2 border-t border-purple-100">
            <span>Total</span>
            <span class="text-primary text-lg">৳{{ cartStore.total?.toFixed(2) }}</span>
          </div>
        </div>
        <div class="flex gap-3">
          <Link :href="route('cart.index')" @click="close" class="btn-secondary flex-1 text-center text-sm">
            View Cart
          </Link>
          <Link :href="route('checkout.index')" @click="close" class="btn-primary flex-1 text-center text-sm">
            Checkout
          </Link>
        </div>
        <p v-if="del.message" class="text-center text-xs text-success font-medium mt-3">{{ del.message }}</p>
        <p v-else class="text-center text-xs text-muted mt-3">Delivery charge calculated at checkout by location.</p>
      </div>

    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart'
import { useDelivery } from '@/composables/useDelivery'
import VariationTags from '@/Components/ui/VariationTags.vue'
import {
  XMarkIcon, ShoppingBagIcon, TrashIcon, PlusIcon, MinusIcon, TruckIcon,
} from '@heroicons/vue/24/outline'

const cartStore = useCartStore()
const del = useDelivery()
const toast     = useToast()
const isOpen    = ref(false)

const updatingId = ref(null)
const removingId = ref(null)

const open  = async () => { isOpen.value = true; await cartStore.fetch() }
const close = () => { isOpen.value = false }

const updateQty = async (item, delta) => {
  if (updatingId.value === item.id) return
  updatingId.value = item.id
  try {
    const newQty = item.quantity + delta
    if (newQty < 1) {
      await cartStore.removeItem(item.id)
      toast.success('Item removed from cart')
    } else {
      await cartStore.updateItem(item.id, newQty)
    }
  } catch {
    toast.error('Could not update cart.')
  } finally {
    updatingId.value = null
  }
}

const removeItem = async (id) => {
  if (removingId.value === id) return
  removingId.value = id
  try {
    await cartStore.removeItem(id)
    toast.success('Item removed from cart')
  } catch {
    toast.error('Could not remove item.')
  } finally {
    removingId.value = null
  }
}

onMounted(()   => window.addEventListener('open-cart', open))
onUnmounted(() => window.removeEventListener('open-cart', open))
</script>

<style scoped>
.slide-right-enter-active, .slide-right-leave-active {
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-right-enter-from, .slide-right-leave-to { transform: translateX(100%); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
