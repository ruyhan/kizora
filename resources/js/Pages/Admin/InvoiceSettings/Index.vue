<template>
  <AdminLayout>
    <Head title="Invoice Settings" />
    <SettingsTabs />

    <div class="grid lg:grid-cols-2 gap-6">
      <!-- ── Form ── -->
      <form @submit.prevent="save" class="space-y-5">
        <!-- Branding -->
        <section class="bg-white rounded-2xl border border-purple-50 p-5 space-y-4">
          <h3 class="font-display font-semibold text-charcoal">Branding</h3>
          <div>
            <label class="lbl">Company logo</label>
            <div class="flex items-center gap-3">
              <div class="w-20 h-20 rounded-xl border border-purple-100 bg-purple-50/40 flex items-center justify-center overflow-hidden shrink-0">
                <img v-if="form.company_logo" :src="form.company_logo" class="w-full h-full object-contain" alt="">
                <PhotoIcon v-else class="w-6 h-6 text-muted/40" />
              </div>
              <button type="button" @click="picker = true" class="btn-secondary text-sm px-3 py-2">{{ form.company_logo ? 'Change' : 'Choose logo' }}</button>
              <button v-if="form.company_logo" type="button" @click="form.company_logo = ''" class="text-error text-sm hover:underline">Remove</button>
            </div>
          </div>
          <div class="grid sm:grid-cols-2 gap-3">
            <div><label class="lbl">Primary colour</label><ColorField v-model="form.primary_color" /></div>
            <div><label class="lbl">Secondary colour</label><ColorField v-model="form.secondary_color" /></div>
          </div>
        </section>

        <!-- Company info -->
        <section class="bg-white rounded-2xl border border-purple-50 p-5 space-y-3">
          <h3 class="font-display font-semibold text-charcoal">Company information</h3>
          <div><label class="lbl">Company name</label><input v-model="form.company_name" class="input-field"></div>
          <div><label class="lbl">Address</label><textarea v-model="form.company_address" rows="2" class="input-field"></textarea></div>
          <div class="grid sm:grid-cols-2 gap-3">
            <div><label class="lbl">Phone</label><input v-model="form.company_phone" class="input-field"></div>
            <div><label class="lbl">Email</label><input v-model="form.company_email" class="input-field"></div>
          </div>
          <div><label class="lbl">Website</label><input v-model="form.company_website" class="input-field"></div>
        </section>

        <!-- Content -->
        <section class="bg-white rounded-2xl border border-purple-50 p-5 space-y-3">
          <h3 class="font-display font-semibold text-charcoal">Invoice content</h3>
          <div class="grid sm:grid-cols-2 gap-3">
            <div><label class="lbl">Invoice title</label><input v-model="form.invoice_title" class="input-field"></div>
            <div><label class="lbl">Header text (optional)</label><input v-model="form.header_text" class="input-field"></div>
          </div>
          <div><label class="lbl">Footer message</label><textarea v-model="form.footer_message" rows="2" class="input-field"></textarea></div>
          <div><label class="lbl">Additional notes (optional)</label><textarea v-model="form.notes" rows="2" class="input-field"></textarea></div>
          <div><label class="lbl">Terms &amp; conditions (optional)</label><textarea v-model="form.terms" rows="2" class="input-field"></textarea></div>
        </section>

        <div class="flex items-center gap-3">
          <Button type="submit" :loading="form.processing">Save settings</Button>
          <span v-if="form.recentlySuccessful" class="text-success text-sm font-medium">Saved ✓</span>
        </div>
      </form>

      <!-- ── Live preview ── -->
      <div class="lg:sticky lg:top-6 self-start">
        <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Live preview</p>
        <div class="rounded-2xl overflow-hidden shadow-lg border border-purple-50 bg-white text-[12px] leading-relaxed">
          <div :style="{ background: form.secondary_color }" class="h-1.5"></div>
          <div class="p-6">
            <!-- header -->
            <div class="flex items-start justify-between gap-4 mb-5">
              <div>
                <img v-if="form.company_logo" :src="form.company_logo" class="max-h-12 mb-2" alt="">
                <div v-else class="text-lg font-bold" :style="{ color: form.primary_color }">{{ form.company_name || 'Kizora' }}</div>
                <p class="text-muted whitespace-pre-line mt-1">{{ form.company_address }}</p>
                <p class="text-muted">{{ form.company_phone }}</p>
                <p class="text-muted">{{ form.company_email }}</p>
                <p class="text-muted">{{ form.company_website }}</p>
              </div>
              <div class="text-right shrink-0">
                <div class="text-xl font-extrabold tracking-wide" :style="{ color: form.primary_color }">{{ form.invoice_title || 'INVOICE' }}</div>
                <p v-if="form.header_text" class="text-muted">{{ form.header_text }}</p>
                <p class="mt-2 font-semibold">INV-2026-AB12CD</p>
                <p class="text-muted">Order: KZ-SAMPLE</p>
                <p class="text-muted">Date: {{ today }}</p>
              </div>
            </div>
            <!-- bill to -->
            <div class="border border-gray-100 rounded-lg p-3 mb-4">
              <p class="font-bold text-[10px] uppercase tracking-wide mb-1" :style="{ color: form.primary_color }">Bill To</p>
              <p class="font-semibold">Rahim Ahmed</p>
              <p class="text-muted">+880 1700-000000 · Banani, Dhaka</p>
            </div>
            <!-- items -->
            <table class="w-full border-collapse">
              <thead>
                <tr :style="{ background: form.primary_color }" class="text-white">
                  <th class="text-left px-2.5 py-2">Product</th>
                  <th class="text-right px-2.5 py-2">Qty</th>
                  <th class="text-right px-2.5 py-2">Price</th>
                  <th class="text-right px-2.5 py-2">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(it, i) in sampleItems" :key="i" class="border-b border-gray-100">
                  <td class="px-2.5 py-2">{{ it.name }}</td>
                  <td class="px-2.5 py-2 text-right">{{ it.qty }}</td>
                  <td class="px-2.5 py-2 text-right">৳{{ it.price.toLocaleString() }}</td>
                  <td class="px-2.5 py-2 text-right">৳{{ (it.qty * it.price).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
            <!-- totals -->
            <div class="flex justify-end mt-3">
              <table class="w-1/2">
                <tr><td class="text-muted px-2 py-1">Subtotal</td><td class="text-right px-2 py-1">৳{{ subtotal.toLocaleString() }}</td></tr>
                <tr><td class="text-muted px-2 py-1">Delivery</td><td class="text-right px-2 py-1">৳80</td></tr>
                <tr class="font-bold" :style="{ color: form.primary_color }"><td class="px-2 py-1 border-t-2" :style="{ borderColor: form.primary_color }">Grand Total</td><td class="px-2 py-1 text-right border-t-2" :style="{ borderColor: form.primary_color }">৳{{ (subtotal + 80).toLocaleString() }}</td></tr>
              </table>
            </div>
            <p v-if="form.notes" class="mt-3 text-muted"><span class="font-semibold" :style="{ color: form.primary_color }">Notes:</span> {{ form.notes }}</p>
            <p v-if="form.terms" class="mt-1 text-muted"><span class="font-semibold" :style="{ color: form.primary_color }">Terms:</span> {{ form.terms }}</p>
            <!-- footer -->
            <div class="mt-6 pt-3 text-center" :style="{ borderTop: `2px solid ${form.secondary_color}` }">
              <p class="font-semibold" :style="{ color: form.primary_color }">{{ form.footer_message || 'Thank you for shopping with us!' }}</p>
              <p class="text-muted">{{ form.company_name }} · {{ form.company_website }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <MediaPicker :open="picker" @close="picker = false" @select="onLogo" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import Button from '@/Components/ui/Button.vue'
import ColorField from '@/Components/ui/ColorField.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import { PhotoIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ settings: { type: Object, default: () => ({}) } })

const form = useForm({
  company_logo: '', company_name: '', company_address: '', company_phone: '',
  company_email: '', company_website: '', invoice_title: 'INVOICE', header_text: '',
  footer_message: '', notes: '', terms: '', primary_color: '#17181B', secondary_color: '#ED1C24',
  ...props.settings,
})

const picker = ref(false)
const onLogo = (media) => { if (media?.file_url) form.company_logo = media.file_url; picker.value = false }

const today = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
const sampleItems = [
  { name: 'Kizora Voyager 25L Backpack', qty: 1, price: 1990 },
  { name: 'Kizora Bottle Carry Sleeve', qty: 2, price: 390 },
]
const subtotal = computed(() => sampleItems.reduce((s, i) => s + i.qty * i.price, 0))

const save = () => form.post(route('admin.invoice-settings.update'), { preserveScroll: true })
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
</style>
