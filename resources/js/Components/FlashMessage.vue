<template>
  <div></div>
</template>

<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'

const page = usePage()
const toast = useToast()

watch(() => page.props.flash, (flash) => {
  if (flash?.success) toast.success(flash.success)
  if (flash?.error) toast.error(flash.error)
}, { immediate: true, deep: true })
</script>
