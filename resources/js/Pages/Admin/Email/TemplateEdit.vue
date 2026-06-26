<template>
  <AdminLayout>
    <Head :title="`Email · ${form.name}`" />

    <div class="space-y-6 w-full max-w-6xl">
      <SettingsTabs />
      <EmailTabs />

      <div class="flex items-center gap-3">
        <Link :href="route('admin.email.templates')" class="w-9 h-9 rounded-xl bg-white border border-purple-100 flex items-center justify-center text-muted hover:text-ink">
          <ArrowLeftIcon class="w-4 h-4" />
        </Link>
        <div class="flex-1 min-w-0">
          <h1 class="text-xl font-semibold text-charcoal font-display truncate">{{ form.name }}</h1>
          <p class="text-muted text-xs">Edit the subject, design and variables for this email.</p>
        </div>
        <button type="button" role="switch" :aria-checked="form.is_enabled" @click="form.is_enabled = !form.is_enabled"
          :class="form.is_enabled ? 'bg-ink' : 'bg-gray-300'"
          class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors shrink-0" title="Enable/disable">
          <span :class="form.is_enabled ? 'translate-x-[22px]' : 'translate-x-[3px]'"
            class="inline-block w-[18px] h-[18px] bg-white rounded-full shadow transition-transform"></span>
        </button>
      </div>

      <div class="grid lg:grid-cols-2 gap-6 items-start">
        <!-- Editor -->
        <div class="space-y-5">
          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-5 space-y-4">
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Template name</label>
              <input v-model="form.name" type="text" class="input-field">
            </div>
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Subject line</label>
              <input ref="subjectEl" v-model="form.subject" type="text" class="input-field" @focus="activeField = 'subject'">
              <p v-if="errors.subject" class="text-error text-xs mt-1">{{ errors.subject }}</p>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium text-charcoal">Email body (HTML)</label>
              <span class="text-[11px] text-muted">Click a variable to insert it</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <button v-for="v in variables" :key="v" type="button" @click="insertVar(v)"
                class="text-[11px] font-mono px-2 py-1 rounded-md border border-purple-100 bg-purple-50/50 text-ink hover:bg-purple-100 transition-colors">
                {{ '{' + v + '}' }}
              </button>
            </div>
            <textarea ref="bodyEl" v-model="form.body_html" rows="20" spellcheck="false"
              @focus="activeField = 'body'"
              class="input-field font-mono text-xs leading-relaxed" style="resize:vertical"></textarea>
            <p v-if="errors.body_html" class="text-error text-xs">{{ errors.body_html }}</p>
          </div>

          <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-5 space-y-3">
            <h3 class="font-display font-semibold text-charcoal text-sm">Send a test</h3>
            <div class="flex gap-2">
              <input v-model="testTo" type="email" class="input-field flex-1" placeholder="you@example.com">
              <button type="button" @click="sendTest" :disabled="busy.test || !testTo" class="btn-secondary text-sm px-4 flex items-center gap-2 shrink-0">
                <span v-if="busy.test" class="w-4 h-4 border-2 border-ink/40 border-t-ink rounded-full animate-spin"></span>
                <PaperAirplaneIcon v-else class="w-4 h-4" /> Send test
              </button>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="save" :disabled="busy.save" class="btn-primary flex items-center gap-2 text-sm px-6">
              <span v-if="busy.save" class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
              {{ busy.save ? 'Saving…' : 'Save template' }}
            </button>
            <button type="button" @click="duplicate" class="btn-secondary text-sm px-4 flex items-center gap-1.5">
              <DocumentDuplicateIcon class="w-4 h-4" /> Duplicate
            </button>
            <button v-if="!template.is_custom" type="button" @click="reset" class="text-sm text-muted hover:text-ink px-3 flex items-center gap-1.5">
              <ArrowPathIcon class="w-4 h-4" /> Reset to default
            </button>
          </div>
        </div>

        <!-- Preview -->
        <div class="lg:sticky lg:top-6 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-display font-semibold text-charcoal text-sm">Live preview</h3>
            <div class="inline-flex rounded-lg border border-purple-100 overflow-hidden">
              <button type="button" @click="device = 'desktop'" :class="device === 'desktop' ? 'bg-ink text-white' : 'text-muted hover:text-ink'" class="px-3 py-1.5 text-xs font-semibold flex items-center gap-1.5">
                <ComputerDesktopIcon class="w-4 h-4" /> Desktop
              </button>
              <button type="button" @click="device = 'mobile'" :class="device === 'mobile' ? 'bg-ink text-white' : 'text-muted hover:text-ink'" class="px-3 py-1.5 text-xs font-semibold flex items-center gap-1.5">
                <DevicePhoneMobileIcon class="w-4 h-4" /> Mobile
              </button>
            </div>
          </div>
          <div class="rounded-2xl border border-purple-100 overflow-hidden bg-[#f4f4f5] flex justify-center p-3">
            <iframe ref="frame" :style="{ width: device === 'mobile' ? '380px' : '100%', height: '680px', border: 0 }"
              class="bg-white rounded-lg transition-all" title="Email preview"></iframe>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, watch, onMounted, nextTick } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import EmailTabs from '@/Components/admin/EmailTabs.vue'
import {
  ArrowLeftIcon, ArrowPathIcon, PaperAirplaneIcon, DocumentDuplicateIcon,
  ComputerDesktopIcon, DevicePhoneMobileIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ template: Object, variables: Array })

const form = reactive({
  name: props.template.name,
  subject: props.template.subject,
  body_html: props.template.body_html,
  is_enabled: props.template.is_enabled,
})
const errors = ref({})
const busy = reactive({ save: false, test: false })
const testTo = ref('')
const device = ref('desktop')
const frame = ref(null)
const bodyEl = ref(null)
const subjectEl = ref(null)
const activeField = ref('body')

const insertVar = (v) => {
  const token = `{${v}}`
  const el = activeField.value === 'subject' ? subjectEl.value : bodyEl.value
  const key = activeField.value === 'subject' ? 'subject' : 'body_html'
  if (!el) { form[key] += token; return }
  const start = el.selectionStart ?? form[key].length
  const end = el.selectionEnd ?? start
  form[key] = form[key].slice(0, start) + token + form[key].slice(end)
  nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = start + token.length })
}

let timer = null
const refreshPreview = () => {
  clearTimeout(timer)
  timer = setTimeout(async () => {
    try {
      const { data } = await axios.post(route('admin.email.templates.preview', props.template.id), {
        subject: form.subject, body_html: form.body_html,
      })
      if (frame.value) frame.value.srcdoc = data.html
    } catch { /* ignore */ }
  }, 350)
}
watch(() => [form.subject, form.body_html], refreshPreview)
onMounted(refreshPreview)

const save = () => {
  busy.save = true
  errors.value = {}
  router.post(route('admin.email.templates.update', props.template.id), { ...form }, {
    preserveScroll: true,
    onError: (e) => { errors.value = e },
    onFinish: () => { busy.save = false },
  })
}
const sendTest = () => {
  busy.test = true
  router.post(route('admin.email.templates.test', props.template.id), { to: testTo.value }, {
    preserveScroll: true,
    onFinish: () => { busy.test = false },
  })
}
const duplicate = () => router.post(route('admin.email.templates.duplicate', props.template.id), {}, { preserveScroll: true })
const reset = () => {
  if (!confirm('Reset this template to its default design? Your changes will be lost.')) return
  router.post(route('admin.email.templates.reset', props.template.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload(),
  })
}
</script>
