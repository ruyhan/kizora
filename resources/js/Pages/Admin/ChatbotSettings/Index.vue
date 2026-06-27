<template>
  <AdminLayout>
    <Head title="Chatbot Settings" />
    <SettingsTabs />

    <form @submit.prevent="save" class="max-w-3xl space-y-5 pb-24">
      <!-- AI Provider -->
      <section class="bg-white rounded-2xl border border-purple-50 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-display font-semibold text-charcoal">AI provider</h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <span class="text-sm text-muted">{{ form.enabled ? 'Enabled' : 'Disabled' }}</span>
            <button type="button" @click="form.enabled = !form.enabled" :class="form.enabled ? 'bg-ink' : 'bg-gray-300'" class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors">
              <span :class="form.enabled ? 'translate-x-[22px]' : 'translate-x-[3px]'" class="inline-block w-[18px] h-[18px] bg-white rounded-full shadow transition-transform"></span>
            </button>
          </label>
        </div>
        <div>
          <label class="lbl">Provider</label>
          <div class="inline-flex p-1 rounded-xl bg-[#F1F1F2]">
            <button v-for="p in ['groq','openrouter']" :key="p" type="button" @click="form.provider = p"
              :class="form.provider === p ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="px-4 py-2 rounded-lg text-sm font-semibold capitalize">{{ p }}</button>
          </div>
        </div>
        <!-- Key field follows the selected provider -->
        <div v-if="form.provider === 'groq'">
          <label class="lbl">Groq API key</label>
          <input v-model="form.groq_api_key" type="password" class="input-field" :placeholder="hasGroqKey ? '•••••••• (saved — leave blank to keep)' : 'gsk_…'">
        </div>
        <div v-else>
          <label class="lbl">OpenRouter API key</label>
          <input v-model="form.openrouter_api_key" type="password" class="input-field" :placeholder="hasOpenrouterKey ? '•••••••• (saved — leave blank to keep)' : 'sk-or-…'">
        </div>
        <div>
          <label class="lbl">Model</label>
          <SelectMenu v-model="modelChoice" :options="modelSelectOptions" />
          <input v-if="modelChoice === '__custom__'" v-model="form.model" class="input-field mt-2" placeholder="Enter model id (e.g. openai/gpt-4o)">
          <p class="text-xs text-muted mt-1">Models shown are for the selected provider. Choose “Custom…” to enter any supported model id.</p>
        </div>

        <!-- Test connection -->
        <div class="pt-3 border-t border-purple-50">
          <div class="flex items-center gap-3 flex-wrap">
            <button type="button" @click="testConnection" :disabled="test.loading"
              class="btn-secondary text-sm px-4 flex items-center gap-2 disabled:opacity-60">
              <span v-if="test.loading" class="w-4 h-4 border-2 border-ink/40 border-t-ink rounded-full animate-spin"></span>
              <BoltIcon v-else class="w-4 h-4" />
              {{ test.loading ? 'Testing…' : 'Test connection' }}
            </button>
            <span v-if="test.result" class="inline-flex items-center gap-1.5 text-sm font-semibold"
              :class="test.result.ok ? 'text-success' : 'text-error'">
              <CheckCircleIcon v-if="test.result.ok" class="w-4 h-4" />
              <ExclamationTriangleIcon v-else class="w-4 h-4" />
              {{ test.result.ok ? 'Connected' : 'Not connected' }}
            </span>
          </div>
          <p v-if="test.result" class="text-xs mt-2 leading-relaxed" :class="test.result.ok ? 'text-muted' : 'text-error/90'">
            {{ test.result.message }}
            <span v-if="test.result.ok && test.result.reply"> · model replied: “{{ test.result.reply }}”</span>
          </p>
          <p class="text-[11px] text-muted mt-1">Tests the selected provider using the key above, or the saved key if left blank.</p>
        </div>
      </section>

      <!-- Identity -->
      <section class="bg-white rounded-2xl border border-purple-50 p-5 space-y-4">
        <h3 class="font-display font-semibold text-charcoal">Identity</h3>
        <div><label class="lbl">Assistant name</label><input v-model="form.name" class="input-field"></div>
        <div><label class="lbl">Welcome message</label><textarea v-model="form.welcome_message" rows="2" class="input-field"></textarea></div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="lbl">Avatar</label>
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-full border border-purple-100 bg-purple-50/40 overflow-hidden flex items-center justify-center shrink-0">
                <img v-if="form.avatar" :src="form.avatar" class="w-full h-full object-cover" alt=""><PhotoIcon v-else class="w-5 h-5 text-muted/40" />
              </div>
              <button type="button" @click="pick('avatar')" class="btn-secondary text-xs px-3 py-1.5">Choose</button>
              <button v-if="form.avatar" type="button" @click="form.avatar = ''" class="text-error text-xs hover:underline">Remove</button>
            </div>
          </div>
          <div>
            <label class="lbl">Widget icon</label>
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-xl border border-purple-100 bg-purple-50/40 overflow-hidden flex items-center justify-center shrink-0">
                <img v-if="form.widget_icon" :src="form.widget_icon" class="w-full h-full object-contain" alt=""><PhotoIcon v-else class="w-5 h-5 text-muted/40" />
              </div>
              <button type="button" @click="pick('widget_icon')" class="btn-secondary text-xs px-3 py-1.5">Choose</button>
              <button v-if="form.widget_icon" type="button" @click="form.widget_icon = ''" class="text-error text-xs hover:underline">Remove</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Design -->
      <section class="bg-white rounded-2xl border border-purple-50 p-5 space-y-4">
        <h3 class="font-display font-semibold text-charcoal">Widget design</h3>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="lbl">Primary colour</label><ColorField v-model="form.primary_color" /></div>
          <div class="space-y-3">
            <div>
              <label class="lbl">Position</label>
              <div class="inline-flex p-1 rounded-xl bg-[#F1F1F2]">
                <button v-for="p in ['bottom-right','bottom-left']" :key="p" type="button" @click="form.position = p" :class="form.position === p ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">{{ p }}</button>
              </div>
            </div>
            <div>
              <label class="lbl">Theme</label>
              <div class="inline-flex p-1 rounded-xl bg-[#F1F1F2]">
                <button v-for="t in ['light','dark']" :key="t" type="button" @click="form.theme = t" :class="form.theme === t ? 'bg-white shadow-sm text-ink' : 'text-muted'" class="px-3 py-1.5 rounded-lg text-xs font-semibold capitalize">{{ t }}</button>
              </div>
            </div>
            <label class="flex items-center gap-2.5 cursor-pointer">
              <input type="checkbox" v-model="form.rounded" class="rounded w-4 h-4">
              <span class="text-sm text-charcoal">Rounded / modern UI</span>
            </label>
          </div>
        </div>
      </section>

      <div class="fixed bottom-0 inset-x-0 md:left-64 bg-white border-t border-purple-100 px-6 py-3 flex items-center justify-end gap-3 z-20">
        <span v-if="form.recentlySuccessful" class="text-success text-sm font-medium">Saved ✓</span>
        <Button type="submit" :loading="form.processing">Save settings</Button>
      </div>
    </form>

    <MediaPicker :open="picker.open" @close="picker.open = false" @select="onPick" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import Button from '@/Components/ui/Button.vue'
import ColorField from '@/Components/ui/ColorField.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import { PhotoIcon, BoltIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ settings: Object, hasGroqKey: Boolean, hasOpenrouterKey: Boolean })

const form = useForm({
  enabled: true, provider: 'groq', groq_api_key: '', openrouter_api_key: '', model: 'llama-3.3-70b-versatile',
  name: 'Kizora Assistant', welcome_message: '', avatar: '', widget_icon: '',
  primary_color: '#17181B', position: 'bottom-right', theme: 'light', rounded: true,
  ...props.settings,
})
// keys are never sent back from the server — keep blank
form.groq_api_key = ''
form.openrouter_api_key = ''

// Provider-aware model dropdown
const MODELS = {
  groq: ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'meta-llama/llama-4-scout-17b-16e-instruct', 'gemma2-9b-it'],
  openrouter: ['openai/gpt-4o-mini', 'openai/gpt-4o', 'anthropic/claude-3.5-sonnet', 'google/gemini-flash-1.5', 'meta-llama/llama-3.3-70b-instruct'],
}
const modelOptions = computed(() => MODELS[form.provider] || [])
const modelSelectOptions = computed(() => [...modelOptions.value.map(m => ({ value: m, label: m })), { value: '__custom__', label: 'Custom…' }])
const modelChoice = ref(modelOptions.value.includes(form.model) ? form.model : '__custom__')
watch(() => form.provider, (p) => { form.model = MODELS[p][0]; modelChoice.value = MODELS[p][0] })
watch(modelChoice, (v) => { if (v !== '__custom__') form.model = v })

const picker = ref({ open: false, field: null })
const pick = (field) => { picker.value = { open: true, field } }
const onPick = (media) => { if (media?.file_url && picker.value.field) form[picker.value.field] = media.file_url; picker.value.open = false }

const save = () => form.post(route('admin.chatbot-settings.update'), { preserveScroll: true })

// Test connection — pings the provider with the typed (or saved) key.
const test = ref({ loading: false, result: null })
const testConnection = async () => {
  test.value = { loading: true, result: null }
  try {
    const { data } = await axios.post(route('admin.chatbot-settings.test'), {
      provider: form.provider,
      model: form.model,
      groq_api_key: form.groq_api_key,
      openrouter_api_key: form.openrouter_api_key,
    })
    test.value = { loading: false, result: data }
  } catch (e) {
    test.value = { loading: false, result: { ok: false, message: e?.response?.data?.message || 'Request failed.' } }
  }
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.lbl { @apply block text-sm font-medium text-charcoal mb-1.5; }
</style>
