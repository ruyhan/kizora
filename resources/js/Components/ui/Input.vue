<template>
  <div>
    <label v-if="label" :for="id" class="text-sm font-medium text-charcoal mb-1.5 block">
      {{ label }} <span v-if="required" class="text-accent">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :class="['input-field', error ? 'border-error! ring-2 ring-error/15' : '']"
      :aria-invalid="error ? 'true' : undefined"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="text-error text-xs mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label:       { type: String, default: '' },
  type:        { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  error:       { type: String, default: '' },
  required:    { type: Boolean, default: false },
  disabled:    { type: Boolean, default: false },
  id:          { type: String, default: () => `in-${Math.random().toString(36).slice(2, 9)}` },
})
defineEmits(['update:modelValue'])
</script>
