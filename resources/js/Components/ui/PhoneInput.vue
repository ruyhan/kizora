<template>
  <div class="kz-phone">
    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted pointer-events-none select-none">🇧🇩 +880</span>
      <input ref="input" type="tel" inputmode="numeric" autocomplete="tel" maxlength="11"
        :class="[inputClass, showError ? 'border-error' : '']" style="padding-left: 5.25rem"
        :placeholder="placeholder" :value="local" @input="onInput" @blur="touched = true">
    </div>
    <p v-if="showError" class="text-error text-xs mt-1">{{ message }}</p>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { normalizeBdPhone, toLocalBdPhone, BD_PHONE_MESSAGE } from '@/utils/phone'

const props = defineProps({
  modelValue:  { type: String, default: '' },
  inputClass:  { type: String, default: 'input-field' },
  placeholder: { type: String, default: '01712345678' },
  // The parent already shows a server-side error for this field
  hideError:   { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const message = BD_PHONE_MESSAGE
const local = ref(toLocalBdPhone(props.modelValue))
const touched = ref(false)

// Only flagged once the field has been left, so typing isn't nagged at.
const showError = computed(() => !props.hideError && touched.value && local.value.length > 0 && !normalizeBdPhone(local.value))

const onInput = (e) => {
  local.value = e.target.value.replace(/\D/g, '').slice(0, 11)
  e.target.value = local.value
  // Hand the parent the stored format once it's valid, the raw digits until then.
  emit('update:modelValue', normalizeBdPhone(local.value) || local.value)
}

watch(() => props.modelValue, (v) => {
  const next = toLocalBdPhone(v)
  if (next !== local.value && normalizeBdPhone(v)) local.value = next
})
</script>
