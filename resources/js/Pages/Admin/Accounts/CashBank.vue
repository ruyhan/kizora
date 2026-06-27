<template>
  <AdminLayout>
    <Head title="Accounts · Cash & Bank" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <p class="text-muted text-sm mb-4">Cash &amp; Bank balances · as of {{ data.as_of }}</p>

    <div class="bg-white rounded-2xl border border-purple-50 shadow-sm overflow-hidden max-w-3xl">
      <table class="w-full text-sm">
        <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase tracking-wide">
          <tr><th class="px-4 py-3">Payment Account</th><th class="px-4 py-3 text-right">Cash In</th><th class="px-4 py-3 text-right">Cash Out</th><th class="px-4 py-3 text-right">Current Balance</th></tr>
        </thead>
        <tbody class="divide-y divide-purple-50">
          <tr v-for="r in data.rows" :key="r.account" class="hover:bg-purple-50/30">
            <td class="px-4 py-2.5 font-medium text-charcoal">{{ r.account }}</td>
            <td class="px-4 py-2.5 text-right text-success">{{ money(r.cash_in) }}</td>
            <td class="px-4 py-2.5 text-right text-error">{{ money(r.cash_out) }}</td>
            <td class="px-4 py-2.5 text-right font-semibold" :class="r.balance>=0?'text-charcoal':'text-error'">{{ money(r.balance) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="bg-purple-50/60 font-bold text-charcoal">
            <td class="px-4 py-3">Total</td>
            <td class="px-4 py-3 text-right">{{ money(data.total.cash_in) }}</td>
            <td class="px-4 py-3 text-right">{{ money(data.total.cash_out) }}</td>
            <td class="px-4 py-3 text-right">{{ money(data.total.balance) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ data: Object, currency: { type: String, default: 'BDT' } })
const sym = computed(() => props.currency === 'BDT' ? '৳' : props.currency + ' ')
const money = (n) => sym.value + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })
</script>
