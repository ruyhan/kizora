<template>
  <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">

    <!-- Card Header -->
    <div class="p-5 border-b border-purple-50 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <!-- Colored gateway badge -->
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
          :style="{ backgroundColor: color + '18', color }">
          <span class="font-bold text-[11px] leading-none">{{ title[0] }}</span>
        </div>
        <div>
          <h3 class="font-semibold text-charcoal">{{ title }}</h3>
          <p class="text-xs text-muted">{{ subtitle }}</p>
        </div>
      </div>

      <!-- Status badge -->
      <span :class="form.enabled === '1' ? 'badge-success' : 'badge-error'" class="badge text-xs shrink-0">
        {{ form.enabled === '1' ? 'Enabled' : 'Disabled' }}
      </span>
    </div>

    <!-- Card Body -->
    <form @submit.prevent="save" class="p-5 space-y-4">

      <!-- Enable / Mode row -->
      <div class="grid sm:grid-cols-2 gap-4 p-4 bg-purple-50/50 rounded-xl">
        <div>
          <label class="text-sm font-medium text-charcoal mb-2 block">Gateway Status</label>
          <div class="flex gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.enabled" value="1" class="text-primary">
              <span class="text-sm text-charcoal">Enabled</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.enabled" value="0" class="text-primary">
              <span class="text-sm text-charcoal">Disabled</span>
            </label>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium text-charcoal mb-2 block">Mode</label>
          <div class="flex gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.mode" value="sandbox" class="text-primary">
              <span class="text-sm text-charcoal">Sandbox</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.mode" value="live" class="text-warning">
              <span class="text-sm font-medium" :class="form.mode === 'live' ? 'text-warning' : 'text-charcoal'">Live</span>
            </label>
          </div>
          <p v-if="form.mode === 'live'" class="text-xs text-warning mt-1 flex items-center gap-1">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
            Live mode — real transactions will be processed
          </p>
        </div>
      </div>

      <!-- Gateway-specific credential fields via slot -->
      <slot name="fields" :form="form" />

      <!-- Save button -->
      <div class="flex items-center justify-between pt-2">
        <p v-if="saveError" class="text-error text-sm">{{ saveError }}</p>
        <button type="submit" :disabled="saving"
          class="btn-primary ml-auto flex items-center gap-2 text-sm px-6">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Saving…' : 'Save Settings' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  gateway:  { type: String, required: true },
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
  color:    { type: String, default: '#17181B' },
  initial:  { type: Object, default: () => ({}) },
})
const emit = defineEmits(['saved'])

const saving   = ref(false)
const saveError = ref('')

// Build a local reactive form from the initial props
const form = reactive({
  enabled: props.initial.enabled || '0',
  mode:    props.initial.mode    || 'sandbox',
  ...Object.fromEntries(
    Object.entries(props.initial).filter(([k]) => k !== 'enabled' && k !== 'mode')
  ),
})

// Re-sync if parent updates (e.g., after save)
watch(() => props.initial, (val) => {
  Object.assign(form, val)
}, { deep: true })

const save = () => {
  saving.value   = true
  saveError.value = ''

  router.post(route('admin.payments.update', props.gateway), form, {
    preserveScroll: true,
    onSuccess:  () => { emit('saved', props.title); saving.value = false },
    onError: (errors) => {
      saveError.value = Object.values(errors)[0] ?? 'Save failed.'
      saving.value = false
    },
    onFinish: () => { saving.value = false },
  })
}
</script>
