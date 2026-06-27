<template>
  <AdminLayout>
    <Head title="Accounts · Settings" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <form @submit.prevent="save" class="max-w-3xl space-y-5 pb-24">
      <!-- Company / FY -->
      <section class="bg-white rounded-2xl border border-purple-100 p-5 grid sm:grid-cols-2 gap-4">
        <h3 class="sm:col-span-2 font-display font-semibold text-charcoal">General</h3>
        <div><label class="lbl">Company name</label><input v-model="form.company" class="input-field"></div>
        <div><label class="lbl">Currency</label><input v-model="form.currency" class="input-field"></div>
        <div><label class="lbl">Financial year</label><input v-model.number="form.financial_year" type="number" class="input-field"></div>
        <div><label class="lbl">Start date</label><input v-model="form.start_date" type="date" class="input-field"></div>
      </section>

      <!-- Partners -->
      <section class="bg-white rounded-2xl border border-purple-100 p-5 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-display font-semibold text-charcoal">Partners</h3>
          <span class="text-sm font-semibold" :class="ownershipTotal===100 ? 'text-success' : 'text-error'">Ownership total: {{ ownershipTotal }}%</span>
        </div>
        <div v-for="(p,i) in form.partners" :key="i" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
          <input v-model="p.name" placeholder="Partner name" class="input-field sm:col-span-5 py-2 text-sm">
          <div class="sm:col-span-3 relative"><input v-model.number="p.ownership" type="number" min="0" max="100" placeholder="Ownership" class="input-field py-2 text-sm pr-7"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted text-sm">%</span></div>
          <input v-model.number="p.opening_capital" type="number" placeholder="Opening capital" class="input-field sm:col-span-3 py-2 text-sm">
          <button type="button" @click="form.partners.splice(i,1)" class="sm:col-span-1 text-error hover:bg-error/5 rounded-lg h-9 flex items-center justify-center"><TrashIcon class="w-4 h-4" /></button>
        </div>
        <button type="button" @click="form.partners.push({name:'',ownership:0,opening_capital:0})" class="btn-secondary text-sm px-3 py-1.5 inline-flex items-center gap-1.5"><PlusIcon class="w-4 h-4" /> Add partner</button>
        <p v-if="ownershipTotal !== 100" class="text-error text-xs">⚠ Ownership percentages must total 100%.</p>
      </section>

      <!-- Dropdown lists -->
      <section v-for="list in listDefs" :key="list.key" class="bg-white rounded-2xl border border-purple-100 p-5">
        <h3 class="font-display font-semibold text-charcoal mb-3">{{ list.label }}</h3>
        <div class="flex flex-wrap gap-2">
          <div v-for="(item,i) in form[list.key]" :key="i" class="flex items-center gap-1 bg-purple-50 rounded-lg pl-2 pr-1 py-1">
            <input v-model="form[list.key][i]" class="bg-transparent text-sm outline-none w-32">
            <button type="button" @click="form[list.key].splice(i,1)" class="text-muted hover:text-error"><XMarkIcon class="w-3.5 h-3.5" /></button>
          </div>
          <button type="button" @click="form[list.key].push('')" class="text-sm text-primary font-semibold inline-flex items-center gap-1 px-2"><PlusIcon class="w-3.5 h-3.5" /> Add</button>
        </div>
      </section>

      <div class="fixed bottom-0 inset-x-0 md:left-64 bg-white border-t border-purple-100 px-6 py-3 flex items-center justify-end gap-3 z-20">
        <span v-if="form.recentlySuccessful" class="text-success text-sm font-medium">Saved ✓</span>
        <Button type="submit" :loading="form.processing">Save settings</Button>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import Button from '@/Components/ui/Button.vue'
import { BanknotesIcon, PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ settings: Object })

const form = useForm({
  company: props.settings.company,
  currency: props.settings.currency,
  financial_year: props.settings.financial_year,
  start_date: props.settings.start_date,
  partners: JSON.parse(JSON.stringify(props.settings.partners || [])),
  transaction_types: [...(props.settings.transaction_types || [])],
  account_heads: [...(props.settings.account_heads || [])],
  payment_accounts: [...(props.settings.payment_accounts || [])],
  statuses: [...(props.settings.statuses || [])],
})

const listDefs = [
  { key: 'transaction_types', label: 'Transaction Types' },
  { key: 'account_heads', label: 'Account Heads' },
  { key: 'payment_accounts', label: 'Payment Accounts' },
  { key: 'statuses', label: 'Status Options' },
]

const ownershipTotal = computed(() => Math.round(form.partners.reduce((s, p) => s + (Number(p.ownership) || 0), 0) * 100) / 100)

const save = () => {
  // Trim empty list items before saving.
  listDefs.forEach(l => { form[l.key] = form[l.key].map(x => (x || '').trim()).filter(Boolean) })
  form.post(route('admin.accounts.settings.update'), { preserveScroll: true })
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
</style>
