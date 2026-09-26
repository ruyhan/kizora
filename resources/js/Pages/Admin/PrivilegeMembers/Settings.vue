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
                <p class="hint !mt-0.5">Numbers are issued automatically on approval. Already-issued cards never change.</p>
              </div>
              <div class="inline-flex p-1 rounded-xl bg-purple-50 border border-purple-100">
                <button v-for="m in numberModes" :key="m.key" type="button" @click="form.number_mode = m.key"
                  :class="form.number_mode === m.key ? 'bg-white shadow-sm text-ink' : 'text-muted hover:text-charcoal'"
                  class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors">{{ m.label }}</button>
              </div>

              <div v-if="form.number_mode === 'random'" class="space-y-2">
                <div class="max-w-[220px]">
                  <label class="lbl">Fixed start (8 digits)</label>
                  <input v-model="form.random_prefix" inputmode="numeric" maxlength="8" class="input-field font-mono">
                  <p v-if="form.errors.random_prefix" class="err">{{ form.errors.random_prefix }}</p>
                </div>
                <p class="hint">The last 7 digits are random for every card, so numbers can't be guessed from one another. Recommended.</p>
              </div>

              <div v-else class="grid grid-cols-3 gap-3">
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
              <p v-if="form.number_mode === 'sequential'" class="text-xs text-amber-700">Sequential numbers are easy to guess (…00001, …00002). Keep the checkout card field off if you use this.</p>
              <p class="text-xs text-muted">{{ issued }} card(s) issued so far · next approval gets
                <span class="font-mono font-semibold text-[#9a7b4f]">{{ nextPreview }}</span><span v-if="form.number_mode === 'random'"> (example)</span></p>
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

          <!-- DISCOUNT -->
          <template v-else-if="tab === 'discount'">
            <div>
              <label class="lbl">Member discount (%)</label>
              <div class="relative max-w-[180px]">
                <input v-model.number="form.member_discount_percent" type="number" min="0" max="100" step="0.5" class="input-field pr-9">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-muted">%</span>
              </div>
              <p v-if="form.errors.member_discount_percent" class="err">{{ form.errors.member_discount_percent }}</p>
              <p class="hint">Off the order subtotal (delivery excluded). Every <strong>approved</strong> member gets it automatically at checkout when logged in. Set 0 to turn the discount off.</p>
            </div>

            <label class="flex items-center justify-between gap-4 rounded-xl border border-purple-100 p-4 cursor-pointer"
              :class="{ 'opacity-50': !discountOn }">
              <span>
                <span class="block font-semibold text-charcoal text-sm">Card number field on checkout</span>
                <span class="hint !mt-0.5 block">Lets guests (not logged in) enter a card number to claim the discount. Also tells every visitor the membership exists. Turn off if you notice misuse — logged-in members keep their discount.</span>
              </span>
              <input v-model="form.checkout_card_field" type="checkbox" :disabled="!discountOn" class="w-5 h-5 accent-ink">
            </label>

            <label class="flex items-center justify-between gap-4 rounded-xl border border-purple-100 p-4 cursor-pointer"
              :class="{ 'opacity-50': !discountOn }">
              <span>
                <span class="block font-semibold text-charcoal text-sm">Combine with coupons</span>
                <span class="hint !mt-0.5 block">Off: only the bigger of the coupon or member discount applies. On: both are deducted.</span>
              </span>
              <input v-model="form.discount_stacks_with_coupon" type="checkbox" :disabled="!discountOn" class="w-5 h-5 accent-ink">
            </label>

            <div v-if="discountOn && form.checkout_card_field" class="rounded-xl bg-purple-50/50 border border-purple-100 p-4">
              <p class="text-[11px] uppercase tracking-wider text-muted font-semibold mb-2">Guests see at checkout</p>
              <p class="text-sm font-medium text-charcoal">Privilege Member? Get {{ percentLabel }}% off</p>
              <p class="text-xs text-muted">Enter your Privilege card number to get {{ percentLabel }}% off your order.</p>
            </div>
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
              <label class="lbl">Who can apply — minimum single order</label>
              <div class="relative max-w-[200px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">৳</span>
                <input v-model.number="form.min_order_to_apply" type="number" min="0" step="100" class="input-field pl-7">
              </div>
              <p v-if="form.errors.min_order_to_apply" class="err">{{ form.errors.min_order_to_apply }}</p>
              <p class="hint">A customer can apply only after one <strong>completed</strong> order of at least this amount. The requirement and their progress are shown on their membership page. Set 0 to let anyone apply.</p>
            </div>
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
import { RectangleStackIcon, DocumentTextIcon, SparklesIcon, ReceiptPercentIcon } from '@heroicons/vue/24/outline'

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
  number_mode: props.settings.number_mode || 'random',
  random_prefix: props.settings.random_prefix,
  number_prefix: props.settings.number_prefix,
  serial_digits: props.settings.serial_digits,
  serial_start: props.settings.serial_start,
  accepting_applications: !!props.settings.accepting_applications,
  min_order_to_apply: Number(props.settings.min_order_to_apply) || 0,
  program_title: props.settings.program_title,
  program_intro: props.settings.program_intro,
  benefits: [...(props.settings.benefits || [])],
  member_discount_percent: Number(props.settings.member_discount_percent) || 0,
  checkout_card_field: !!props.settings.checkout_card_field,
  discount_stacks_with_coupon: !!props.settings.discount_stacks_with_coupon,
})

const discountOn = computed(() => Number(form.member_discount_percent) > 0)
const percentLabel = computed(() => Number(form.member_discount_percent) || 0)

const tabs = [
  { key: 'front', label: 'Front', icon: RectangleStackIcon },
  { key: 'back', label: 'Back', icon: DocumentTextIcon },
  { key: 'discount', label: 'Discount', icon: ReceiptPercentIcon },
  { key: 'page', label: 'Member Page', icon: SparklesIcon },
]
const tab = ref('front')
const flipped = ref(false)
// Preview follows the editor tab (Front ↔ Back).
const setTab = (k) => { tab.value = k; if (k === 'front' || k === 'back') flipped.value = k === 'back' }

const sample = reactive({ name: 'Othiya Israq', number: computed(() => nextPreview.value) })

const numberModes = [
  { key: 'random', label: 'Random (secure)' },
  { key: 'sequential', label: 'Sequential' },
]
// Example random tail for the preview — the real one is drawn on approval.
const sampleTail = String(Math.floor(Math.random() * 1e7)).padStart(7, '0')

// Mirror of the server's next-number rule, so edits to the scheme preview instantly.
const nextPreview = computed(() => {
  if (form.number_mode === 'random') {
    const raw = String(form.random_prefix || '').padEnd(8, '0').slice(0, 8) + sampleTail
    return `${raw.slice(0, 4)} ${raw.slice(4, 8)} ${raw.slice(8)}`
  }
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
