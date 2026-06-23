<template>
  <Teleport to="body">
    <Transition name="kz-fade">
      <div v-if="open" class="fixed inset-0 z-[110] bg-ink/55 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="close" aria-hidden="true">
        <Transition name="kz-pop" appear>
          <div v-if="open" ref="panel"
            class="bg-surface w-full shadow-2xl outline-none rounded-t-2xl sm:rounded-2xl max-h-[92vh] sm:max-h-[88vh] overflow-hidden flex flex-col"
            :class="maxWidth"
            role="dialog" aria-modal="true" :aria-label="title || 'Dialog'" tabindex="-1"
            @keydown.esc="close">
            <div v-if="title || $slots.header" class="flex items-center justify-between gap-3 px-5 py-4 border-b border-border shrink-0">
              <slot name="header"><h2 class="font-display font-semibold text-ink text-lg">{{ title }}</h2></slot>
              <button @click="close" aria-label="Close"
                class="w-9 h-9 rounded-xl bg-[#F1F1F2] hover:bg-[#E7E7EA] flex items-center justify-center text-muted hover:text-ink transition-colors">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
            <div class="flex-1 overflow-y-auto overscroll-contain"><slot /></div>
            <div v-if="$slots.footer" class="border-t border-border shrink-0"><slot name="footer" /></div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onUnmounted, nextTick } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  open:     { type: Boolean, default: false },
  title:    { type: String, default: '' },
  maxWidth: { type: String, default: 'sm:max-w-2xl' },
})
const emit = defineEmits(['update:open'])
const panel = ref(null)
const close = () => emit('update:open', false)

watch(() => props.open, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
  if (v) nextTick(() => panel.value?.focus())
})
onUnmounted(() => { document.body.style.overflow = '' })
</script>

<style scoped>
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity 0.22s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
.kz-pop-enter-active { transition: transform 0.26s cubic-bezier(0.16,1,0.3,1), opacity 0.26s ease; }
.kz-pop-leave-active { transition: transform 0.16s ease, opacity 0.16s ease; }
.kz-pop-enter-from, .kz-pop-leave-to { opacity: 0; transform: translateY(12px) scale(0.98); }
@media (prefers-reduced-motion: reduce) {
  .kz-pop-enter-active, .kz-pop-leave-active { transition: opacity 0.2s ease; }
  .kz-pop-enter-from, .kz-pop-leave-to { transform: none; }
}
</style>
