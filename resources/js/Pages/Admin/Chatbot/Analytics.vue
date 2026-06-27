<template>
  <AdminLayout>
    <Head title="Chatbot Analytics" />
    <ChatbotTabs />

    <div class="flex items-center justify-end mb-4">
      <DateRangeFilter route-name="admin.chatbot.analytics" :filters="filters" />
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="k in kpiCards" :key="k.label" class="bg-white rounded-2xl border border-purple-50 p-5">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center" :class="k.bg"><component :is="k.icon" class="w-5 h-5" :class="k.color" /></span>
        <p class="text-2xl font-bold text-charcoal mt-3">{{ k.value }}</p>
        <p class="text-muted text-sm">{{ k.label }}</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 mb-4">
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Most asked products</h3>
        <p class="text-muted text-xs mb-4">Shown by the assistant</p>
        <div class="h-64"><Bar v-if="mostAsked.length" :data="bar(mostAsked, '#17181B')" :options="opts" /><Empty v-else /></div>
      </div>
      <div class="bg-white rounded-2xl border border-purple-50 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-1">Top categories asked</h3>
        <p class="text-muted text-xs mb-4">By chat recommendations</p>
        <div class="h-64 flex items-center justify-center"><Doughnut v-if="topCategories.length" :data="doughnut" :options="dopts" /><Empty v-else /></div>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
      <List title="Most clicked products" :rows="mostClicked" unit="clicks" empty="No clicks yet." />
      <List title="Products leading to orders" :rows="leadingToOrders" unit="sold" empty="No chatbot orders yet." />
      <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
        <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-2">Top questions</h3>
        <table class="w-full text-sm"><tbody class="divide-y divide-purple-50">
          <tr v-for="(q, i) in topQuestions" :key="i" class="hover:bg-purple-50/20">
            <td class="px-5 py-2.5 text-charcoal truncate max-w-[220px]">{{ q.query }}</td>
            <td class="px-5 py-2.5 text-right text-muted">{{ q.count }}×</td>
          </tr>
          <tr v-if="!topQuestions.length"><td class="px-5 py-8 text-center text-muted text-sm">No questions yet.</td></tr>
        </tbody></table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { h, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ChatbotTabs from '@/Components/admin/ChatbotTabs.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale } from 'chart.js'
import { ChatBubbleLeftRightIcon, ShoppingBagIcon, BanknotesIcon, ArrowTrendingUpIcon } from '@heroicons/vue/24/outline'
ChartJS.register(Title, Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale)

const props = defineProps({ filters: Object, kpis: Object, mostAsked: Array, mostClicked: Array, leadingToOrders: Array, topQuestions: Array, topCategories: Array })

const kpiCards = computed(() => [
  { label: 'Conversations', value: Number(props.kpis.conversations).toLocaleString(), icon: ChatBubbleLeftRightIcon, bg: 'bg-purple-50', color: 'text-primary' },
  { label: 'Chatbot Orders', value: Number(props.kpis.orders).toLocaleString(), icon: ShoppingBagIcon, bg: 'bg-blue-50', color: 'text-blue-500' },
  { label: 'Revenue', value: '৳' + Number(props.kpis.revenue).toLocaleString(), icon: BanknotesIcon, bg: 'bg-green-50', color: 'text-success' },
  { label: 'Conversion Rate', value: props.kpis.conversion_rate + '%', icon: ArrowTrendingUpIcon, bg: 'bg-amber-50', color: 'text-amber-500' },
])

const Empty = () => h('p', { class: 'text-muted text-sm text-center w-full pt-20' }, 'No data yet.')
const bar = (rows, color) => ({ labels: rows.map((r) => r.name), datasets: [{ data: rows.map((r) => Number(r.count)), backgroundColor: color, borderRadius: 6, maxBarThickness: 26 }] })
const opts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 } }, grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f1f1f3' } } } }
const PIE = ['#6366F1', '#0EA5E9', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6']
const doughnut = computed(() => ({ labels: props.topCategories.map((c) => c.name), datasets: [{ data: props.topCategories.map((c) => Number(c.count)), backgroundColor: PIE, borderWidth: 0 }] }))
const dopts = { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, usePointStyle: true, boxWidth: 6 } } } }

const List = (p) => h('div', { class: 'bg-white rounded-2xl border border-purple-50 overflow-hidden' }, [
  h('h3', { class: 'font-display font-semibold text-charcoal px-5 pt-5 pb-2' }, p.title),
  h('table', { class: 'w-full text-sm' }, [h('tbody', { class: 'divide-y divide-purple-50' },
    p.rows.length
      ? p.rows.map((r, i) => h('tr', { key: i, class: 'hover:bg-purple-50/20' }, [
          h('td', { class: 'px-5 py-2.5 text-charcoal truncate max-w-[200px]' }, r.name),
          h('td', { class: 'px-5 py-2.5 text-right text-muted' }, `${r.count} ${p.unit}`),
        ]))
      : [h('tr', [h('td', { class: 'px-5 py-8 text-center text-muted text-sm' }, p.empty)])],
  )]),
])
</script>
