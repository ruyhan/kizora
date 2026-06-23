<template>
  <div class="kz-phone">
    <input ref="input" type="tel" :class="inputClass" autocomplete="tel" :placeholder="placeholder">
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import intlTelInput from 'intl-tel-input/intlTelInputWithUtils'
import 'intl-tel-input/styles'

const props = defineProps({
  modelValue: { type: String, default: '' },
  inputClass: { type: String, default: 'input-field' },
  placeholder: { type: String, default: 'Phone number' },
})
const emit = defineEmits(['update:modelValue'])

const input = ref(null)
let iti = null

const currentNumber = () => {
  if (!iti) return ''
  const e164 = iti.getNumber()
  if (e164) return e164
  const c = iti.getSelectedCountryData()
  const digits = (input.value?.value || '').replace(/\D/g, '')
  return digits ? '+' + (c.dialCode || '') + digits : ''
}
const emitVal = () => emit('update:modelValue', currentNumber())

onMounted(() => {
  iti = intlTelInput(input.value, {
    initialCountry: 'bd',          // default: Bangladesh
    countrySearch: true,
    nationalMode: false,
  })
  if (props.modelValue) iti.setNumber(props.modelValue)
  input.value.addEventListener('input', emitVal)
  input.value.addEventListener('countrychange', emitVal)
})

onBeforeUnmount(() => { iti?.destroy() })

watch(() => props.modelValue, (v) => {
  if (iti && v && v !== iti.getNumber()) iti.setNumber(v)
})
</script>

<style>
/* intl-tel-input renders a wrapper around the input — make it full width */
.kz-phone .iti { width: 100%; display: block; }
.kz-phone .iti__tel-input { width: 100%; }
</style>
