<template>
  <AdminLayout>
    <Head title="Accounts · Import" />
    <div class="flex items-center gap-2 mb-2">
      <BanknotesIcon class="w-6 h-6 text-charcoal" />
      <h1 class="font-display text-2xl font-bold text-charcoal">Accounts</h1>
    </div>
    <AccountsTabs />

    <!-- Step indicator -->
    <div class="flex items-center gap-2 mb-6 text-xs">
      <template v-for="(s,i) in steps" :key="i">
        <div class="flex items-center gap-2" :class="step>=i+1 ? 'text-ink font-semibold' : 'text-muted'">
          <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px]" :class="step>=i+1 ? 'bg-ink text-white' : 'bg-purple-100'">{{ i+1 }}</span>{{ s }}
        </div>
        <span v-if="i<steps.length-1" class="w-6 h-px bg-purple-200"></span>
      </template>
    </div>

    <div class="bg-white rounded-2xl border border-purple-100 p-6 max-w-5xl">
      <!-- Step 1: Upload -->
      <div v-if="step===1">
        <div @dragover.prevent @drop.prevent="onDrop" class="border-2 border-dashed border-purple-200 rounded-2xl p-10 text-center">
          <ArrowUpTrayIcon class="w-10 h-10 mx-auto text-muted/50 mb-3" />
          <p class="text-charcoal font-medium">Drag &amp; drop your .xlsx / .xls / .csv file here</p>
          <p class="text-muted text-sm mb-4">or</p>
          <label class="btn-secondary text-sm px-4 py-2 cursor-pointer inline-block">
            Browse file <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onPick">
          </label>
          <p class="text-muted text-xs mt-3">Max 10MB · up to 5,000 rows</p>
        </div>
        <div v-if="file" class="mt-4 flex items-center justify-between bg-purple-50 rounded-xl px-4 py-3">
          <span class="text-sm text-charcoal">{{ file.name }} <span class="text-muted">· {{ (file.size/1024).toFixed(0) }} KB</span></span>
          <button @click="preview" :disabled="loading" class="btn-primary text-sm px-5 py-2">{{ loading ? 'Reading…' : 'Preview' }}</button>
        </div>
        <p v-if="error" class="text-error text-sm mt-3">{{ error }}</p>
      </div>

      <!-- Step 2: Mapping -->
      <div v-else-if="step===2">
        <h3 class="font-display font-semibold text-charcoal mb-1">Map columns</h3>
        <p class="text-muted text-sm mb-4">Match each system field to a column from your file. Auto-detected where possible.</p>
        <div class="grid sm:grid-cols-2 gap-3 mb-6">
          <div v-for="(label,key) in fields" :key="key" class="flex items-center gap-2">
            <span class="text-sm text-charcoal w-40 shrink-0">{{ label }}</span>
            <select v-model="mapping[key]" class="input-field text-sm py-2 flex-1">
              <option :value="null">— skip —</option>
              <option v-for="(h,i) in headers" :key="i" :value="i">{{ h || ('Column ' + (i+1)) }}</option>
            </select>
          </div>
        </div>
        <h4 class="text-sm font-semibold text-charcoal mb-2">Preview (first 5 rows)</h4>
        <div class="overflow-x-auto border border-purple-100 rounded-xl mb-6">
          <table class="w-full text-xs">
            <thead class="bg-purple-50/50"><tr><th v-for="(h,i) in headers" :key="i" class="px-2 py-2 text-left text-muted whitespace-nowrap">{{ h || ('Col '+(i+1)) }}</th></tr></thead>
            <tbody>
              <tr v-for="(r,ri) in rawRows.slice(0,5)" :key="ri" class="border-t border-purple-50">
                <td v-for="(c,ci) in headers" :key="ci" class="px-2 py-1.5 whitespace-nowrap">{{ r[ci] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex justify-between">
          <button @click="step=1" class="btn-secondary text-sm px-4 py-2">Back</button>
          <button @click="goValidate" class="btn-primary text-sm px-5 py-2">Validate {{ rawRows.length }} rows</button>
        </div>
      </div>

      <!-- Step 3: Validation -->
      <div v-else-if="step===3">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <h3 class="font-display font-semibold text-charcoal">Review &amp; fix</h3>
          <p class="text-sm"><span class="text-success font-semibold">{{ readyCount }} ready</span> · <span class="text-error font-semibold">{{ errorCount }} with errors</span></p>
        </div>
        <div class="overflow-x-auto border border-purple-100 rounded-xl mb-4 max-h-[420px]">
          <table class="w-full text-xs min-w-[900px]">
            <thead class="bg-purple-50/50 sticky top-0"><tr>
              <th class="px-2 py-2 text-left">#</th><th class="px-2 py-2 text-left">Date</th><th class="px-2 py-2 text-left">Type</th>
              <th class="px-2 py-2 text-left">Account Head</th><th class="px-2 py-2 text-left">Party</th>
              <th class="px-2 py-2 text-right">Cash In</th><th class="px-2 py-2 text-right">Cash Out</th><th class="px-2 py-2 text-left">Status</th><th class="px-2 py-2 text-left">Issue</th>
            </tr></thead>
            <tbody>
              <tr v-for="(r,i) in rows" :key="i" class="border-t border-purple-50" :class="r._error ? 'bg-error/5' : ''">
                <td class="px-2 py-1 text-muted">{{ i+1 }}</td>
                <td class="px-2 py-1"><input v-model="r.date" @input="validate" class="w-24 input-field py-1 text-xs"></td>
                <td class="px-2 py-1">
                  <select v-model="r.type" @change="validate" class="input-field py-1 text-xs w-36" :class="!lists.transaction_types.includes(r.type) ? 'border-warning' : ''">
                    <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
                  </select>
                </td>
                <td class="px-2 py-1"><input v-model="r.account_head" class="w-32 input-field py-1 text-xs"></td>
                <td class="px-2 py-1"><input v-model="r.party" class="w-28 input-field py-1 text-xs"></td>
                <td class="px-2 py-1"><input v-model.number="r.cash_in" @input="validate" type="number" class="w-20 input-field py-1 text-xs text-right"></td>
                <td class="px-2 py-1"><input v-model.number="r.cash_out" @input="validate" type="number" class="w-20 input-field py-1 text-xs text-right"></td>
                <td class="px-2 py-1">
                  <select v-model="r.status" class="input-field py-1 text-xs w-24"><option v-for="s in lists.statuses" :key="s" :value="s">{{ s }}</option></select>
                </td>
                <td class="px-2 py-1 text-error">{{ r._error || '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <label class="flex items-center gap-2 text-sm text-charcoal mb-4"><input type="checkbox" v-model="overwrite" class="kz-check"> Overwrite duplicates (same date + amount + type + description)</label>
        <div class="flex justify-between">
          <button @click="step=2" class="btn-secondary text-sm px-4 py-2">Back</button>
          <button @click="commit" :disabled="committing || readyCount===0" class="btn-primary text-sm px-5 py-2 disabled:opacity-50">
            {{ committing ? 'Importing…' : `Import ${readyCount} valid rows` }}
          </button>
        </div>
      </div>

      <!-- Step 4/5: Result -->
      <div v-else-if="step===4">
        <div class="text-center py-6">
          <CheckCircleIcon class="w-14 h-14 text-success mx-auto mb-3" />
          <h3 class="font-display text-xl font-bold text-charcoal mb-1">Import complete</h3>
          <p class="text-muted text-sm mb-5">Batch #{{ result.batch_id }}</p>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-2xl mx-auto mb-6">
            <div class="bg-purple-50 rounded-xl p-3"><p class="text-2xl font-bold text-charcoal">{{ result.processed }}</p><p class="text-xs text-muted">Processed</p></div>
            <div class="bg-green-50 rounded-xl p-3"><p class="text-2xl font-bold text-success">{{ result.imported }}</p><p class="text-xs text-muted">Imported</p></div>
            <div class="bg-amber-50 rounded-xl p-3"><p class="text-2xl font-bold text-amber-600">{{ result.skipped }}</p><p class="text-xs text-muted">Skipped</p></div>
            <div class="bg-blue-50 rounded-xl p-3"><p class="text-2xl font-bold text-blue-600">{{ result.duplicates }}</p><p class="text-xs text-muted">Duplicates</p></div>
          </div>
          <div v-if="result.reasons?.length" class="text-left max-w-2xl mx-auto mb-5 text-xs text-muted bg-purple-50/50 rounded-xl p-3 max-h-32 overflow-y-auto">
            <p v-for="(reason,i) in result.reasons" :key="i">• {{ reason }}</p>
          </div>
          <div class="flex items-center justify-center gap-3">
            <Link :href="route('admin.accounts.transactions')" class="btn-primary text-sm px-5 py-2">View transactions</Link>
            <button @click="rollback(result.batch_id)" class="btn-secondary text-error text-sm px-4 py-2">Undo this import</button>
            <button @click="reset" class="btn-secondary text-sm px-4 py-2">Import another</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Import history -->
    <div class="bg-white rounded-2xl border border-purple-50 mt-6 max-w-5xl overflow-hidden">
      <h3 class="font-display font-semibold text-charcoal px-5 pt-5 pb-3">Import history</h3>
      <table class="w-full text-sm">
        <thead class="bg-purple-50/50 text-left text-xs text-muted uppercase"><tr><th class="px-4 py-2.5">File</th><th class="px-4 py-2.5">Rows</th><th class="px-4 py-2.5">Imported</th><th class="px-4 py-2.5">Skipped</th><th class="px-4 py-2.5">When</th><th class="px-4 py-2.5 text-right">Action</th></tr></thead>
        <tbody class="divide-y divide-purple-50">
          <tr v-for="b in batches" :key="b.id">
            <td class="px-4 py-2.5 text-charcoal">{{ b.filename }}</td><td class="px-4 py-2.5">{{ b.total_rows }}</td>
            <td class="px-4 py-2.5 text-success">{{ b.imported }}</td><td class="px-4 py-2.5 text-muted">{{ b.skipped }}</td>
            <td class="px-4 py-2.5 text-muted text-xs">{{ b.created_at }}</td>
            <td class="px-4 py-2.5 text-right"><button @click="rollback(b.id)" class="text-error text-xs hover:underline">Roll back</button></td>
          </tr>
          <tr v-if="!batches.length"><td colspan="6" class="px-4 py-8 text-center text-muted">No imports yet.</td></tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountsTabs from '@/Components/admin/AccountsTabs.vue'
import { useToast } from 'vue-toastification'
import { BanknotesIcon, ArrowUpTrayIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ fields: Object, lists: Object, batches: Array })
const toast = useToast()

const steps = ['Upload', 'Map', 'Validate', 'Done']
const step = ref(1)
const file = ref(null)
const loading = ref(false)
const error = ref('')

const headers = ref([])
const rawRows = ref([])
const mapping = ref({})
const fields = props.fields

const rows = ref([])
const overwrite = ref(false)
const committing = ref(false)
const result = ref({})

const typeOptions = computed(() => Array.from(new Set([...props.lists.transaction_types, ...rows.value.map(r => r.type).filter(Boolean)])))

const onPick = (e) => { file.value = e.target.files[0] || null; error.value = '' }
const onDrop = (e) => { file.value = e.dataTransfer.files[0] || null; error.value = '' }

const preview = async () => {
  loading.value = true; error.value = ''
  try {
    const fd = new FormData(); fd.append('file', file.value)
    const { data } = await axios.post(route('admin.accounts.import.preview'), fd)
    headers.value = data.headers; rawRows.value = data.rows; mapping.value = data.mapping
    step.value = 2
  } catch (e) { error.value = e?.response?.data?.error || 'Could not read the file.' }
  finally { loading.value = false }
}

// Excel serial number or date string → Y-m-d
const toDate = (v) => {
  if (v === null || v === undefined || v === '') return ''
  const s = String(v).trim()
  if (/^\d+(\.\d+)?$/.test(s) && Number(s) > 59) {
    return new Date(Date.UTC(1899, 11, 30) + Number(s) * 86400000).toISOString().slice(0, 10)
  }
  const d = new Date(s)
  return isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10)
}
const cell = (row, key) => { const i = mapping.value[key]; return i === null || i === undefined ? '' : row[i] }

const goValidate = () => {
  rows.value = rawRows.value.map((r) => {
    const status = props.lists.statuses.includes(String(cell(r, 'status'))) ? String(cell(r, 'status')) : 'Draft'
    return {
      date: toDate(cell(r, 'date')),
      voucher_no: String(cell(r, 'voucher_no') || ''),
      type: String(cell(r, 'type') || ''),
      account_head: String(cell(r, 'account_head') || ''),
      party: String(cell(r, 'party') || ''),
      description: String(cell(r, 'description') || ''),
      payment_account: String(cell(r, 'payment_account') || ''),
      partner: String(cell(r, 'partner') || ''),
      cash_in: Number(cell(r, 'cash_in')) || 0,
      cash_out: Number(cell(r, 'cash_out')) || 0,
      vat_tax: Number(cell(r, 'vat_tax')) || 0,
      discount: Number(cell(r, 'discount')) || 0,
      status,
      remarks: String(cell(r, 'remarks') || ''),
      _error: '',
    }
  })
  validate()
  step.value = 3
}

const validate = () => {
  rows.value.forEach((r) => {
    if (!r.date) r._error = 'Invalid date'
    else if (!r.type) r._error = 'Missing type'
    else if (r.cash_in > 0 && r.cash_out > 0) r._error = 'Both Cash In & Out'
    else if (r.cash_in <= 0 && r.cash_out <= 0) r._error = 'No amount'
    else r._error = ''
  })
}
const readyCount = computed(() => rows.value.filter(r => !r._error).length)
const errorCount = computed(() => rows.value.filter(r => r._error).length)

const commit = async () => {
  committing.value = true
  try {
    const ready = rows.value.filter(r => !r._error).map(({ _error, ...r }) => r)
    const { data } = await axios.post(route('admin.accounts.import.commit'), { filename: file.value?.name, rows: ready, overwrite: overwrite.value })
    result.value = data; step.value = 4
  } catch (e) { toast.error(e?.response?.data?.message || 'Import failed.') }
  finally { committing.value = false }
}

const rollback = (id) => {
  if (!confirm('Roll back this import? All its transactions will be deleted.')) return
  router.post(route('admin.accounts.import.rollback', id), {}, { preserveScroll: true, onSuccess: () => { toast.success('Import rolled back.'); if (step.value === 4) reset() } })
}
const reset = () => { step.value = 1; file.value = null; rows.value = []; result.value = {}; error.value = '' }
</script>

<style scoped>
@reference "../../../../css/app.css";
</style>
