import { defineStore } from 'pinia'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { useAnalytics } from '@/composables/useAnalytics'

export const useCartStore = defineStore('cart', () => {
  const items          = ref([])
  const count          = ref(0)
  const subtotal       = ref(0)
  const delivery_charge = ref(0)
  const discount       = ref(0)
  const total          = ref(0)
  const loaded         = ref(false)
  const fetching       = ref(false)
  // Admin-controlled delivery config (kept in sync from the server)
  const delivery       = ref({ free_enabled: false, type: 'all', minimum: 0, inside: 80, outside: 150 })

  // Single in-flight fetch promise — prevents duplicate concurrent fetches
  let _fetchPromise = null

  // ── Getters ──────────────────────────────────────────────────────────────
  const getItemByProductId = (productId) =>
    items.value.find(i => i.product_id === productId) ?? null

  const getQtyByProductId = (productId) =>
    items.value.find(i => i.product_id === productId)?.quantity ?? 0

  // ── Private helpers ───────────────────────────────────────────────────────
  function _recalculate() {
    subtotal.value        = items.value.reduce((s, i) => s + i.price * i.quantity, 0)
    count.value           = items.value.reduce((s, i) => s + i.quantity, 0)
    // Free (all / minimum-met) → 0; otherwise the Inside-Dhaka estimate (exact at checkout)
    const c = delivery.value
    const free = c.free_enabled && (c.type === 'all' || subtotal.value >= (Number(c.minimum) || 0))
    delivery_charge.value = free ? 0 : Number(c.inside) || 0
    total.value           = subtotal.value + delivery_charge.value - discount.value
  }

  // ── Actions ───────────────────────────────────────────────────────────────
  async function fetch() {
    // If already fetching, wait for the in-flight request rather than firing a second one
    if (fetching.value) {
      if (_fetchPromise) return _fetchPromise
      return
    }
    fetching.value = true
    _fetchPromise  = (async () => {
      try {
        const { data } = await axios.get(route('cart.json'))
        items.value           = data.items ?? []
        count.value           = data.count ?? 0
        subtotal.value        = data.subtotal ?? 0
        delivery_charge.value = data.delivery_charge ?? 0
        discount.value        = data.discount ?? 0
        total.value           = data.total ?? 0
        if (data.delivery) delivery.value = data.delivery
        loaded.value          = true
      } catch (e) {
        console.error('Cart fetch error:', e)
      } finally {
        fetching.value = false
        _fetchPromise  = null
      }
    })()
    return _fetchPromise
  }

  async function ensureLoaded() {
    if (loaded.value) return
    return fetch()
  }

  function add(productId, quantity = 1, variations = []) {
    return new Promise((resolve, reject) => {
      router.post(route('cart.add'), { product_id: productId, quantity, variations }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
          try {
            await fetch()
            useAnalytics().push('add_to_cart', { items: [{ item_id: productId, quantity }] })
          } finally { resolve() }
        },
        onError: (e) => reject(e),
      })
    })
  }

  function updateItem(itemId, quantity) {
    // Optimistic UI update — revert on failure
    const item   = items.value.find(i => i.id === itemId)
    const oldQty = item?.quantity
    if (item) { item.quantity = quantity; _recalculate() }

    return new Promise((resolve, reject) => {
      router.patch(route('cart.update', itemId), { quantity }, {
        preserveScroll: true,
        onSuccess: async () => {
          try { await fetch() } finally { resolve() }
        },
        onError: (e) => {
          if (item && oldQty !== undefined) { item.quantity = oldQty; _recalculate() }
          reject(e)
        },
      })
    })
  }

  function removeItem(itemId) {
    // Optimistic UI update — revert on failure
    const idx     = items.value.findIndex(i => i.id === itemId)
    const removed = idx >= 0 ? items.value.splice(idx, 1)[0] : null
    if (removed) _recalculate()

    return new Promise((resolve, reject) => {
      router.delete(route('cart.remove', itemId), {
        preserveScroll: true,
        onSuccess: async () => {
          try { await fetch() } finally { resolve() }
        },
        onError: (e) => {
          if (removed && idx >= 0) { items.value.splice(idx, 0, removed); _recalculate() }
          reject(e)
        },
      })
    })
  }

  return {
    // State
    items, count, subtotal, delivery_charge, discount, total, loaded, fetching, delivery,
    // Getters
    getItemByProductId, getQtyByProductId,
    // Actions
    fetch, ensureLoaded, add, updateItem, removeItem,
  }
})
