<template>
  <AdminLayout>
    <Head title="Reports" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Reports</h1>
        <p class="text-muted text-sm">Sales &amp; customer performance.</p>
      </div>
      <div class="flex items-center gap-2">
        <DateRangeFilter route-name="admin.reports.index" :filters="filters" />
        <a v-if="canExport" :href="route('admin.reports.export', exportParams)" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5">
          <ArrowDownTrayIcon class="w-4 h-4" /> Export CSV
        </a>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="k in kpiCards" :key="k.label" class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
        <div class="flex items-start justify-between">
          <span class="w-10 h-10 rounded-xl flex items-center justify-center" :class="k.iconBg">
            <component :is="k.icon" class="w-5 h-5" :class="k.iconColor" />
          </span>
          <span v-if="k.change !== null && k.change !== undefined"
            :class="k.change >= 0 ? 'text-success bg-green-50' : 'text-error bg-red-50'"
            class="text-xs font-semibold px-2 py-0.5 rounded-full">{{ k.change >= 0 ? '+' : '' }}{{ k.change }}%</span>
        </div>
        <p class="text-2xl font-bold text-charcoal mt-3">{{ k.value }}</p>
        <p class="text-muted text-sm">{{ k.label }}</p>
      </div>
    </div>

    <!-- Revenue trend + status -->
    <div class="grid lg:grid-cols-3 gap-4 mb-6">
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Revenue &amp; Orders</h3>
        <p class="text-muted text-xs mb-4">Daily performance for the selected range</p>
        <div class="h-64"><Line v-if="hasDaily" :data="lineData" :options="lineOpts" /><Empty v-else /></div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Orders by status</h3>
        <p class="text-muted text-xs mb-4">Distribution</p>
        <div class="h-64 flex items-center justify-center"><Doughnut v-if="hasStatus" :data="statusData" :options="doughnutOpts" /><Empty v-else /></div>
      </div>
    </div>

    <!-- Top products + customers -->
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-3">Top products</h3>
        <table class="w-full">
          <thead class="bg-purple-50/50"><tr><th class="th">Product</th><th class="th text-center">Sold</th><th class="th text-right">Revenue</th></tr></thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="p in topProducts" :key="p.product_name" class="hover:bg-purple-50/20">
              <td class="px-5 py-3 text-sm text-charcoal truncate max-w-[220px]">{{ p.product_name }}</td>
              <td class="px-5 py-3 text-center"><span class="badge badge-purple text-xs">{{ p.sold }}</span></td>
              <td class="px-5 py-3 text-right font-semibold text-charcoal text-sm">৳{{ Number(p.revenue).toLocaleString() }}</td>
            </tr>
            <tr v-if="!topProducts.length"><td colspan="3" class="px-5 py-10 text-center text-muted text-sm">No sales in this range.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-3">Top customers</h3>
        <table class="w-full">
          <thead class="bg-purple-50/50"><tr><th class="th">Customer</th><th class="th text-center">Orders</th><th class="th text-right">Spend</th></tr></thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="(c, i) in topCustomers" :key="i" class="hover:bg-purple-50/20">
              <td class="px-5 py-3 text-sm text-charcoal truncate max-w-[220px]">{{ c.name }}</td>
              <td class="px-5 py-3 text-center"><span class="badge badge-purple text-xs">{{ c.orders }}</span></td>
              <td class="px-5 py-3 text-right font-semibold text-charcoal text-sm">৳{{ Number(c.spend).toLocaleString() }}</td>
            </tr>
            <tr v-if="!topCustomers.length"><td colspan="3" class="px-5 py-10 text-center text-muted text-sm">No customer orders in this range.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, h } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, Title, Tooltip, Legend, LineElement, PointElement,
  CategoryScale, LinearScale, ArcElement, Filler,
} from 'chart.js'
import {
  BanknotesIcon, ShoppingBagIcon, CalculatorIcon, UserPlusIcon, ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, ArcElement, Filler)

const props = defineProps({
  filters: Object, kpis: Object, daily: Array, byStatus: Object, byPayment: Object,
  topProducts: Array, topCustomers: Array, canExport: Boolean,
})

const Empty = () => h('p', { class: 'text-muted text-sm text-center w-full' }, 'No data for this range.')

// Forward the active range to the CSV export link.
const exportParams = computed(() => props.filters?.range === 'custom'
  ? { from: props.filters.from, to: props.filters.to }
  : { range: props.filters?.range })

const fmt = (n) => '৳' + Number(n || 0).toLocaleString()
const kpiCards = computed(() => [
  { label: 'Revenue', value: fmt(props.kpis.revenue), change: props.kpis.revenue_change, icon: BanknotesIcon, iconBg: 'bg-green-50', iconColor: 'text-success' },
  { label: 'Orders', value: Number(props.kpis.orders).toLocaleString(), change: props.kpis.orders_change, icon: ShoppingBagIcon, iconBg: 'bg-purple-50', iconColor: 'text-primary' },
  { label: 'Avg. order value', value: fmt(props.kpis.aov), change: props.kpis.aov_change, icon: CalculatorIcon, iconBg: 'bg-blue-50', iconColor: 'text-blue-500' },
  { label: 'New customers', value: Number(props.kpis.new_customers).toLocaleString(), change: props.kpis.customers_change, icon: UserPlusIcon, iconBg: 'bg-amber-50', iconColor: 'text-amber-500' },
])

// Revenue line
const hasDaily = computed(() => props.daily.length > 0)
const lineData = computed(() => ({
  labels: props.daily.map((d) => d.date),
  datasets: [
    { label: 'Revenue', data: props.daily.map((d) => d.revenue), borderColor: '#17181B', backgroundColor: 'rgba(23,24,27,0.06)', fill: true, tension: 0.4, yAxisID: 'y', pointRadius: 0 },
    { label: 'Orders', data: props.daily.map((d) => d.orders), borderColor: '#ED1C24', backgroundColor: 'transparent', tension: 0.4, yAxisID: 'y1', pointRadius: 0 },
  ],
}))
const lineOpts = {
  responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
  plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } } },
  scales: { y: { position: 'left', grid: { color: '#f1f1f3' } }, y1: { position: 'right', grid: { drawOnChartArea: false } }, x: { grid: { display: false } } },
}

// Status doughnut
const STATUS_COLORS = { pending: '#F59E0B', confirmed: '#3B82F6', processing: '#6366F1', out_for_delivery: '#14B8A6', completed: '#10B981', cancelled: '#9CA3AF' }
const hasStatus = computed(() => Object.keys(props.byStatus || {}).length > 0)
const statusData = computed(() => {
  const keys = Object.keys(props.byStatus)
  return {
    labels: keys.map((k) => k.replaceAll('_', ' ')),
    datasets: [{ data: keys.map((k) => props.byStatus[k]), backgroundColor: keys.map((k) => STATUS_COLORS[k] || '#C4B5FD'), borderWidth: 0 }],
  }
})
const doughnutOpts = { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11 } } } } }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-5 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
