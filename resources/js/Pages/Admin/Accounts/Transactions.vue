<template>
  <AdminLayout>
    <Head title="Accounts · Transactions" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-purple-100 p-3 mb-4 flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[180px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search party, description, voucher…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.type" @update:model-value="v=>set('type',v)" :options="opt(lists.transaction_types,'All types')" trigger-class="text-sm py-2" class="w-40" />
      <SelectMenu :model-value="f.account_head" @update:model-value="v=>set('account_head',v)" :options="opt(lists.account_heads,'All heads')" trigger-class="text-sm py-2" class="w-40" />
      <SelectMenu :model-value="f.status" @update:model-value="v=>set('status',v)" :options="opt(lists.statuses,'All status')" trigger-class="text-sm py-2" class="w-36" />
      <SelectMenu :model-value="f.payment_account" @update:model-value="v=>set('payment_account',v)" :options="opt(lists.payment_accounts,'All accounts')" trigger-class="text-sm py-2" class="w-40" />
      <DateRangeFilter route-name="admin.accounts.transactions" :filters="filters" :params="f" />
      <a :href="exportUrl" class="btn-secondary text-sm px-3 py-2 inline-flex items-center gap-1.5"><ArrowDownTrayIcon class="w-4 h-4" /> CSV</a>
      <button v-if="canManage" @click="openCreate" class="btn-primary text-sm px-4 py-2 inline-flex items-center gap-1.5"><PlusIcon class="w-4 h-4" /> New</button>
    </div>

    <div class="bg-white rounded-2xl border border-purple-50 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1100px]">
          <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase tracking-wide">
            <tr>
              <th v-if="canManage" class="px-3 py-3 w-9"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="px-3 py-3">Voucher</th><th class="px-3 py-3">Date</th><th class="px-3 py-3">Type</th>
              <th class="px-3 py-3">Account Head</th><th class="px-3 py-3">Party</th>
              <th class="px-3 py-3 text-right">Cash In</th><th class="px-3 py-3 text-right">Cash Out</th>
              <th class="px-3 py-3 text-right">Net</th><th class="px-3 py-3 text-right">Balance</th>
              <th class="px-3 py-3">Status</th><th class="px-3 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-purple-50/30">
              <td v-if="canManage" class="px-3 py-2.5"><input type="checkbox" :value="t.id" v-model="selected" class="kz-check"></td>
              <td class="px-3 py-2.5 font-medium text-charcoal whitespace-nowrap">{{ t.voucher_no }}</td>
              <td class="px-3 py-2.5 whitespace-nowrap text-muted">{{ t.date }}</td>
              <td class="px-3 py-2.5 whitespace-nowrap">{{ t.type }}</td>
              <td class="px-3 py-2.5 whitespace-nowrap text-muted">{{ t.account_head || '—' }}</td>
              <td class="px-3 py-2.5 max-w-[160px] truncate" :title="t.party">{{ t.party || '—' }}</td>
              <td class="px-3 py-2.5 text-right font-medium" :class="t.cash_in ? 'text-success' : 'text-muted/50'">{{ t.cash_in ? money(t.cash_in) : '—' }}</td>
              <td class="px-3 py-2.5 text-right font-medium" :class="t.cash_out ? 'text-error' : 'text-muted/50'">{{ t.cash_out ? money(t.cash_out) : '—' }}</td>
              <td class="px-3 py-2.5 text-right" :class="t.net_cash_flow >= 0 ? 'text-success' : 'text-error'">{{ money(t.net_cash_flow) }}</td>
              <td class="px-3 py-2.5 text-right text-charcoal">{{ t.running_balance === null ? '—' : money(t.running_balance) }}</td>
              <td class="px-3 py-2.5"><span :class="statusClass(t.status)" class="badge text-[11px]">{{ t.status }}</span></td>
              <td class="px-3 py-2.5 text-right whitespace-nowrap">
                <button v-if="canManage" @click="openEdit(t)" class="icon-btn text-primary" title="Edit"><PencilSquareIcon class="w-4 h-4" /></button>
                <button v-if="canManage" @click="destroy(t)" class="icon-btn text-error" title="Delete"><TrashIcon class="w-4 h-4" /></button>
              </td>
            </tr>
            <tr v-if="!transactions.data.length"><td :colspan="canManage ? 12 : 11" class="px-4 py-12 text-center text-muted">No transactions found.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="transactions.links && transactions.links.length > 3" class="flex flex-wrap gap-1 justify-center mt-4">
      <component :is="l.url ? 'button' : 'span'" v-for="(l,i) in transactions.links" :key="i" @click="l.url && go(l.url)"
        :class="[l.active ? 'bg-ink text-white' : 'text-muted hover:text-ink', !l.url && 'opacity-40']" class="px-3 py-1.5 rounded-lg text-sm font-medium" v-html="l.label" />
    </div>

    <!-- Create / Edit modal -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="modal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="modal.open=false"></div>
          <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-border max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border sticky top-0 bg-white">
              <h3 class="font-display font-semibold text-charcoal">{{ editing ? 'Edit transaction' : 'New transaction' }}</h3>
              <button @click="modal.open=false" class="text-muted hover:text-ink"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <div class="p-5 grid sm:grid-cols-2 gap-4">
              <div><label class="lbl">Date *</label><input v-model="form.date" type="date" class="input-field"><p v-if="form.errors.date" class="err">{{ form.errors.date }}</p></div>
              <div><label class="lbl">Voucher No.</label><input :value="editing ? editing.voucher_no : nextVoucher" disabled class="input-field bg-purple-50/40 text-muted"></div>
              <div><label class="lbl">Transaction Type *</label><SelectMenu v-model="form.type" :options="plain(lists.transaction_types)" /><p v-if="form.errors.type" class="err">{{ form.errors.type }}</p></div>
              <div><label class="lbl">Account Head</label><SelectMenu v-model="form.account_head" :options="opt(lists.account_heads,'—')" /></div>
              <div><label class="lbl">Party / Customer / Supplier</label><input v-model="form.party" class="input-field"></div>
              <div><label class="lbl">Payment Account</label><SelectMenu v-model="form.payment_account" :options="opt(lists.payment_accounts,'—')" /></div>
              <div v-if="needsPartner"><label class="lbl">Partner *</label><SelectMenu v-model="form.partner" :options="opt(partners,'—')" /><p v-if="form.errors.partner" class="err">{{ form.errors.partner }}</p></div>
              <div><label class="lbl">Status *</label><SelectMenu v-model="form.status" :options="plain(lists.statuses)" /></div>
              <div><label class="lbl">Cash In</label><input v-model.number="form.cash_in" type="number" step="0.01" min="0" class="input-field" :class="form.cash_out>0 ? 'opacity-50':''"><p v-if="form.errors.cash_in" class="err">{{ form.errors.cash_in }}</p></div>
              <div><label class="lbl">Cash Out</label><input v-model.number="form.cash_out" type="number" step="0.01" min="0" class="input-field"><p v-if="form.errors.cash_out" class="err">{{ form.errors.cash_out }}</p></div>
              <div><label class="lbl">VAT / Tax</label><input v-model.number="form.vat_tax" type="number" step="0.01" min="0" class="input-field"></div>
              <div><label class="lbl">Discount</label><input v-model.number="form.discount" type="number" step="0.01" min="0" class="input-field"></div>
              <div class="sm:col-span-2"><label class="lbl">Description</label><input v-model="form.description" class="input-field"></div>
              <div class="sm:col-span-2"><label class="lbl">Remarks</label><textarea v-model="form.remarks" rows="2" class="input-field"></textarea></div>
              <div class="sm:col-span-2 flex items-center gap-4 text-sm bg-purple-50/50 rounded-xl px-4 py-2.5">
                <span>Net: <strong :class="netPreview >= 0 ? 'text-success':'text-error'">{{ money(netPreview) }}</strong></span>
                <span class="text-muted">Profit impact: <strong class="text-charcoal">{{ money(profitPreview) }}</strong></span>
              </div>
            </div>
            <div class="px-5 py-4 border-t border-border flex justify-end gap-2 sticky bottom-0 bg-white">
              <button @click="modal.open=false" class="btn-secondary text-sm px-4">Cancel</button>
              <button @click="save" :disabled="form.processing" class="btn-primary text-sm px-5 disabled:opacity-60">{{ form.processing ? 'Saving…' : 'Save' }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <BulkDeleteBar v-if="canManage" :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected=[]" />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import { useToast } from 'vue-toastification'
import { BanknotesIcon, MagnifyingGlassIcon, PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ transactions: Object, filters: Object, lists: Object, partners: Array, currency: String, nextVoucher: String, canManage: Boolean })
const toast = useToast()

const sym = computed(() => props.currency === 'BDT' ? '৳' : (props.currency || '') + ' ')
const money = (n) => sym.value + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })
const opt = (arr, allLabel) => [{ value: '', label: allLabel }, ...(arr || []).map(x => ({ value: x, label: x }))]
const plain = (arr) => (arr || []).map(x => ({ value: x, label: x }))
const statusClass = (s) => ({ Posted: 'badge-success', Draft: 'badge-gray', Pending: 'badge-warning', Cancelled: 'badge-error' }[s] || 'badge-gray')

// Filters
const f = reactive({
  search: props.filters?.search || '', type: props.filters?.type || '', account_head: props.filters?.account_head || '',
  status: props.filters?.status || '', payment_account: props.filters?.payment_account || '',
  range: props.filters?.range, from: props.filters?.from, to: props.filters?.to,
})
let _t
const apply = () => router.get(route('admin.accounts.transactions'), f, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(_t); _t = setTimeout(apply, 350) }
const set = (k, v) => { f[k] = v; apply() }
const go = (url) => router.get(url, {}, { preserveState: true, preserveScroll: true })
const exportUrl = computed(() => route('admin.accounts.transactions.export', f))

// Bulk
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.transactions.data.length > 0 && selected.value.length === props.transactions.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.transactions.data.map(t => t.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.accounts.transactions.bulk-destroy'), { ids: selected.value }, { preserveScroll: true, onSuccess: () => selected.value = [], onFinish: () => bulkDeleting.value = false })
}

// Form / modal
const modal = reactive({ open: false })
const editing = ref(null)
const blank = () => ({ date: new Date().toISOString().slice(0, 10), type: 'Sales', account_head: '', party: '', description: '', payment_account: 'Cash', partner: '', cash_in: null, cash_out: null, vat_tax: null, discount: null, status: 'Posted', remarks: '' })
const form = useForm(blank())

const needsPartner = computed(() => ['Partner Investment', 'Partner Drawing'].includes(form.type))
const netPreview = computed(() => (Number(form.cash_in) || 0) - (Number(form.cash_out) || 0))
const NON_PROFIT = ['Partner Investment', 'Partner Drawing', 'Loan Received', 'Loan Repayment', 'Asset Purchase', 'Bank Transfer', 'Opening Balance']
const profitPreview = computed(() => NON_PROFIT.includes(form.type) ? 0 : netPreview.value)

const openCreate = () => { editing.value = null; form.defaults(blank()); form.reset(); form.clearErrors(); modal.open = true }
const openEdit = (t) => {
  editing.value = t
  form.defaults({ ...t, cash_in: t.cash_in || null, cash_out: t.cash_out || null, vat_tax: t.vat_tax || null, discount: t.discount || null })
  form.reset(); form.clearErrors(); modal.open = true
}
const save = () => {
  const opts = { preserveScroll: true, onSuccess: () => { modal.open = false; toast.success('Saved.') } }
  editing.value
    ? form.put(route('admin.accounts.transactions.update', editing.value.id), opts)
    : form.post(route('admin.accounts.transactions.store'), opts)
}
const destroy = (t) => { if (confirm('Delete this transaction?')) router.delete(route('admin.accounts.transactions.destroy', t.id), { preserveScroll: true }) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
.err { @apply text-error text-xs mt-1; }
.icon-btn { @apply w-8 h-8 rounded-lg border border-purple-100 inline-flex items-center justify-center hover:bg-purple-50 transition-colors ml-1; }
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .2s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
</style>
