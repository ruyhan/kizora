<template>
  <Teleport to="body">
    <Transition name="kz-fade">
      <div v-if="open" class="fixed inset-0 z-[100] bg-ink/50 backdrop-blur-sm" @click="close" aria-hidden="true"></div>
    </Transition>
    <Transition :name="transitionName">
      <div
        v-if="open"
        ref="panel"
        class="fixed z-[101] bg-surface flex flex-col shadow-2xl outline-none"
        :class="panelClass"
        role="dialog"
        aria-modal="true"
        :aria-label="title || 'Panel'"
        tabindex="-1"
        @keydown.esc="close"
      >
        <div v-if="title || $slots.header" class="flex items-center justify-between gap-3 px-5 py-4 border-b border-border shrink-0">
          <slot name="header">
            <h2 class="font-display font-semibold text-ink text-lg">{{ title }}</h2>
          </slot>
          <button @click="close" aria-label="Close"
            class="w-9 h-9 rounded-xl bg-[#F1F1F2] hover:bg-[#E7E7EA] flex items-center justify-center text-muted hover:text-ink transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <div class="flex-1 overflow-y-auto overscroll-contain"><slot /></div>
        <div v-if="$slots.footer" class="border-t border-border shrink-0"><slot name="footer" /></div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed, onUnmounted, nextTick } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  open:  { type: Boolean, default: false },
  side:  { type: String, default: 'right' }, // right | left | bottom
  title: { type: String, default: '' },
})
const emit = defineEmits(['update:open'])
const panel = ref(null)

const close = () => emit('update:open', false)

const panelClass = computed(() => ({
  right:  'top-0 right-0 h-full w-[88%] max-w-md',
  left:   'top-0 left-0 h-full w-[88%] max-w-md',
  bottom: 'bottom-0 inset-x-0 max-h-[88vh] rounded-t-2xl',
}[props.side]))

const transitionName = computed(() => (props.side === 'bottom' ? 'kz-sheet' : props.side === 'left' ? 'kz-slide-left' : 'kz-slide-right'))

watch(() => props.open, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
  if (v) nextTick(() => panel.value?.focus())
})
onUnmounted(() => { document.body.style.overflow = '' })
</script>

<style scoped>
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity 0.25s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }

.kz-slide-right-enter-active, .kz-slide-right-leave-active,
.kz-slide-left-enter-active, .kz-slide-left-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.kz-slide-right-enter-from, .kz-slide-right-leave-to { transform: translateX(100%); }
.kz-slide-left-enter-from, .kz-slide-left-leave-to { transform: translateX(-100%); }

.kz-sheet-enter-active, .kz-sheet-leave-active { transition: transform 0.32s cubic-bezier(0.4,0,0.2,1); }
.kz-sheet-enter-from, .kz-sheet-leave-to { transform: translateY(100%); }

@media (prefers-reduced-motion: reduce) {
  .kz-slide-right-enter-active, .kz-slide-right-leave-active,
  .kz-slide-left-enter-active, .kz-slide-left-leave-active,
  .kz-sheet-enter-active, .kz-sheet-leave-active { transition: none; }
}
</style>
