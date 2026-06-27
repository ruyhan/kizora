<template>
  <AdminLayout>
    <Head title="Admin Dashboard" />

    <!-- Header + date range -->
    <div class="flex items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Dashboard</h1>
        <p class="text-muted text-sm">{{ filters?.label }} overview</p>
      </div>
      <DateRangeFilter route-name="admin.dashboard" :filters="filters" />
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div v-for="stat in statCards" :key="stat.label"
        class="bg-white rounded-2xl p-5 shadow-sm border border-purple-50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="stat.bg">
            <component :is="stat.icon" class="w-5 h-5" :class="stat.iconColor" />
          </div>
          <span v-if="stat.change !== null && stat.change !== undefined"
            :class="stat.change >= 0 ? 'text-success bg-success/10' : 'text-error bg-error/10'"
            class="text-xs font-semibold px-2 py-1 rounded-lg" title="vs previous 30 days">
            {{ stat.change >= 0 ? '+' : '' }}{{ stat.change }}%
          </span>
        </div>
        <p class="text-2xl font-bold text-charcoal font-display">{{ stat.value }}</p>
        <p class="text-muted text-sm mt-0.5">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid lg:grid-cols-3 gap-6 mb-6">

      <!-- Revenue & Orders Line Chart -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="font-semibold text-charcoal text-base">Revenue & Orders</h3>
            <p class="text-muted text-xs mt-0.5">Last 30 days performance</p>
          </div>
          <div class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-purple-500 inline-block"></span>
              Revenue
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>
              Orders
            </span>
          </div>
        </div>
        <div class="h-64">
          <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
          <div v-else class="h-full flex items-center justify-center text-muted text-sm">
            No sales data yet
          </div>
        </div>
      </div>

      <!-- Top Products Bar Chart -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
        <div class="mb-6">
          <h3 class="font-semibold text-charcoal text-base">Top Products</h3>
          <p class="text-muted text-xs mt-0.5">By units sold</p>
        </div>
        <div class="h-64">
          <Bar v-if="barChartData" :data="barChartData" :options="barChartOptions" />
          <div v-else class="h-full flex items-center justify-center text-muted text-sm">
            No sales data yet
          </div>
        </div>
      </div>
    </div>

    <!-- Orders & Sidebar -->
    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Recent Orders -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50">
          <h3 class="font-semibold text-charcoal text-base">Recent Orders</h3>
          <Link :href="route('admin.orders.index')" class="text-primary text-sm hover:underline flex items-center gap-1">
            View All <ArrowRightIcon class="w-3.5 h-3.5" />
          </Link>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-purple-50/60">
              <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-muted uppercase tracking-wider">Order</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-muted uppercase tracking-wider">Customer</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-muted uppercase tracking-wider">Status</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-muted uppercase tracking-wider">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-purple-50">
              <tr v-for="order in recentOrders" :key="order.id"
                class="hover:bg-purple-50/30 transition-colors">
                <td class="px-6 py-4">
                  <Link :href="route('admin.orders.show', order.id)"
                    class="font-medium text-charcoal text-sm hover:text-primary transition-colors">
                    {{ order.order_number }}
                  </Link>
                  <p class="text-xs text-muted mt-0.5">{{ order.created_at }}</p>
                </td>
                <td class="px-6 py-4 text-sm text-charcoal">{{ order.customer }}</td>
                <td class="px-6 py-4">
                  <span :class="statusBadge(order.status)" class="badge text-xs">{{ order.status_label }}</span>
                </td>
                <td class="px-6 py-4 text-right font-semibold text-charcoal text-sm">
                  ৳{{ Number(order.total).toFixed(2) }}
                </td>
              </tr>
              <tr v-if="!recentOrders?.length">
                <td colspan="4" class="px-6 py-10 text-center text-muted text-sm">No orders yet</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Column -->
      <div class="space-y-5">

        <!-- Revenue Breakdown Doughnut -->
        <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
          <h3 class="font-semibold text-charcoal text-base mb-1">Order Status</h3>
          <p class="text-muted text-xs mb-4">Overall breakdown</p>
          <div class="h-44 flex items-center justify-center">
            <Doughnut v-if="doughnutData" :data="doughnutData" :options="doughnutOptions" />
            <p v-else class="text-muted text-sm">No data yet</p>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
          <h3 class="font-semibold text-charcoal text-base mb-4">Quick Actions</h3>
          <div class="space-y-2">
            <Link :href="route('admin.products.create')"
              class="flex items-center gap-3 p-3 rounded-xl bg-purple-50 hover:bg-primary hover:text-white text-charcoal transition-all duration-200 text-sm group">
              <PlusCircleIcon class="w-5 h-5 text-primary group-hover:text-white transition-colors" />
              Add New Product
            </Link>
            <Link :href="route('admin.orders.index', { status: 'pending' })"
              class="flex items-center gap-3 p-3 rounded-xl bg-warning/10 hover:bg-warning hover:text-white text-charcoal transition-all duration-200 text-sm group">
              <ClockIcon class="w-5 h-5 text-warning group-hover:text-white transition-colors" />
              View Pending Orders
              <span v-if="stats.pending_orders > 0" class="ml-auto badge badge-warning text-xs">
                {{ stats.pending_orders }}
              </span>
            </Link>
            <Link :href="route('home')" target="_blank"
              class="flex items-center gap-3 p-3 rounded-xl bg-purple-50 hover:bg-purple-100 text-charcoal transition-all duration-200 text-sm group">
              <ArrowTopRightOnSquareIcon class="w-5 h-5 text-muted group-hover:text-primary transition-colors" />
              View Live Website
            </Link>
          </div>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { statusBadge } from '@/composables/useOrderStatus'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import {
  ShoppingBagIcon,
  BanknotesIcon,
  ClockIcon,
  UsersIcon,
  ArrowRightIcon,
  PlusCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'

ChartJS.register(
  CategoryScale, LinearScale, PointElement, LineElement,
  BarElement, ArcElement, Title, Tooltip, Legend, Filler
)

const props = defineProps({
  filters: Object,
  stats: Object,
  recentOrders: Array,
  salesData: Array,
  topProducts: Array,
})

// ── Stat Cards ──
const statCards = computed(() => [
  { icon: ShoppingBagIcon, label: 'Total Orders',    value: props.stats.total_orders,                              bg: 'bg-purple-100',  iconColor: 'text-primary',   change: props.stats.orders_change },
  { icon: BanknotesIcon,   label: 'Total Revenue',   value: '৳' + Number(props.stats.total_revenue).toFixed(0),   bg: 'bg-success/10',  iconColor: 'text-success',   change: props.stats.revenue_change },
  { icon: ClockIcon,       label: 'Pending Orders',  value: props.stats.pending_orders,                            bg: 'bg-warning/10',  iconColor: 'text-warning',   change: props.stats.pending_change },
  { icon: UsersIcon,       label: 'Customers',       value: props.stats.total_customers,                           bg: 'bg-blue-50',     iconColor: 'text-blue-500',  change: props.stats.customers_change },
])

// ── Line Chart: Revenue & Orders (last 30 days) ──
const lineChartData = computed(() => {
  if (!props.salesData?.length) return null
  const labels = props.salesData.map(d => {
    const dt = new Date(d.date)
    return dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
  })
  return {
    labels,
    datasets: [
      {
        label: 'Revenue (৳)',
        data: props.salesData.map(d => Number(d.revenue)),
        borderColor: '#17181B',
        backgroundColor: 'rgba(23, 24, 27, 0.08)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#17181B',
        pointRadius: 3,
        pointHoverRadius: 5,
        yAxisID: 'y',
      },
      {
        label: 'Orders',
        data: props.salesData.map(d => Number(d.orders)),
        borderColor: '#F2A93B',
        backgroundColor: 'rgba(242, 169, 59, 0.08)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#F2A93B',
        pointRadius: 3,
        pointHoverRadius: 5,
        yAxisID: 'y1',
      },
    ],
  }
})

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1A1530',
      titleColor: '#fff',
      bodyColor: 'rgba(255,255,255,0.75)',
      padding: 10,
      cornerRadius: 8,
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#6B6580', font: { size: 11 }, maxTicksLimit: 8 },
    },
    y: {
      position: 'left',
      grid: { color: 'rgba(124,58,237,0.06)' },
      ticks: { color: '#6B6580', font: { size: 11 }, callback: v => '৳' + v },
    },
    y1: {
      position: 'right',
      grid: { drawOnChartArea: false },
      ticks: { color: '#6B6580', font: { size: 11 } },
    },
  },
}

// ── Bar Chart: Top Products ──
const barChartData = computed(() => {
  if (!props.topProducts?.length) return null
  return {
    labels: props.topProducts.map(p => p.product_name.length > 14 ? p.product_name.slice(0, 14) + '…' : p.product_name),
    datasets: [{
      label: 'Units Sold',
      data: props.topProducts.map(p => Number(p.sold)),
      backgroundColor: [
        'rgba(124,58,237,0.85)',
        'rgba(124,58,237,0.68)',
        'rgba(124,58,237,0.52)',
        'rgba(124,58,237,0.38)',
        'rgba(124,58,237,0.24)',
      ],
      borderRadius: 6,
      borderSkipped: false,
    }],
  }
})

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1A1530',
      titleColor: '#fff',
      bodyColor: 'rgba(255,255,255,0.75)',
      padding: 10,
      cornerRadius: 8,
    },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#6B6580', font: { size: 10 } } },
    y: { grid: { color: 'rgba(124,58,237,0.06)' }, ticks: { color: '#6B6580', font: { size: 11 } } },
  },
}

// ── Doughnut Chart: Order Status ──
const doughnutData = computed(() => {
  const completed = props.stats.completed_orders ?? 0
  const pending   = props.stats.pending_orders   ?? 0
  const other     = (props.stats.total_orders ?? 0) - completed - pending
  if (props.stats.total_orders === 0) return null
  return {
    labels: ['Completed', 'Pending', 'Other'],
    datasets: [{
      data: [completed, pending, other],
      backgroundColor: ['#2E9D63', '#F2A93B', '#C4B2F6'],
      borderWidth: 0,
      hoverOffset: 4,
    }],
  }
})

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%',
  plugins: {
    legend: {
      position: 'bottom',
      labels: { color: '#6B6580', font: { size: 11 }, boxWidth: 10, padding: 12 },
    },
    tooltip: {
      backgroundColor: '#1A1530',
      titleColor: '#fff',
      bodyColor: 'rgba(255,255,255,0.75)',
      padding: 10,
      cornerRadius: 8,
    },
  },
}
</script>
