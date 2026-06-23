<template>
  <AdminLayout>
    <Head title="Sales Analysis" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Sales Analysis</h1>
        <p class="text-muted text-sm">Total sales report across Website, POS &amp; AI Chatbot.</p>
      </div>
      <a v-if="canExport" :href="route('admin.reports.sales-export')" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5">
        <ArrowDownTrayIcon class="w-4 h-4" /> Export
      </a>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="k in kpiCards" :key="k.label" class="bg-white rounded-2xl border border-purple-50 p-5">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center" :class="k.bg"><component :is="k.icon" class="w-5 h-5" :class="k.color" /></span>
        <p class="text-2xl font-bold text-charcoal mt-3">{{ k.value }}</p>
        <p class="text-muted text-sm">{{ k.label }}</p>
      </div>
    </div>

    <!-- Channel breakdown -->
    <div class="grid lg:grid-cols-3 gap-4 mb-6">
      <div class="lg:col-span-2 bg-white rounded-2xl border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-3">Sales by channel</h3>
        <table class="w-full">
          <thead class="bg-purple-50/50"><tr><th class="th">Channel</th><th class="th text-center">Orders</th><th class="th text-right">Revenue</th><th class="th text-right">Share</th></tr></thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="c in byChannel" :key="c.channel" class="hover:bg-purple-50/20">
              <td class="px-5 py-3"><span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full" :style="{ background: channelColor[c.channel] }"></span>{{ c.channel }}</span></td>
              <td class="px-5 py-3 text-center">{{ c.orders }}</td>
              <td class="px-5 py-3 text-right font-semibold text-charcoal">৳{{ Number(c.revenue).toLocaleString() }}</td>
              <td class="px-5 py-3 text-right text-muted">{{ share(c.revenue) }}%</td>
            </tr>
            <tr class="bg-purple-50/40 font-bold">
              <td class="px-5 py-3">Total</td>
              <td class="px-5 py-3 text-center">{{ kpis.orders }}</td>
              <td class="px-5 py-3 text-right text-primary">৳{{ Number(kpis.revenue).toLocaleString() }}</td>
              <td class="px-5 py-3 text-right">100%</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Revenue share</h3>
        <p class="text-muted text-xs mb-4">By channel</p>
        <div class="h-56 flex items-center justify-center"><Doughnut v-if="totalRev > 0" :data="channelData" :options="dopts" /><p v-else class="text-muted text-sm">No sales yet.</p></div>
      </div>
    </div>

    <!-- Trend + top products -->
    <div class="grid lg:grid-cols-2 gap-4">
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Revenue trend</h3>
        <p class="text-muted text-xs mb-4">Last 30 days (all channels)</p>
        <div class="h-64"><Line v-if="daily.length" :data="lineData" :options="lopts" /><p v-else class="text-muted text-sm text-center pt-20">No data.</p></div>
      </div>
      <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-3">Top products (by revenue)</h3>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-purple-50">
            <tr v-for="p in topProducts" :key="p.product_name" class="hover:bg-purple-50/20">
              <td class="px-5 py-2.5 text-charcoal truncate max-w-[200px]">{{ p.product_name }}</td>
              <td class="px-5 py-2.5 text-center text-muted">{{ p.sold }} sold</td>
              <td class="px-5 py-2.5 text-right font-semibold">৳{{ Number(p.revenue).toLocaleString() }}</td>
            </tr>
            <tr v-if="!topProducts.length"><td class="px-5 py-8 text-center text-muted text-sm">No sales yet.</td></tr>
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
import { Line, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, PointElement, ArcElement, CategoryScale, LinearScale, Filler } from 'chart.js'
import { BanknotesIcon, ShoppingBagIcon, CalculatorIcon, CubeIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, ArcElement, CategoryScale, LinearScale, Filler)

const props = defineProps({ kpis: Object, byChannel: Array, byStatus: Object, daily: Array, topProducts: Array, canExport: Boolean })

const channelColor = { Website: '#17181B', POS: '#0EA5E9', Chatbot: '#ED1C24' }
const totalRev = computed(() => props.byChannel.reduce((s, c) => s + Number(c.revenue), 0))
const share = (v) => totalRev.value ? Math.round((Number(v) / totalRev.value) * 100) : 0

const kpiCards = computed(() => [
  { label: 'Total Revenue', value: '৳' + Number(props.kpis.revenue).toLocaleString(), icon: BanknotesIcon, bg: 'bg-green-50', color: 'text-success' },
  { label: 'Total Orders', value: Number(props.kpis.orders).toLocaleString(), icon: ShoppingBagIcon, bg: 'bg-purple-50', color: 'text-primary' },
  { label: 'Avg. Order Value', value: '৳' + Number(props.kpis.aov).toLocaleString(), icon: CalculatorIcon, bg: 'bg-blue-50', color: 'text-blue-500' },
  { label: 'Units Sold', value: Number(props.kpis.units).toLocaleString(), icon: CubeIcon, bg: 'bg-amber-50', color: 'text-amber-500' },
])

const channelData = computed(() => ({
  labels: props.byChannel.map((c) => c.channel),
  datasets: [{ data: props.byChannel.map((c) => Number(c.revenue)), backgroundColor: props.byChannel.map((c) => channelColor[c.channel]), borderWidth: 0 }],
}))
const dopts = { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11 } } } } }

const lineData = computed(() => ({
  labels: props.daily.map((d) => d.date),
  datasets: [{ label: 'Revenue', data: props.daily.map((d) => d.revenue), borderColor: '#17181B', backgroundColor: 'rgba(23,24,27,.06)', fill: true, tension: 0.4, pointRadius: 0 }],
}))
const lopts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f1f1f3' } } } }
</script>

<style scoped>
@reference "../../../../css/app.css";
.th { @apply text-left px-5 py-3 text-xs font-semibold text-muted uppercase tracking-wider; }
</style>
