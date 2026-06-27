<template>
  <AdminLayout>
    <Head title="Accounts · Monthly Summary" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <p class="text-muted text-sm">Monthly Summary — FY {{ data.year }} · as of {{ data.as_of }}</p>
      <button @click="print" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5"><PrinterIcon class="w-4 h-4" /> Print / PDF</button>
    </div>

    <div class="bg-white rounded-2xl border border-purple-50 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1000px]">
          <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase tracking-wide">
            <tr>
              <th class="px-3 py-3">Month</th><th class="px-3 py-3 text-right">Sales</th><th class="px-3 py-3 text-right">Other Income</th>
              <th class="px-3 py-3 text-right">Partner Inv.</th><th class="px-3 py-3 text-right">Purchases</th><th class="px-3 py-3 text-right">Op. Expenses</th>
              <th class="px-3 py-3 text-right">Drawings</th><th class="px-3 py-3 text-right">Cash In</th><th class="px-3 py-3 text-right">Cash Out</th>
              <th class="px-3 py-3 text-right">Net Cash</th><th class="px-3 py-3 text-right">Profit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="r in data.rows" :key="r.month" class="hover:bg-purple-50/30">
              <td class="px-3 py-2.5 font-medium text-charcoal">{{ r.month }}</td>
              <td class="px-3 py-2.5 text-right">{{ money(r.sales) }}</td>
              <td class="px-3 py-2.5 text-right">{{ money(r.other_income) }}</td>
              <td class="px-3 py-2.5 text-right">{{ money(r.partner_investment) }}</td>
              <td class="px-3 py-2.5 text-right">{{ money(r.purchases) }}</td>
              <td class="px-3 py-2.5 text-right">{{ money(r.operating_expenses) }}</td>
              <td class="px-3 py-2.5 text-right">{{ money(r.partner_drawings) }}</td>
              <td class="px-3 py-2.5 text-right text-success">{{ money(r.cash_in) }}</td>
              <td class="px-3 py-2.5 text-right text-error">{{ money(r.cash_out) }}</td>
              <td class="px-3 py-2.5 text-right" :class="r.net_cash_flow>=0?'text-success':'text-error'">{{ money(r.net_cash_flow) }}</td>
              <td class="px-3 py-2.5 text-right" :class="r.profit>=0?'text-success':'text-error'">{{ money(r.profit) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-purple-50/60 font-bold text-charcoal">
              <td class="px-3 py-3">Year Total</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.sales) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.other_income) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.partner_investment) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.purchases) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.operating_expenses) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.partner_drawings) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.cash_in) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.cash_out) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.net_cash_flow) }}</td>
              <td class="px-3 py-3 text-right">{{ money(data.total.profit) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import { BanknotesIcon, PrinterIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ data: Object, currency: { type: String, default: 'BDT' } })
const sym = computed(() => props.currency === 'BDT' ? '৳' : props.currency + ' ')
const money = (n) => sym.value + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })
const print = () => window.print()
</script>
