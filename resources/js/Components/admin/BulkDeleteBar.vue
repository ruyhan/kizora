<template>
  <Teleport to="body">
    <Transition name="kz-bar">
      <div v-if="count > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[80] flex items-center gap-3 bg-ink text-white rounded-2xl shadow-2xl pl-5 pr-3 py-2.5">
        <span class="text-sm font-semibold whitespace-nowrap">{{ count }} selected</span>
        <button @click="$emit('clear')" class="text-xs text-gray-300 hover:text-white">Clear</button>
        <div class="w-px h-5 bg-white/20"></div>
        <template v-if="!confirming">
          <button @click="confirming = true" class="text-sm font-semibold px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 inline-flex items-center gap-1.5 transition-colors">
            <TrashIcon class="w-4 h-4" /> Delete selected
          </button>
        </template>
        <template v-else>
          <span class="text-xs text-gray-200 whitespace-nowrap">Delete {{ count }} permanently?</span>
          <button @click="$emit('confirm')" :disabled="busy" class="text-sm font-semibold px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 transition-colors">{{ busy ? 'Deleting…' : 'Yes, delete' }}</button>
          <button @click="confirming = false" class="text-sm px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition-colors">Cancel</button>
        </template>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ count: { type: Number, default: 0 }, busy: { type: Boolean, default: false } })
defineEmits(['confirm', 'clear'])

const confirming = ref(false)
watch(() => props.count, (c) => { if (c === 0) confirming.value = false })
</script>

<style scoped>
.kz-bar-enter-active, .kz-bar-leave-active { transition: opacity .2s ease; }
.kz-bar-enter-from, .kz-bar-leave-to { opacity: 0; }
</style>
