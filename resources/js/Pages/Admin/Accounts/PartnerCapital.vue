<template>
  <AdminLayout>
    <Head title="Accounts · Partner Capital" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <p class="text-muted text-sm">Partner Capital · profit share for FY {{ year }} · as of {{ data.as_of }}</p>
      <button @click="print" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5"><PrinterIcon class="w-4 h-4" /> Print / PDF</button>
    </div>

    <div class="bg-white rounded-2xl border border-purple-50 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[820px]">
          <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase tracking-wide">
            <tr>
              <th class="px-4 py-3">Partner</th><th class="px-4 py-3 text-right">Ownership %</th><th class="px-4 py-3 text-right">Opening Capital</th>
              <th class="px-4 py-3 text-right">Investment</th><th class="px-4 py-3 text-right">Drawings</th><th class="px-4 py-3 text-right">Current Capital</th>
              <th class="px-4 py-3 text-right">Profit/Loss Share</th><th class="px-4 py-3 text-right">Capital + Profit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="r in data.rows" :key="r.name" class="hover:bg-purple-50/30">
              <td class="px-4 py-2.5 font-medium text-charcoal">{{ r.name }}</td>
              <td class="px-4 py-2.5 text-right">{{ r.ownership }}%</td>
              <td class="px-4 py-2.5 text-right">{{ money(r.opening_capital) }}</td>
              <td class="px-4 py-2.5 text-right text-success">{{ money(r.investment) }}</td>
              <td class="px-4 py-2.5 text-right text-error">{{ money(r.drawings) }}</td>
              <td class="px-4 py-2.5 text-right font-semibold">{{ money(r.current_capital) }}</td>
              <td class="px-4 py-2.5 text-right" :class="r.profit_share>=0?'text-success':'text-error'">{{ money(r.profit_share) }}</td>
              <td class="px-4 py-2.5 text-right font-semibold text-charcoal">{{ money(r.capital_plus_profit) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-purple-50/60 font-bold text-charcoal">
              <td class="px-4 py-3">Total</td>
              <td class="px-4 py-3 text-right" :class="data.total.ownership===100?'':'text-error'">{{ data.total.ownership }}%</td>
              <td class="px-4 py-3 text-right">{{ money(data.total.opening_capital) }}</td>
              <td class="px-4 py-3 text-right">{{ money(data.total.investment) }}</td>
              <td class="px-4 py-3 text-right">{{ money(data.total.drawings) }}</td>
              <td class="px-4 py-3 text-right">{{ money(data.total.current_capital) }}</td>
              <td class="px-4 py-3 text-right">{{ money(data.total.profit_share) }}</td>
              <td class="px-4 py-3 text-right">{{ money(data.total.capital_plus_profit) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <p v-if="data.total.ownership !== 100" class="text-error text-sm mt-3">⚠ Ownership percentages total {{ data.total.ownership }}% (should be 100%). Fix in Settings.</p>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import { BanknotesIcon, PrinterIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ data: Object, currency: { type: String, default: 'BDT' }, year: Number })
const sym = computed(() => props.currency === 'BDT' ? '৳' : props.currency + ' ')
const money = (n) => sym.value + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })
const print = () => window.print()
</script>
