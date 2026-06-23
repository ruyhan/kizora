<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="showOrder" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

        <div class="relative w-full max-w-3xl max-h-[92vh] bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50 shrink-0">
            <div v-if="order">
              <h2 class="font-semibold text-charcoal text-lg">{{ order.order_number }}
                <span v-if="order.source && order.source !== 'web'" class="badge badge-purple text-[10px] align-middle ml-1 uppercase">{{ order.source }}</span>
              </h2>
              <p class="text-muted text-xs mt-0.5">{{ order.created_at }}</p>
            </div>
            <div v-else class="h-10 w-48 bg-purple-50 rounded-xl animate-pulse"></div>
            <div class="flex items-center gap-2 ml-4">
              <template v-if="order && !editing && !confirmingDelete">
                <button v-if="can('orders.edit')" @click="startEdit" class="btn-secondary text-xs px-3 py-1.5 inline-flex items-center gap-1"><PencilSquareIcon class="w-3.5 h-3.5" /> Edit Order</button>
                <button v-if="can('orders.delete')" @click="confirmingDelete = true" class="text-xs px-3 py-1.5 inline-flex items-center gap-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-medium transition-colors"><TrashIcon class="w-3.5 h-3.5" /> Delete</button>
              </template>
              <template v-if="confirmingDelete">
                <span class="text-xs text-muted hidden sm:inline">Delete permanently?</span>
                <button @click="deleteOrder" :disabled="deleting" class="text-xs px-3 py-1.5 rounded-lg bg-rose-600 text-white hover:bg-rose-700 font-semibold transition-colors">{{ deleting ? 'Deleting…' : 'Yes, delete' }}</button>
                <button @click="confirmingDelete = false" class="btn-secondary text-xs px-3 py-1.5">Cancel</button>
              </template>
              <template v-if="editing">
                <button @click="cancelEdit" class="btn-secondary text-xs px-3 py-1.5">Cancel</button>
                <button @click="saveEdit" :disabled="savingEdit" class="btn-primary text-xs px-3 py-1.5">{{ savingEdit ? 'Saving…' : 'Save changes' }}</button>
              </template>
              <button @click="close" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center transition-colors text-muted hover:text-charcoal">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="loadingOrder" class="flex-1 p-6 space-y-4">
            <div v-for="i in 4" :key="i" class="h-16 bg-purple-50 rounded-2xl animate-pulse"></div>
          </div>

          <!-- Content -->
          <div v-else-if="order" class="flex-1 overflow-y-auto">
            <div class="grid md:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-purple-50">

              <!-- Left -->
              <div class="md:col-span-2 p-6 space-y-4">
                <!-- Status -->
                <div v-if="can('orders.update_status')" class="flex items-center gap-3 p-4 bg-purple-50 rounded-2xl">
                  <div class="flex-1">
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">Update Status</p>
                    <select v-model="newStatus" class="input-field text-sm py-2">
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="processing">Processing</option>
                      <option value="out_for_delivery">Out for Delivery</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                  <button @click="updateStatus" :disabled="updating" class="btn-primary text-sm px-5 py-2.5 mt-5 shrink-0">{{ updating ? 'Saving…' : 'Update' }}</button>
                </div>

                <!-- Invoice -->
                <div class="flex items-center gap-2 p-3 bg-purple-50/50 rounded-2xl">
                  <span class="text-xs font-semibold text-muted uppercase tracking-wider">Invoice</span>
                  <span class="text-xs text-muted font-mono">{{ order.invoice_number }}</span>
                  <div class="ml-auto"><InvoiceActions :order-id="order.id" /></div>
                </div>

                <!-- Items (read) -->
                <div v-if="!editing" class="bg-white border border-purple-50 rounded-2xl overflow-hidden">
                  <p class="px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider border-b border-purple-50">Order Items</p>
                  <div class="divide-y divide-purple-50">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 px-4 py-3">
                      <img :src="item.product_image" class="w-12 h-12 object-cover rounded-xl bg-purple-50 shrink-0" alt="">
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-charcoal text-sm truncate">{{ item.product_name }}</p>
                        <VariationTags :variations="item.variations" dense />
                        <p class="text-muted text-xs">৳{{ Number(item.price).toFixed(2) }} × {{ item.quantity }}</p>
                      </div>
                      <p class="font-bold text-charcoal text-sm shrink-0">৳{{ Number(item.total).toFixed(2) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Items (edit) -->
                <div v-else class="bg-white border border-purple-50 rounded-2xl overflow-hidden">
                  <p class="px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wider border-b border-purple-50">Order Items</p>
                  <div v-if="!can('orders.edit_items')" class="px-4 py-3 text-xs text-muted">You don't have permission to edit items.</div>
                  <template v-else>
                    <div class="divide-y divide-purple-50">
                      <div v-for="(it, i) in editForm.items" :key="i" class="flex items-center gap-3 px-4 py-3">
                        <img :src="it.image" class="w-11 h-11 object-cover rounded-lg bg-purple-50 shrink-0" alt="">
                        <div class="flex-1 min-w-0">
                          <p class="font-medium text-charcoal text-sm truncate">{{ it.name }}</p>
                          <p class="text-muted text-xs">৳{{ Number(it.price).toLocaleString() }} each</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                          <button type="button" @click="it.quantity > 1 ? it.quantity-- : null" class="w-6 h-6 rounded border border-purple-100">−</button>
                          <input v-model.number="it.quantity" type="number" min="1" class="w-12 text-center text-sm border border-purple-100 rounded py-1">
                          <button type="button" @click="it.quantity++" class="w-6 h-6 rounded border border-purple-100">+</button>
                        </div>
                        <button type="button" @click="editForm.items.splice(i, 1)" class="text-error w-6 h-6 shrink-0">✕</button>
                      </div>
                    </div>
                    <div v-if="can('orders.add_products')" class="p-3 border-t border-purple-50 relative">
                      <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <input v-model="prodQuery" @input="searchProducts" type="text" placeholder="+ Add product (search name / SKU)…" class="input-field text-sm pl-9 py-2">
                      </div>
                      <div v-if="prodResults.length" class="absolute z-20 left-3 right-3 mt-1 bg-white border border-purple-100 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                        <button v-for="p in prodResults" :key="p.id" type="button" @click="addProduct(p)" class="flex items-center gap-2 w-full text-left px-3 py-2 hover:bg-purple-50">
                          <img :src="p.image" class="w-8 h-8 rounded object-cover bg-purple-50" alt="">
                          <span class="flex-1 text-sm truncate">{{ p.name }}</span>
                          <span class="text-xs text-muted">৳{{ p.price.toLocaleString() }} · {{ p.stock }} left</span>
                        </button>
                      </div>
                    </div>
                    <p v-if="editError" class="px-4 py-2 text-error text-xs">{{ editError }}</p>
                  </template>
                </div>

                <!-- Totals -->
                <div class="bg-purple-50/40 rounded-2xl p-4 space-y-2">
                  <div class="flex justify-between text-sm"><span class="text-muted">Subtotal</span><span>৳{{ Number(editing ? editSubtotal : order.subtotal).toFixed(2) }}</span></div>
                  <div class="flex justify-between text-sm"><span class="text-muted">Delivery</span><span>{{ order.delivery_charge == 0 ? 'FREE' : '৳' + Number(order.delivery_charge).toFixed(2) }}</span></div>
                  <div v-if="order.discount > 0" class="flex justify-between text-sm"><span class="text-success">Discount</span><span class="text-success">-৳{{ Number(order.discount).toFixed(2) }}</span></div>
                  <hr class="border-purple-200">
                  <div class="flex justify-between font-bold text-base"><span>Total</span><span class="text-primary">৳{{ Number(order.total).toFixed(2) }}</span></div>
                  <p v-if="editing" class="text-[11px] text-muted pt-1">Delivery &amp; total are recalculated on save (based on delivery settings &amp; location).</p>
                </div>
              </div>

              <!-- Right -->
              <div class="p-6 space-y-5">
                <div>
                  <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Current Status</p>
                  <span :class="statusBadge(order.status)" class="badge text-xs">{{ order.status_label }}</span>
                </div>

                <template v-if="!editing">
                  <div>
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Customer</p>
                    <p class="font-semibold text-charcoal text-sm">{{ order.customer.name }}</p>
                    <p class="text-muted text-xs mt-0.5">{{ order.customer.email }}</p>
                    <p class="text-muted text-xs">{{ order.customer.phone }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Delivery</p>
                    <p class="text-charcoal text-sm font-medium"><span v-if="order.delivery_city">{{ order.delivery_city }}</span><span v-if="order.delivery_area"> · {{ order.delivery_area }}</span></p>
                    <p class="text-muted text-sm leading-relaxed mt-1">{{ order.delivery_address }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Payment</p>
                    <p class="text-sm text-charcoal uppercase font-medium">{{ order.payment_method }}</p>
                    <span :class="paymentBadge(order.payment_status)" class="badge text-xs mt-1">{{ order.payment_status }}</span>
                  </div>
                  <div v-if="order.notes">
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Notes</p>
                    <p class="text-muted text-sm leading-relaxed">{{ order.notes }}</p>
                  </div>
                </template>

                <template v-else>
                  <div v-if="can('orders.edit_customer')" class="space-y-2.5">
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider">Customer</p>
                    <input v-model="editForm.name" placeholder="Name" class="input-field text-sm py-2">
                    <input v-model="editForm.phone" placeholder="Phone" class="input-field text-sm py-2">
                    <input v-model="editForm.email" placeholder="Email" class="input-field text-sm py-2">
                    <textarea v-model="editForm.address" rows="2" placeholder="Delivery address" class="input-field text-sm py-2"></textarea>
                    <div class="inline-flex p-1 rounded-xl bg-[#F1F1F2] w-full">
                      <button type="button" @click="editForm.inside_dhaka = true" :class="editForm.inside_dhaka ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="flex-1 px-2 py-1.5 rounded-lg text-xs font-semibold">Inside Dhaka</button>
                      <button type="button" @click="editForm.inside_dhaka = false" :class="!editForm.inside_dhaka ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="flex-1 px-2 py-1.5 rounded-lg text-xs font-semibold">Outside Dhaka</button>
                    </div>
                    <textarea v-model="editForm.notes" rows="2" placeholder="Notes" class="input-field text-sm py-2"></textarea>
                  </div>
                  <div class="space-y-2.5">
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider">Payment</p>
                    <select v-model="editForm.payment_method" class="input-field text-sm py-2">
                      <option v-for="m in ['cod','cash','card','mobile_banking','bank_transfer','bkash','nagad','other']" :key="m" :value="m">{{ m.replaceAll('_',' ') }}</option>
                    </select>
                    <select v-model="editForm.payment_status" class="input-field text-sm py-2">
                      <option v-for="s in ['pending','paid','failed','refunded','cancelled']" :key="s" :value="s">{{ s }}</option>
                    </select>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import VariationTags from '@/Components/ui/VariationTags.vue'
import InvoiceActions from '@/Components/admin/InvoiceActions.vue'
import { XMarkIcon, PencilSquareIcon, MagnifyingGlassIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { statusBadge, paymentBadge } from '@/composables/useOrderStatus'
import { usePermissions } from '@/composables/usePermissions'

const emit = defineEmits(['updated'])
const { can } = usePermissions()
const toast = useToast()

const showOrder = ref(false)
const loadingOrder = ref(false)
const order = ref(null)
const newStatus = ref('')
const updating = ref(false)
const confirmingDelete = ref(false)
const deleting = ref(false)

const editing = ref(false)
const editForm = ref(null)
const savingEdit = ref(false)
const editError = ref('')
const prodQuery = ref('')
const prodResults = ref([])
let prodTimer = null

const editSubtotal = computed(() =>
  editForm.value ? editForm.value.items.reduce((s, i) => s + Number(i.price) * Number(i.quantity || 0), 0) : 0
)

const open = async (id) => {
  showOrder.value = true
  loadingOrder.value = true
  order.value = null
  editing.value = false
  confirmingDelete.value = false
  document.body.style.overflow = 'hidden'
  try {
    const { data } = await axios.get(route('admin.orders.data', id))
    order.value = data
    newStatus.value = data.status
  } catch {
    toast.error('Failed to load order.')
    close()
  } finally {
    loadingOrder.value = false
  }
}

const close = () => {
  showOrder.value = false
  order.value = null
  editing.value = false
  confirmingDelete.value = false
  document.body.style.overflow = ''
}

defineExpose({ open, close })

const deleteOrder = () => {
  if (!order.value) return
  deleting.value = true
  router.delete(route('admin.orders.destroy', order.value.id), {
    preserveScroll: true,
    onSuccess: () => { confirmingDelete.value = false; emit('updated'); close(); toast.success('Order deleted.') },
    onError: () => toast.error('Failed to delete order.'),
    onFinish: () => { deleting.value = false },
  })
}

const updateStatus = () => {
  if (!order.value) return
  updating.value = true
  router.patch(route('admin.orders.status', order.value.id), { status: newStatus.value }, {
    preserveScroll: true,
    onSuccess: async () => { await open(order.value.id); emit('updated') },
    onError: () => toast.error('Failed to update status.'),
    onFinish: () => { updating.value = false },
  })
}

const startEdit = () => {
  editError.value = ''
  editForm.value = {
    name: order.value.customer.name || '', phone: order.value.customer.phone || '', email: order.value.customer.email || '',
    address: order.value.delivery_address || '', inside_dhaka: !!order.value.inside_dhaka, notes: order.value.notes || '',
    payment_method: order.value.payment_method || 'cod', payment_status: order.value.payment_status || 'pending',
    items: order.value.items.map((i) => ({ id: i.id, product_id: i.product_id, name: i.product_name, price: Number(i.price), quantity: i.quantity, image: i.product_image })),
  }
  editing.value = true
}
const cancelEdit = () => { editing.value = false; editError.value = '' }

const searchProducts = () => {
  clearTimeout(prodTimer)
  prodTimer = setTimeout(async () => {
    if (!prodQuery.value.trim()) { prodResults.value = []; return }
    try { const { data } = await axios.get(route('admin.orders.products-search'), { params: { q: prodQuery.value } }); prodResults.value = data } catch { prodResults.value = [] }
  }, 300)
}
const addProduct = (p) => {
  const ex = editForm.value.items.find((i) => i.product_id === p.id)
  if (ex) ex.quantity++
  else editForm.value.items.push({ product_id: p.id, name: p.name, price: p.price, quantity: 1, image: p.image })
  prodQuery.value = ''
  prodResults.value = []
}
const saveEdit = () => {
  savingEdit.value = true
  editError.value = ''
  router.put(route('admin.orders.update', order.value.id), {
    name: editForm.value.name, phone: editForm.value.phone, email: editForm.value.email,
    address: editForm.value.address, inside_dhaka: editForm.value.inside_dhaka, notes: editForm.value.notes,
    payment_method: editForm.value.payment_method, payment_status: editForm.value.payment_status,
    items: editForm.value.items.map((i) => ({ id: i.id, product_id: i.product_id, quantity: i.quantity })),
  }, {
    preserveScroll: true,
    onSuccess: async () => { editing.value = false; await open(order.value.id); emit('updated') },
    onError: (e) => { editError.value = e.items || e.error || 'Could not save changes.' },
    onFinish: () => { savingEdit.value = false },
  })
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .relative, .modal-leave-active .relative { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from .relative { transform: scale(0.95); }
.modal-leave-to .relative { transform: scale(0.95); }
</style>
