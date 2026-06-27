<template>
  <AdminLayout>
    <Head title="Accounts · Account Ledger" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <p class="text-muted text-sm mb-4">Account Ledger · as of {{ data.as_of }} · <span class="text-charcoal">click a head to drill down</span></p>

    <div class="bg-white rounded-2xl border border-purple-50 shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase tracking-wide">
          <tr><th class="px-4 py-3">Account Head</th><th class="px-4 py-3 text-center">Txns</th><th class="px-4 py-3 text-right">Cash In</th><th class="px-4 py-3 text-right">Cash Out</th><th class="px-4 py-3 text-right">Net Balance</th></tr>
        </thead>
        <tbody class="divide-y divide-purple-50">
          <tr v-for="r in data.rows" :key="r.account_head" @click="drill(r.account_head)" class="hover:bg-purple-50/40 cursor-pointer">
            <td class="px-4 py-2.5 font-medium text-primary hover:underline">{{ r.account_head }}</td>
            <td class="px-4 py-2.5 text-center text-muted">{{ r.count }}</td>
            <td class="px-4 py-2.5 text-right text-success">{{ money(r.cash_in) }}</td>
            <td class="px-4 py-2.5 text-right text-error">{{ money(r.cash_out) }}</td>
            <td class="px-4 py-2.5 text-right font-semibold" :class="r.net>=0?'text-charcoal':'text-error'">{{ money(r.net) }}</td>
          </tr>
          <tr v-if="!data.rows.length"><td colspan="5" class="px-4 py-12 text-center text-muted">No posted transactions yet.</td></tr>
        </tbody>
        <tfoot v-if="data.rows.length">
          <tr class="bg-purple-50/60 font-bold text-charcoal">
            <td class="px-4 py-3">Total</td><td class="px-4 py-3 text-center">{{ data.total.count }}</td>
            <td class="px-4 py-3 text-right">{{ money(data.total.cash_in) }}</td>
            <td class="px-4 py-3 text-right">{{ money(data.total.cash_out) }}</td>
            <td class="px-4 py-3 text-right">{{ money(data.total.net) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Drill-down modal -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="drillOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="drillOpen=false"></div>
          <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-border max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border sticky top-0 bg-white">
              <h3 class="font-display font-semibold text-charcoal">{{ drillHead }} <span class="text-muted font-normal text-sm">· {{ drillRows.length }} transactions</span></h3>
              <button @click="drillOpen=false" class="text-muted hover:text-ink"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase"><tr><th class="px-4 py-2.5">Date</th><th class="px-4 py-2.5">Voucher</th><th class="px-4 py-2.5">Type</th><th class="px-4 py-2.5">Party</th><th class="px-4 py-2.5 text-right">Cash In</th><th class="px-4 py-2.5 text-right">Cash Out</th></tr></thead>
              <tbody class="divide-y divide-purple-50">
                <tr v-for="(r,i) in drillRows" :key="i">
                  <td class="px-4 py-2 text-muted whitespace-nowrap">{{ r.date }}</td><td class="px-4 py-2">{{ r.voucher_no }}</td>
                  <td class="px-4 py-2">{{ r.type }}</td><td class="px-4 py-2 max-w-[160px] truncate">{{ r.party || '—' }}</td>
                  <td class="px-4 py-2 text-right text-success">{{ r.cash_in ? money(r.cash_in) : '—' }}</td>
                  <td class="px-4 py-2 text-right text-error">{{ r.cash_out ? money(r.cash_out) : '—' }}</td>
                </tr>
                <tr v-if="drillLoading"><td colspan="6" class="px-4 py-8 text-center text-muted">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import { BanknotesIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ data: Object, currency: { type: String, default: 'BDT' } })
const sym = computed(() => props.currency === 'BDT' ? '৳' : props.currency + ' ')
const money = (n) => sym.value + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })

const drillOpen = ref(false)
const drillHead = ref('')
const drillRows = ref([])
const drillLoading = ref(false)
const drill = async (head) => {
  drillHead.value = head; drillRows.value = []; drillLoading.value = true; drillOpen.value = true
  try {
    const { data } = await axios.get(route('admin.accounts.ledger.head', { head }))
    drillRows.value = data.rows || []
  } catch { /* ignore */ } finally { drillLoading.value = false }
}
</script>

<style scoped>
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .2s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
</style>
