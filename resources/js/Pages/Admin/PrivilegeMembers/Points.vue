<template>
  <AdminLayout>
    <Head title="Point Management" />

    <PrivilegeTabs />

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
      <div v-for="s in statCards" :key="s.label" class="bg-white rounded-2xl border border-purple-50 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-wider text-muted font-semibold">{{ s.label }}</p>
        <p class="text-2xl font-display font-bold text-charcoal mt-1">{{ s.value }}</p>
        <p v-if="s.sub" class="text-xs text-muted mt-0.5">{{ s.sub }}</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] gap-6 items-start">

      <!-- ── Rules ── -->
      <form @submit.prevent="save" class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-purple-100">
          <h2 class="font-semibold text-charcoal">Point rules</h2>
          <p class="text-xs text-muted mt-0.5">For customers <strong>without</strong> an approved Privilege Card. Members get their card discount instead.</p>
        </div>

        <div class="p-5 sm:p-6 space-y-5">
          <label class="flex items-center justify-between gap-4 rounded-xl border border-purple-100 p-4 cursor-pointer">
            <span>
              <span class="block font-semibold text-charcoal text-sm">Reward points enabled</span>
              <span class="hint !mt-0.5 block">When off, nobody earns or redeems points and the badge is hidden. Balances are kept.</span>
            </span>
            <input v-model="form.enabled" type="checkbox" class="w-5 h-5 accent-ink">
          </label>

          <!-- Earning -->
          <div>
            <p class="lbl">Earning</p>
            <div class="flex flex-wrap items-center gap-2 text-sm text-charcoal">
              Every
              <div class="relative w-28">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">৳</span>
                <input v-model.number="form.earn_amount" type="number" min="1" step="1" class="input-field py-2 pl-7">
              </div>
              spent earns
              <input v-model.number="form.earn_points" type="number" min="1" step="1" class="input-field py-2 w-20">
              point(s)
            </div>
            <p v-if="form.errors.earn_amount || form.errors.earn_points" class="err">{{ form.errors.earn_amount || form.errors.earn_points }}</p>
            <p class="hint">Credited when the order is marked <strong>Completed</strong>, on the product amount paid (after discounts, delivery excluded). Taken back if the order is later cancelled.</p>
          </div>

          <!-- Redeeming -->
          <div>
            <p class="lbl">Redeeming</p>
            <div class="flex flex-wrap items-center gap-2 text-sm text-charcoal">
              1 point =
              <div class="relative w-28">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">৳</span>
                <input v-model.number="form.point_value" type="number" min="0.01" step="0.01" class="input-field py-2 pl-7">
              </div>
            </div>
            <p v-if="form.errors.point_value" class="err">{{ form.errors.point_value }}</p>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="lbl">Minimum points to redeem</label>
              <input v-model.number="form.min_redeem" type="number" min="1" step="1" class="input-field">
              <p v-if="form.errors.min_redeem" class="err">{{ form.errors.min_redeem }}</p>
              <p class="hint">Customers can use points only once they have at least this many.</p>
            </div>
            <div>
              <label class="lbl">Max per order (% of products)</label>
              <div class="relative">
                <input v-model.number="form.max_redeem_percent" type="number" min="1" max="100" step="1" class="input-field pr-8">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted text-sm">%</span>
              </div>
              <p v-if="form.errors.max_redeem_percent" class="err">{{ form.errors.max_redeem_percent }}</p>
              <p class="hint">100% = points can pay for the whole product amount.</p>
            </div>
          </div>

          <!-- Live example -->
          <div class="rounded-xl bg-[#d4b891]/10 border border-[#d4b891]/40 p-4 text-sm space-y-1.5">
            <p class="text-[11px] uppercase tracking-wider text-[#9a7b4f] font-semibold">Example</p>
            <p class="text-charcoal">An order of <strong>৳{{ money(exampleOrder) }}</strong> earns <strong>{{ exampleEarn }} points</strong>.</p>
            <p class="text-charcoal"><strong>{{ form.min_redeem || 0 }} points</strong> (the minimum) = <strong>৳{{ money((form.min_redeem || 0) * (form.point_value || 0)) }}</strong> off.</p>
            <p class="text-muted text-xs">Customer return: ৳{{ money(returnPer100) }} back for every ৳100 spent ({{ returnPct }}%).</p>
          </div>
        </div>

        <div class="px-5 sm:px-6 py-4 border-t border-purple-100 bg-purple-50/30 flex items-center justify-between gap-3">
          <p class="text-xs text-muted">{{ form.isDirty ? 'You have unsaved changes.' : 'All changes saved.' }}</p>
          <div class="flex gap-2">
            <button type="button" @click="form.reset()" :disabled="!form.isDirty" class="btn-secondary text-sm py-2 disabled:opacity-40">Discard</button>
            <button type="submit" :disabled="form.processing || !form.isDirty" class="btn-primary text-sm py-2 disabled:opacity-40">
              {{ form.processing ? 'Saving…' : 'Save rules' }}
            </button>
          </div>
        </div>
      </form>

      <!-- ── Balances + adjust ── -->
      <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
          <div class="px-5 py-4 border-b border-purple-100 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-charcoal">Customer balances</h2>
            <div class="flex items-center gap-2 w-full sm:w-auto">
              <div class="relative flex-1 sm:w-56">
                <MagnifyingGlassIcon class="w-4 h-4 text-muted absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="search" @input="applyDebounced" type="text" placeholder="Search name, email, phone…" class="input-field text-sm pl-9 py-2 w-full">
              </div>
              <button v-if="can('membership.manage')" type="button" @click="openAdjust(null)" class="btn-primary text-sm py-2 px-3 shrink-0 inline-flex items-center gap-1">
                <PlusIcon class="w-4 h-4" /> Adjust
              </button>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-purple-50/40 text-left text-[11px] uppercase tracking-wider text-muted">
                <tr><th class="px-5 py-2.5">Customer</th><th class="px-5 py-2.5 text-right">Points</th><th class="px-5 py-2.5 text-right">Worth</th><th class="px-5 py-2.5"></th></tr>
              </thead>
              <tbody class="divide-y divide-purple-50">
                <tr v-for="c in customers.data" :key="c.id">
                  <td class="px-5 py-3">
                    <p class="font-medium text-charcoal">{{ c.name }}</p>
                    <p class="text-xs text-muted">{{ c.email }}<span v-if="c.phone"> · {{ c.phone }}</span></p>
                  </td>
                  <td class="px-5 py-3 text-right font-bold text-[#9a7b4f]">{{ c.reward_points.toLocaleString() }}</td>
                  <td class="px-5 py-3 text-right text-muted">৳{{ money(c.reward_points * settings.point_value) }}</td>
                  <td class="px-5 py-3 text-right">
                    <button v-if="can('membership.manage')" type="button" @click="openAdjust(c)" class="text-xs font-semibold text-primary hover:underline">Adjust</button>
                  </td>
                </tr>
                <tr v-if="!customers.data.length">
                  <td colspan="4" class="px-5 py-10 text-center text-muted text-sm">No customers with points{{ search ? ' match your search' : ' yet' }}.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-5 py-3"><Pagination :links="customers.links" /></div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
          <div class="px-5 py-4 border-b border-purple-100"><h2 class="font-semibold text-charcoal">Recent activity</h2></div>
          <ul class="divide-y divide-purple-50">
            <li v-for="t in recent" :key="t.id" class="px-5 py-3 flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm text-charcoal"><span class="font-medium">{{ t.user }}</span> · <span :class="typeCls[t.type]" class="text-xs font-semibold capitalize">{{ typeLabel[t.type] || t.type }}</span></p>
                <p class="text-xs text-muted truncate">{{ t.note }}<span v-if="t.by"> · by {{ t.by }}</span> · {{ t.date }}</p>
              </div>
              <div class="text-right shrink-0">
                <p :class="t.points > 0 ? 'text-success' : 'text-error'" class="font-bold text-sm">{{ t.points > 0 ? '+' : '' }}{{ t.points }}</p>
                <p class="text-[11px] text-muted">bal. {{ t.balance }}</p>
              </div>
            </li>
            <li v-if="!recent.length" class="px-5 py-8 text-center text-muted text-sm">No point activity yet.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Adjust points -->
    <Modal :open="adjusting" @update:open="adjusting = false" max-width="sm:max-w-md" title="Adjust points">
      <form @submit.prevent="submitAdjust" class="p-5 space-y-4">
        <div v-if="!target">
          <label class="lbl">Customer</label>
          <input v-model="lookup" @input="findCustomers" type="text" placeholder="Type a name, email or phone…" class="input-field text-sm">
          <ul v-if="matches.length" class="mt-2 border border-purple-100 rounded-xl divide-y divide-purple-50 max-h-56 overflow-y-auto">
            <li v-for="m in matches" :key="m.id">
              <button type="button" @click="target = m" class="w-full text-left px-3 py-2 hover:bg-purple-50">
                <p class="text-sm font-medium text-charcoal">{{ m.name }} <span class="text-xs text-[#9a7b4f] font-semibold">· {{ m.reward_points }} pts</span></p>
                <p class="text-xs text-muted">{{ m.email }}</p>
              </button>
            </li>
          </ul>
        </div>
        <div v-else class="flex items-center justify-between rounded-xl bg-purple-50/50 border border-purple-100 px-3 py-2">
          <div>
            <p class="text-sm font-medium text-charcoal">{{ target.name }}</p>
            <p class="text-xs text-muted">Current balance: <strong class="text-[#9a7b4f]">{{ target.reward_points }} points</strong></p>
          </div>
          <button type="button" @click="target = null" class="text-xs text-muted hover:text-charcoal">Change</button>
        </div>

        <div>
          <label class="lbl">Points</label>
          <input v-model.number="adjust.points" type="number" step="1" class="input-field" placeholder="e.g. 50 to add, -20 to remove">
          <p v-if="adjust.errors.points" class="err">{{ adjust.errors.points }}</p>
        </div>
        <div>
          <label class="lbl">Note <span class="text-muted font-normal">(optional)</span></label>
          <input v-model="adjust.note" maxlength="200" class="input-field" placeholder="e.g. Goodwill for delayed delivery">
        </div>
        <div class="flex gap-3">
          <button type="button" @click="adjusting = false" class="btn-secondary flex-1">Cancel</button>
          <button type="submit" :disabled="!target || !adjust.points || adjust.processing" class="btn-primary flex-1 disabled:opacity-50">Save</button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PrivilegeTabs from '@/Components/admin/PrivilegeTabs.vue'
import Modal from '@/Components/ui/Modal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { usePermissions } from '@/composables/usePermissions'
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  settings: Object,
  customers: Object,
  recent: { type: Array, default: () => [] },
  filters: Object,
  stats: Object,
})

const { can } = usePermissions()
const money = (v) => Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })

// ── Rules ──
const form = useForm({
  enabled: !!props.settings.enabled,
  earn_amount: Number(props.settings.earn_amount),
  earn_points: Number(props.settings.earn_points),
  point_value: Number(props.settings.point_value),
  min_redeem: Number(props.settings.min_redeem),
  max_redeem_percent: Number(props.settings.max_redeem_percent),
})
const save = () => form.post(route('admin.privilege.points.update'), { preserveScroll: true, onSuccess: () => form.defaults() })

const exampleOrder = 2450
const exampleEarn = computed(() => form.earn_amount > 0 ? Math.floor(exampleOrder / form.earn_amount) * (form.earn_points || 0) : 0)
const returnPer100 = computed(() => form.earn_amount > 0 ? (100 / form.earn_amount) * (form.earn_points || 0) * (form.point_value || 0) : 0)
const returnPct = computed(() => Math.round(returnPer100.value * 100) / 100)

const statCards = computed(() => [
  { label: 'Outstanding points', value: props.stats.outstanding.toLocaleString(), sub: `Worth ৳${money(props.stats.outstanding * props.settings.point_value)}` },
  { label: 'Customers with points', value: props.stats.holders.toLocaleString() },
  { label: 'Total earned', value: props.stats.earned.toLocaleString() },
  { label: 'Total redeemed', value: props.stats.redeemed.toLocaleString() },
])

const typeLabel = { earn: 'Earned', redeem: 'Redeemed', refund: 'Returned', reverse: 'Taken back', adjust: 'Adjusted' }
const typeCls = { earn: 'text-success', redeem: 'text-primary', refund: 'text-success', reverse: 'text-error', adjust: 'text-[#9a7b4f]' }

// ── Balances search ──
const search = ref(props.filters?.search || '')
let t
const applyDebounced = () => {
  clearTimeout(t)
  t = setTimeout(() => router.get(route('admin.privilege.points'), { search: search.value || undefined }, { preserveState: true, preserveScroll: true, replace: true }), 300)
}

// ── Adjust ──
const adjusting = ref(false)
const target = ref(null)
const lookup = ref('')
const matches = ref([])
const adjust = useForm({ points: null, note: '' })

const openAdjust = (customer) => {
  target.value = customer
  lookup.value = ''
  matches.value = []
  adjust.reset()
  adjust.clearErrors()
  adjusting.value = true
}

let lt
const findCustomers = () => {
  clearTimeout(lt)
  lt = setTimeout(async () => {
    if (lookup.value.trim().length < 2) { matches.value = []; return }
    try {
      const { data } = await axios.get(route('admin.privilege.points.search'), { params: { q: lookup.value } })
      matches.value = data
    } catch { matches.value = [] }
  }, 250)
}

const submitAdjust = () => adjust.post(route('admin.privilege.points.adjust', target.value.id), {
  preserveScroll: true,
  onSuccess: () => { adjusting.value = false },
})
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
.hint { @apply text-xs text-muted mt-1; }
.err { @apply text-error text-xs mt-1; }
</style>
