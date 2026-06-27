<template>
  <AdminLayout>
    <Head title="Accounts · Dashboard" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <p class="text-muted text-sm">FY {{ data.year }} · <span class="text-charcoal">as of {{ data.as_of }}</span></p>
      <button @click="print" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5"><PrinterIcon class="w-4 h-4" /> Print / PDF</button>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
      <div v-for="k in kpiCards" :key="k.label" class="bg-white rounded-2xl border border-purple-100 p-4">
        <p class="text-xs text-muted">{{ k.label }}</p>
        <p class="text-xl font-bold mt-1" :class="k.color || 'text-charcoal'">{{ money(k.value) }}</p>
      </div>
    </div>

    <!-- Key metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
      <div v-for="m in metricCards" :key="m.label" class="bg-white rounded-2xl border border-purple-50 p-4">
        <p class="text-[11px] text-muted leading-tight">{{ m.label }}</p>
        <p class="text-base font-semibold text-charcoal mt-1">{{ money(m.value) }}</p>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid lg:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-4">Cash In vs Cash Out (monthly)</h3>
        <div class="h-64"><Bar :data="barData" :options="barOpts" /></div>
      </div>
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-4">Profit trend</h3>
        <div class="h-64"><Line :data="lineData" :options="lineOpts" /></div>
      </div>
    </div>

    <!-- Monthly snapshot -->
    <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
      <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-3">Monthly snapshot — FY {{ data.year }}</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
          <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase tracking-wide">
            <tr><th class="px-4 py-3">Month</th><th class="px-4 py-3 text-right">Sales</th><th class="px-4 py-3 text-right">Purchases</th><th class="px-4 py-3 text-right">Expenses</th><th class="px-4 py-3 text-right">Net Cash Flow</th><th class="px-4 py-3 text-right">Profit</th></tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="r in data.monthly" :key="r.month" class="hover:bg-purple-50/30">
              <td class="px-4 py-2.5 font-medium text-charcoal">{{ r.month }}</td>
              <td class="px-4 py-2.5 text-right">{{ money(r.sales) }}</td>
              <td class="px-4 py-2.5 text-right">{{ money(r.purchases) }}</td>
              <td class="px-4 py-2.5 text-right">{{ money(r.operating_expenses) }}</td>
              <td class="px-4 py-2.5 text-right" :class="r.net_cash_flow >= 0 ? 'text-success' : 'text-error'">{{ money(r.net_cash_flow) }}</td>
              <td class="px-4 py-2.5 text-right" :class="r.profit >= 0 ? 'text-success' : 'text-error'">{{ money(r.profit) }}</td>
            </tr>
          </tbody>
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
import { Bar, Line } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Filler } from 'chart.js'
ChartJS.register(Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const props = defineProps({ data: Object, currency: { type: String, default: 'BDT' } })

const sym = computed(() => props.currency === 'BDT' ? '৳' : props.currency + ' ')
const money = (n) => sym.value + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })
const print = () => window.print()

const kpiCards = computed(() => [
  { label: 'Total Cash In', value: props.data.kpis.total_cash_in, color: 'text-success' },
  { label: 'Total Cash Out', value: props.data.kpis.total_cash_out, color: 'text-error' },
  { label: 'Cash/Bank Balance', value: props.data.kpis.balance },
  { label: 'Estimated Profit', value: props.data.kpis.estimated_profit, color: props.data.kpis.estimated_profit >= 0 ? 'text-success' : 'text-error' },
  { label: 'Partner Capital', value: props.data.kpis.partner_capital },
])
const metricCards = computed(() => [
  { label: 'Net Sales', value: props.data.metrics.net_sales },
  { label: 'Total Purchases', value: props.data.metrics.total_purchases },
  { label: 'Operating Expenses', value: props.data.metrics.operating_expenses },
  { label: 'Partner Investments', value: props.data.metrics.partner_investments },
  { label: 'Partner Drawings', value: props.data.metrics.partner_drawings },
  { label: 'VAT/Tax Paid', value: props.data.metrics.vat_tax_paid },
])

const labels = computed(() => props.data.monthly.map(r => r.month))
const barData = computed(() => ({
  labels: labels.value,
  datasets: [
    { label: 'Cash In', data: props.data.monthly.map(r => r.cash_in), backgroundColor: '#16A34A' },
    { label: 'Cash Out', data: props.data.monthly.map(r => r.cash_out), backgroundColor: '#ED1C24' },
  ],
}))
const barOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6 } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f1f1f3' } } } }
const lineData = computed(() => ({
  labels: labels.value,
  datasets: [{ label: 'Profit', data: props.data.monthly.map(r => r.profit), borderColor: '#17181B', backgroundColor: 'rgba(23,24,27,.06)', fill: true, tension: 0.4, pointRadius: 0 }],
}))
const lineOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f1f3' } } } }
</script>
