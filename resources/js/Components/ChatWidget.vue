<template>
  <div v-if="cfg.enabled" :class="dark ? 'kz-chat-dark' : ''">
    <!-- Floating button -->
    <button v-show="!open" @click="toggle" :style="{ background: cfg.primary_color }"
      :class="[pos, rounded ? 'rounded-full' : 'rounded-xl']"
      class="fixed z-[80] w-14 h-14 shadow-xl flex items-center justify-center text-white hover:scale-105 active:scale-95 transition-transform">
      <img v-if="cfg.widget_icon" :src="cfg.widget_icon" class="w-7 h-7 object-contain" alt="chat">
      <ChatBubbleOvalLeftEllipsisIcon v-else class="w-7 h-7" />
    </button>

    <!-- Panel -->
    <Transition name="kz-chat">
      <div v-if="open" :class="[pos, rounded ? 'rounded-3xl' : 'rounded-lg', dark ? 'bg-[#1b1c20] text-gray-100' : 'bg-white text-charcoal']"
        class="fixed z-[81] w-[92vw] max-w-[380px] h-[70vh] max-h-[600px] shadow-2xl border border-black/10 flex flex-col overflow-hidden">

        <!-- Header -->
        <div :style="{ background: cfg.primary_color }" class="flex items-center gap-3 px-4 py-3 text-white shrink-0">
          <img v-if="cfg.avatar" :src="cfg.avatar" class="w-9 h-9 rounded-full object-cover bg-white/20" alt="">
          <div v-else class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center"><SparklesIcon class="w-5 h-5" /></div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm leading-tight truncate flex items-center gap-1.5">
              <span class="relative flex w-2 h-2 shrink-0">
                <span class="absolute inline-flex w-full h-full rounded-full bg-green-400 opacity-75 animate-ping"></span>
                <span class="relative inline-flex w-2 h-2 rounded-full bg-green-400"></span>
              </span>
              <span class="truncate">{{ cfg.name }}</span>
            </p>
            <p class="text-[11px] text-white/70">Online · AI sales assistant</p>
          </div>
          <button @click="toggle" class="w-8 h-8 rounded-full hover:bg-white/15 flex items-center justify-center"><XMarkIcon class="w-5 h-5" /></button>
        </div>

        <!-- Messages -->
        <div ref="scroller" :class="dark ? 'bg-[#16171a]' : 'bg-[#F7F7F8]'" class="flex-1 overflow-y-auto p-3 space-y-3">
          <div v-for="(m, i) in messages" :key="i">
            <!-- bubble -->
            <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
              <div :class="[
                    m.role === 'user'
                      ? 'text-white rounded-br-sm'
                      : (dark ? 'bg-[#2a2b30] text-gray-100' : 'bg-white text-charcoal') + ' rounded-bl-sm border border-black/5',
                    rounded ? 'rounded-2xl' : 'rounded-md']"
                :style="m.role === 'user' ? { background: cfg.primary_color } : {}"
                class="max-w-[80%] px-3.5 py-2 text-sm leading-relaxed whitespace-pre-line shadow-sm">{{ m.text }}</div>
            </div>

            <!-- product carousel -->
            <div v-if="m.products && m.products.length" class="relative mt-2 group">
              <button v-if="m.products.length > 1" @click="scrollCarousel(i, -1)" class="carousel-arrow left-0"><ChevronLeftIcon class="w-4 h-4" /></button>
              <div :ref="el => setCarousel(i, el)" class="flex gap-2 overflow-x-auto scrollbar-hide scroll-smooth snap-x px-1 pb-1">
                <div v-for="p in m.products" :key="p.id" :class="[dark ? 'bg-[#2a2b30] border-white/10' : 'bg-white border-black/5']"
                  class="snap-start shrink-0 w-36 rounded-xl border overflow-hidden">
                  <img :src="p.image || '/images/product-placeholder.svg'" class="w-full h-24 object-cover bg-purple-50" alt="">
                  <div class="p-2">
                    <p class="text-xs font-semibold leading-tight line-clamp-2 h-8">{{ p.name }}</p>
                    <p class="text-sm font-bold mt-0.5" :style="{ color: cfg.primary_color }">৳{{ p.price.toLocaleString() }}</p>
                    <div class="flex gap-1 mt-1.5">
                      <button @click="addCart(p)" :style="{ background: cfg.primary_color }" class="flex-1 text-white text-[11px] py-1 rounded-md">Add</button>
                      <button @click="startOrder(p)" class="flex-1 border border-current text-[11px] py-1 rounded-md" :style="{ color: cfg.primary_color }">Order</button>
                    </div>
                  </div>
                </div>
              </div>
              <button v-if="m.products.length > 1" @click="scrollCarousel(i, 1)" class="carousel-arrow right-0"><ChevronRightIcon class="w-4 h-4" /></button>
            </div>
          </div>

          <!-- typing -->
          <div v-if="sending" class="flex justify-start">
            <div :class="dark ? 'bg-[#2a2b30]' : 'bg-white border border-black/5'" class="rounded-2xl px-3.5 py-2.5 flex gap-1">
              <span class="kz-dot"></span><span class="kz-dot" style="animation-delay:.15s"></span><span class="kz-dot" style="animation-delay:.3s"></span>
            </div>
          </div>

          <!-- order flow form -->
          <div v-if="order.active" :class="dark ? 'bg-[#2a2b30] border-white/10' : 'bg-white border-black/5'" class="rounded-2xl border p-3 space-y-2">
            <p class="text-xs font-semibold">Complete your order</p>
            <div v-for="it in order.items" :key="it.product.id" class="flex items-center gap-2 text-xs">
              <img :src="it.product.image || '/images/product-placeholder.svg'" class="w-8 h-8 rounded object-cover" alt="">
              <span class="flex-1 truncate">{{ it.product.name }}</span>
              <span>× {{ it.quantity }}</span>
            </div>
            <input v-model="order.form.name" placeholder="Your name *" class="kz-in">
            <input v-model="order.form.phone" placeholder="Phone *" class="kz-in">
            <input v-model="order.form.address" placeholder="Delivery address *" class="kz-in">
            <div class="flex gap-2">
              <button @click="order.form.inside_dhaka = true" :class="order.form.inside_dhaka ? 'ring-2' : 'opacity-60'" :style="{ '--tw-ring-color': cfg.primary_color }" class="flex-1 kz-in text-center">Inside Dhaka</button>
              <button @click="order.form.inside_dhaka = false" :class="!order.form.inside_dhaka ? 'ring-2' : 'opacity-60'" :style="{ '--tw-ring-color': cfg.primary_color }" class="flex-1 kz-in text-center">Outside Dhaka</button>
            </div>
            <p v-if="order.error" class="text-error text-[11px]">{{ order.error }}</p>
            <div class="flex gap-2 pt-1">
              <button @click="order.active = false" class="flex-1 text-xs py-1.5 rounded-md border border-current opacity-70">Cancel</button>
              <button @click="confirmOrder" :disabled="order.placing" :style="{ background: cfg.primary_color }" class="flex-1 text-white text-xs py-1.5 rounded-md">{{ order.placing ? 'Placing…' : 'Confirm order' }}</button>
            </div>
          </div>
        </div>

        <!-- Input -->
        <form @submit.prevent="send" :class="dark ? 'bg-[#1b1c20] border-white/10' : 'bg-white border-black/5'" class="flex items-center gap-2 p-2.5 border-t shrink-0">
          <input v-model="input" :disabled="sending" placeholder="Ask me anything…" :class="dark ? 'bg-[#2a2b30] text-gray-100' : 'bg-[#F1F1F2] text-charcoal'" class="flex-1 px-3 py-2 rounded-full text-sm outline-none">
          <button type="submit" :disabled="!input.trim() || sending" :style="{ background: cfg.primary_color }" class="w-9 h-9 rounded-full text-white flex items-center justify-center disabled:opacity-50 shrink-0"><PaperAirplaneIcon class="w-4 h-4" /></button>
        </form>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart'
import {
  ChatBubbleOvalLeftEllipsisIcon, SparklesIcon, XMarkIcon, PaperAirplaneIcon,
  ChevronLeftIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const cfg = computed(() => usePage().props.chatbot || { enabled: false })
const dark = computed(() => cfg.value.theme === 'dark')
const rounded = computed(() => cfg.value.rounded !== false)
// On mobile the launcher must clear the 64px bottom nav (md:hidden); on desktop it drops back to bottom-5.
const pos = computed(() => (cfg.value.position === 'bottom-left' ? 'bottom-20 left-5 md:bottom-5' : 'bottom-20 right-5 md:bottom-5'))

const toast = useToast()
const cart = useCartStore()

const open = ref(false)
const input = ref('')
const sending = ref(false)
const messages = ref([])
const scroller = ref(null)
const carousels = {}

const toggle = () => {
  open.value = !open.value
  if (open.value && !messages.value.length) {
    messages.value.push({ role: 'assistant', text: cfg.value.welcome_message })
  }
  scrollDown()
}

const scrollDown = () => nextTick(() => { if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight })

const send = async () => {
  const text = input.value.trim()
  if (!text || sending.value) return
  messages.value.push({ role: 'user', text })
  input.value = ''
  sending.value = true
  scrollDown()
  try {
    const { data } = await axios.post(route('chat.message'), { message: text })
    messages.value.push({ role: 'assistant', text: data.reply, products: data.products || [] })
  } catch {
    messages.value.push({ role: 'assistant', text: 'Sorry, I had trouble responding. Please try again.' })
  } finally {
    sending.value = false
    scrollDown()
  }
}

// Carousel
const setCarousel = (i, el) => { if (el) carousels[i] = el }
const scrollCarousel = (i, dir) => { const el = carousels[i]; if (el) el.scrollBy({ left: dir * 150, behavior: 'smooth' }) }

const addCart = async (p) => {
  axios.post(route('chat.event'), { type: 'product_click', product_id: p.id }).catch(() => {})
  try { await cart.add(p.id, 1); toast.success(`${p.name} added to cart`) } catch { toast.error('Could not add to cart.') }
}

// Order flow
const order = reactive({ active: false, items: [], placing: false, error: '', form: { name: '', phone: '', address: '', inside_dhaka: true } })
const startOrder = (p) => {
  axios.post(route('chat.event'), { type: 'product_click', product_id: p.id }).catch(() => {})
  order.items = [{ product: p, quantity: 1 }]
  order.error = ''
  order.active = true
  messages.value.push({ role: 'assistant', text: `Great choice! Let's get "${p.name}" on its way. Just fill in your details below 👇` })
  scrollDown()
}
const confirmOrder = async () => {
  order.error = ''
  if (!order.form.name.trim() || !order.form.phone.trim() || !order.form.address.trim()) { order.error = 'Please fill name, phone and address.'; return }
  order.placing = true
  try {
    const { data } = await axios.post(route('chat.order'), {
      items: order.items.map((i) => ({ product_id: i.product.id, quantity: i.quantity })),
      name: order.form.name, phone: order.form.phone, address: order.form.address,
      inside_dhaka: order.form.inside_dhaka, payment_method: 'cod',
    })
    order.active = false
    messages.value.push({ role: 'assistant', text: `✅ Order ${data.order_number} placed! Total ৳${data.total.toLocaleString()} (delivery ${data.delivery_charge === 0 ? 'FREE' : '৳' + data.delivery_charge}). We'll contact you soon. Thank you!` })
    cart.fetch()
  } catch (e) {
    order.error = e.response?.data?.error || 'Could not place the order. Please try again.'
  } finally { order.placing = false; scrollDown() }
}
</script>

<style scoped>
@reference "../../css/app.css";
.carousel-arrow { @apply absolute top-1/2 -translate-y-1/2 z-10 w-7 h-7 rounded-full bg-white shadow border border-black/5 flex items-center justify-center text-charcoal opacity-0 group-hover:opacity-100 transition-opacity; }
.kz-in { @apply w-full px-2.5 py-1.5 rounded-md text-xs border border-black/10 bg-transparent outline-none; }
.kz-dot { @apply w-1.5 h-1.5 rounded-full bg-current opacity-40 inline-block; animation: kzbounce 1s infinite; }
@keyframes kzbounce { 0%,60%,100% { transform: translateY(0); opacity:.3 } 30% { transform: translateY(-4px); opacity:.8 } }
.kz-chat-enter-active, .kz-chat-leave-active { transition: opacity .2s ease, transform .2s ease; }
.kz-chat-enter-from, .kz-chat-leave-to { opacity: 0; transform: translateY(16px) scale(.98); }
</style>
