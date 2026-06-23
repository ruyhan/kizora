<template>
  <div class="flex flex-wrap items-center gap-2">
    <a v-if="can('invoice.view')" :href="route('admin.invoices.show', orderId)" target="_blank" rel="noopener" class="act">
      <EyeIcon class="w-3.5 h-3.5" /> View
    </a>
    <a v-if="can('invoice.download')" :href="route('admin.invoices.download', orderId)" class="act">
      <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Download
    </a>
    <a v-if="can('invoice.print')" :href="route('admin.invoices.print', orderId)" target="_blank" rel="noopener" class="act">
      <PrinterIcon class="w-3.5 h-3.5" /> Print
    </a>
    <button v-if="showRegenerate && can('invoice.regenerate')" type="button" @click="regenerate" class="act text-error">
      <ArrowPathIcon class="w-3.5 h-3.5" /> Regenerate
    </button>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { usePermissions } from '@/composables/usePermissions'
import { EyeIcon, ArrowDownTrayIcon, PrinterIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  orderId: { type: [Number, String], required: true },
  showRegenerate: { type: Boolean, default: true },
})
const { can } = usePermissions()

const regenerate = () => {
  if (confirm('Regenerate this invoice with a new number?')) {
    router.post(route('admin.invoices.regenerate', props.orderId), {}, { preserveScroll: true })
  }
}
</script>

<style scoped>
@reference "../../../css/app.css";
.act { @apply inline-flex items-center gap-1 text-xs text-primary px-2.5 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors; }
</style>
