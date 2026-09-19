<template>
  <AdminLayout>
    <Head title="Privilege Card Settings" />

    <PrivilegeTabs />

    <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-6 items-start">

      <!-- ── Live preview (left) ── -->
      <div class="lg:sticky lg:top-6">
        <div class="rounded-2xl bg-[radial-gradient(ellipse_at_top,#26262a,#0b0b0c_70%)] p-6 sm:p-10 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <p class="text-[11px] uppercase tracking-[0.25em] text-[#d4b891] font-semibold">Live preview</p>
            <div class="inline-flex p-1 rounded-xl bg-white/5 border border-white/10">
              <button v-for="side in ['front', 'back']" :key="side" @click="flipped = side === 'back'"
                :class="(side === 'back') === flipped ? 'bg-[#d4b891] text-black' : 'text-white/60 hover:text-white'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold capitalize transition-colors">{{ side }}</button>
            </div>
          </div>
          <div class="max-w-md mx-auto">
            <PrivilegeCard v-model:flipped="flipped" :card-number="sample.number" :holder-name="sample.name"
              :tier-line1="form.tier_line_1" :tier-line2="form.tier_line_2"
              :terms-heading="form.terms_heading" :terms="form.terms" shine />
          </div>
          <div class="grid grid-cols-2 gap-3 mt-8">
            <div>
              <label class="block text-[11px] text-white/40 mb-1">Preview holder name</label>
              <input v-model="sample.name" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-white/30 focus:outline-none focus:border-[#d4b891]/60">
            </div>
            <div>
              <label class="block text-[11px] text-white/40 mb-1">Next card number</label>
              <p class="px-3 py-2 text-sm font-mono text-[#d4b891]">{{ nextPreview }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Content editor (right) ── -->
      <form @submit.prevent="save" class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
        <div class="flex border-b border-purple-100 px-2">
          <button v-for="t in tabs" :key="t.key" type="button" @click="setTab(t.key)"
            :class="tab === t.key ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-charcoal'"
            class="px-4 py-3.5 text-sm font-semibold border-b-2 -mb-px transition-colors inline-flex items-center gap-2">
            <component :is="t.icon" class="w-4 h-4" /> {{ t.label }}
          </button>
        </div>

        <div class="p-5 sm:p-6 space-y-5">
          <!-- FRONT -->
          <template v-if="tab === 'front'">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="lbl">Card name — line 1</label>
                <input v-model="form.tier_line_1" maxlength="30" class="input-field uppercase" placeholder="PRIVILEGE">
                <p v-if="form.errors.tier_line_1" class="err">{{ form.errors.tier_line_1 }}</p>
                <p class="hint">Large golden title on the front.</p>
              </div>
              <div>
                <label class="lbl">Card name — line 2</label>
                <input v-model="form.tier_line_2" maxlength="30" class="input-field uppercase" placeholder="CARD">
                <p class="hint">Small spaced subtitle under the title.</p>
              </div>
            </div>

            <div class="rounded-xl border border-purple-100 p-4 space-y-4">
              <div>
                <p class="font-semibold text-charcoal text-sm">Card number format</p>
                <p class="hint !mt-0.5">Numbers are issued automatically on approval: <span class="font-mono">prefix + serial</span>. Already-issued cards never change.</p>
              </div>
              <div class="grid grid-cols-3 gap-3">
                <div class="col-span-3 sm:col-span-1">
                  <label class="lbl">Prefix</label>
                  <input v-model="form.number_prefix" inputmode="numeric" maxlength="12" class="input-field font-mono">
                  <p v-if="form.errors.number_prefix" class="err">{{ form.errors.number_prefix }}</p>
                </div>
                <div>
                  <label class="lbl">Serial digits</label>
                  <input v-model.number="form.serial_digits" type="number" min="3" max="8" class="input-field">
                </div>
                <div>
                  <label class="lbl">Start from</label>
                  <input v-model.number="form.serial_start" type="number" min="1" class="input-field">
                </div>
              </div>
              <p class="text-xs text-muted">{{ issued }} card(s) issued so far · next approval gets <span class="font-mono font-semibold text-[#9a7b4f]">{{ nextPreview }}</span></p>
            </div>
            <p class="text-xs text-muted">The member's <strong>account name</strong> is printed on the card automatically.</p>
          </template>

          <!-- BACK -->
          <template v-else-if="tab === 'back'">
            <div>
              <label class="lbl">Heading</label>
              <input v-model="form.terms_heading" maxlength="60" class="input-field">
              <p v-if="form.errors.terms_heading" class="err">{{ form.errors.terms_heading }}</p>
            </div>
            <ListEditor v-model="form.terms" label="Terms & conditions" :max="10" :maxlength="140" placeholder="Add a term…"
              hint="Shown on the back of the card and beside the card in the customer's account. Keep each point short — 6 points fit best." />
          </template>

          <!-- MEMBER PAGE -->
          <template v-else>
            <label class="flex items-center justify-between gap-4 rounded-xl border border-purple-100 p-4 cursor-pointer">
              <span>
                <span class="block font-semibold text-charcoal text-sm">Accept new applications</span>
                <span class="hint !mt-0.5 block">When off, customers see the program but can't apply.</span>
              </span>
              <input v-model="form.accepting_applications" type="checkbox" class="w-5 h-5 accent-ink">
            </label>
            <div>
              <label class="lbl">Program title</label>
              <input v-model="form.program_title" maxlength="80" class="input-field">
            </div>
            <div>
              <label class="lbl">Introduction</label>
              <textarea v-model="form.program_intro" rows="3" maxlength="500" class="input-field"></textarea>
            </div>
            <ListEditor v-model="form.benefits" label="Member benefits" :max="12" :maxlength="120" placeholder="Add a benefit…"
              hint="Listed on the “Become a member” page for non-members." />
          </template>
        </div>

        <div class="px-5 sm:px-6 py-4 border-t border-purple-100 bg-purple-50/30 flex items-center justify-between gap-3">
          <p class="text-xs text-muted">{{ form.isDirty ? 'You have unsaved changes.' : 'All changes saved.' }}</p>
          <div class="flex gap-2">
            <button type="button" @click="form.reset()" :disabled="!form.isDirty" class="btn-secondary text-sm py-2 disabled:opacity-40">Discard</button>
            <button type="submit" :disabled="form.processing || !form.isDirty" class="btn-primary text-sm py-2 disabled:opacity-40">
              {{ form.processing ? 'Saving…' : 'Save settings' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PrivilegeTabs from '@/Components/admin/PrivilegeTabs.vue'
import PrivilegeCard from '@/Components/PrivilegeCard.vue'
import ListEditor from '@/Components/admin/PrivilegeListEditor.vue'
import { RectangleStackIcon, DocumentTextIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  settings: Object,
  nextNumber: String,
  issued: { type: Number, default: 0 },
})

const form = useForm({
  tier_line_1: props.settings.tier_line_1,
  tier_line_2: props.settings.tier_line_2,
  terms_heading: props.settings.terms_heading,
  terms: [...(props.settings.terms || [])],
  number_prefix: props.settings.number_prefix,
  serial_digits: props.settings.serial_digits,
  serial_start: props.settings.serial_start,
  accepting_applications: !!props.settings.accepting_applications,
  program_title: props.settings.program_title,
  program_intro: props.settings.program_intro,
  benefits: [...(props.settings.benefits || [])],
})

const tabs = [
  { key: 'front', label: 'Front', icon: RectangleStackIcon },
  { key: 'back', label: 'Back', icon: DocumentTextIcon },
  { key: 'page', label: 'Member Page', icon: SparklesIcon },
]
const tab = ref('front')
const flipped = ref(false)
// Preview follows the editor tab (Front ↔ Back).
const setTab = (k) => { tab.value = k; if (k !== 'page') flipped.value = k === 'back' }

const sample = reactive({ name: 'Othiya Israq', number: computed(() => nextPreview.value) })

// Mirror of the server's next-number rule, so edits to the scheme preview instantly.
const nextPreview = computed(() => {
  const digits = Math.min(8, Math.max(3, Number(form.serial_digits) || 5))
  const serverSerial = Number(String(props.nextNumber || '').replace(/\s/g, '').slice(String(props.settings.number_prefix).length)) || 1
  const serial = Math.max(serverSerial, Number(form.serial_start) || 1)
  const raw = String(form.number_prefix || '') + String(serial).padStart(digits, '0')
  return raw.length > 8 ? `${raw.slice(0, 4)} ${raw.slice(4, 8)} ${raw.slice(8)}` : raw
})

const save = () => form.post(route('admin.privilege.settings.update'), {
  preserveScroll: true,
  onSuccess: () => form.defaults(),
})
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1; }
.hint { @apply text-xs text-muted mt-1; }
.err { @apply text-error text-xs mt-1; }
</style>
