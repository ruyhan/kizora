<template>
  <AdminLayout>
    <Head title="Email · Branding" />

    <div class="space-y-6 w-full max-w-6xl">
      <SettingsTabs />
      <EmailTabs />

      <div>
        <h1 class="text-2xl font-bold font-display text-charcoal">Brand Settings</h1>
        <p class="text-muted text-sm mt-1">Logo, banner and company details shared across every email your store sends.</p>
      </div>

      <div class="grid lg:grid-cols-2 gap-6 items-start">
        <!-- Form -->
        <form @submit.prevent="save" class="space-y-6">
          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-5">
            <h3 class="font-display font-semibold text-charcoal">Logo &amp; banner</h3>

            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Company logo</label>
                <div class="flex items-center gap-3">
                  <div class="w-20 h-14 rounded-xl border border-purple-100 bg-ink flex items-center justify-center overflow-hidden shrink-0 p-2">
                    <img v-if="form.logo" :src="form.logo" class="max-w-full max-h-full object-contain" alt="">
                    <PhotoIcon v-else class="w-6 h-6 text-white/40" />
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" @click="pick('logo')" class="btn-secondary text-sm px-3 py-2">{{ form.logo ? 'Change' : 'Choose' }}</button>
                    <button v-if="form.logo" type="button" @click="form.logo = ''" class="text-error text-sm hover:underline">Remove</button>
                  </div>
                </div>
                <p class="text-xs text-muted mt-1.5">Shown as-is on the dark email header — upload a light/white version of your logo. No colour filter is applied.</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Header banner (optional)</label>
                <div class="flex items-center gap-3">
                  <div class="w-20 h-14 rounded-xl border border-purple-100 bg-purple-50/40 flex items-center justify-center overflow-hidden shrink-0">
                    <img v-if="form.banner" :src="form.banner" class="w-full h-full object-cover" alt="">
                    <PhotoIcon v-else class="w-6 h-6 text-muted/40" />
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" @click="pick('banner')" class="btn-secondary text-sm px-3 py-2">{{ form.banner ? 'Change' : 'Choose' }}</button>
                    <button v-if="form.banner" type="button" @click="form.banner = ''" class="text-error text-sm hover:underline">Remove</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-4">
            <h3 class="font-display font-semibold text-charcoal">Company details</h3>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Company name</label>
                <input v-model="form.company_name" type="text" class="input-field">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Phone</label>
                <input v-model="form.phone" type="text" class="input-field">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Website URL</label>
                <input v-model="form.website" type="text" class="input-field" placeholder="https://yourstore.com">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Support email</label>
                <input v-model="form.support_email" type="email" class="input-field">
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Company address</label>
              <textarea v-model="form.address" rows="2" class="input-field"></textarea>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-4">
            <h3 class="font-display font-semibold text-charcoal">Social links</h3>
            <div class="grid sm:grid-cols-2 gap-4">
              <div v-for="p in socialPlatforms" :key="p">
                <label class="text-sm font-medium text-charcoal mb-1.5 block capitalize">{{ p }}</label>
                <input v-model="form.socials[p]" type="text" class="input-field" :placeholder="`https://${p}.com/...`">
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-6 space-y-4">
            <h3 class="font-display font-semibold text-charcoal">Footer</h3>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Footer disclaimer</label>
              <textarea v-model="form.footer_disclaimer" rows="2" class="input-field"></textarea>
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Copyright text</label>
              <input v-model="form.footer_copyright" type="text" class="input-field">
            </div>
          </div>

          <div class="flex items-center justify-end">
            <button type="submit" :disabled="saving" class="btn-primary flex items-center gap-2 text-sm px-6">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
              {{ saving ? 'Saving…' : 'Save branding' }}
            </button>
          </div>
        </form>

        <!-- Live preview -->
        <div class="lg:sticky lg:top-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-display font-semibold text-charcoal text-sm">Live preview</h3>
            <span class="text-xs text-muted">Updates as you type</span>
          </div>
          <div class="rounded-2xl border border-purple-100 overflow-hidden bg-[#f4f4f5]">
            <iframe ref="frame" class="w-full" style="height:640px;border:0" title="Email preview"></iframe>
          </div>
        </div>
      </div>
    </div>

    <MediaPicker :open="picker.open" @close="picker.open = false" @select="onPicked" />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import EmailTabs from '@/Components/admin/EmailTabs.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import { PhotoIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ brand: Object })

const socialPlatforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok']
const form = reactive({
  logo: props.brand.logo || '',
  banner: props.brand.banner || '',
  company_name: props.brand.company_name || '',
  phone: props.brand.phone || '',
  website: props.brand.website || '',
  support_email: props.brand.support_email || '',
  address: props.brand.address || '',
  socials: { ...Object.fromEntries(socialPlatforms.map(p => [p, ''])), ...(props.brand.socials || {}) },
  footer_disclaimer: props.brand.footer_disclaimer || '',
  footer_copyright: props.brand.footer_copyright || '',
})

const saving = ref(false)
const frame = ref(null)
const picker = reactive({ open: false, field: null })

const pick = (field) => { picker.field = field; picker.open = true }
const onPicked = (item) => {
  if (picker.field && item?.file_url) form[picker.field] = item.file_url
  picker.open = false
}

let timer = null
const refreshPreview = () => {
  clearTimeout(timer)
  timer = setTimeout(async () => {
    try {
      const { data } = await axios.post(route('admin.email.brand.preview'), { ...form })
      if (frame.value) frame.value.srcdoc = data.html
    } catch { /* ignore preview errors */ }
  }, 350)
}

watch(form, refreshPreview, { deep: true })
onMounted(refreshPreview)

const save = () => {
  saving.value = true
  router.post(route('admin.email.brand.update'), { ...form }, {
    preserveScroll: true,
    onFinish: () => { saving.value = false },
  })
}
</script>
