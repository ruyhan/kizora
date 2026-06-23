<template>
  <AdminLayout>
    <Head title="Inventory Analytics" />
    <InventoryTabs />

    <div class="grid lg:grid-cols-2 gap-4 mb-4">
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Most sold products</h3>
        <p class="text-muted text-xs mb-4">All-time units sold</p>
        <div class="h-64"><Bar v-if="mostSold.length" :data="bar(mostSold, '#17181B')" :options="opts" /><Empty v-else /></div>
      </div>
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Fast moving products</h3>
        <p class="text-muted text-xs mb-4">Units sold in the last 30 days</p>
        <div class="h-64"><Bar v-if="fastMoving.length" :data="bar(fastMoving, '#ED1C24')" :options="opts" /><Empty v-else /></div>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 mb-4">
      <List title="Slow moving (last 30 days)" :rows="slowMoving" empty="No sales data." />
      <List title="Least sold (all time)" :rows="leastSold" empty="No sales data." />
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
      <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-2">Low stock products</h3>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-purple-50">
            <tr v-for="p in lowStock" :key="p.id" class="hover:bg-purple-50/20">
              <td class="px-5 py-2.5 text-charcoal">{{ p.name }} <span class="text-muted text-xs font-mono">{{ p.sku }}</span></td>
              <td class="px-5 py-2.5 text-right"><span class="badge badge-warning text-xs">{{ p.stock }} left</span></td>
            </tr>
            <tr v-if="!lowStock.length"><td class="px-5 py-8 text-center text-muted text-sm">None 🎉</td></tr>
          </tbody>
        </table>
      </div>
      <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-2">Out of stock products</h3>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-purple-50">
            <tr v-for="p in outOfStock" :key="p.id" class="hover:bg-purple-50/20">
              <td class="px-5 py-2.5 text-charcoal">{{ p.name }} <span class="text-muted text-xs font-mono">{{ p.sku }}</span></td>
              <td class="px-5 py-2.5 text-right"><span class="badge badge-error text-xs">Out</span></td>
            </tr>
            <tr v-if="!outOfStock.length"><td class="px-5 py-8 text-center text-muted text-sm">None 🎉</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { h } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import InventoryTabs from '@/Components/admin/InventoryTabs.vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
  mostSold: Array, leastSold: Array, fastMoving: Array, slowMoving: Array, lowStock: Array, outOfStock: Array,
})

const Empty = () => h('p', { class: 'text-muted text-sm text-center w-full pt-20' }, 'No data yet.')

const bar = (rows, color) => ({
  labels: rows.map((r) => r.product_name),
  datasets: [{ label: 'Units', data: rows.map((r) => Number(r.qty)), backgroundColor: color, borderRadius: 6, maxBarThickness: 26 }],
})
const opts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 } }, grid: { display: false } }, y: { grid: { color: '#f1f1f3' }, beginAtZero: true } } }

// Small list widget
const List = (p) => h('div', { class: 'bg-white rounded-2xl border border-purple-50 overflow-hidden' }, [
  h('h3', { class: 'font-display font-semibold text-charcoal px-5 pt-5 pb-2' }, p.title),
  h('table', { class: 'w-full text-sm' }, [h('tbody', { class: 'divide-y divide-purple-50' },
    p.rows.length
      ? p.rows.map((r, i) => h('tr', { key: i, class: 'hover:bg-purple-50/20' }, [
          h('td', { class: 'px-5 py-2.5 text-charcoal' }, r.product_name),
          h('td', { class: 'px-5 py-2.5 text-right text-muted' }, `${r.qty} sold`),
        ]))
      : [h('tr', [h('td', { class: 'px-5 py-8 text-center text-muted text-sm' }, p.empty)])],
  )]),
])
</script>
