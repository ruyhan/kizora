<template>
  <div class="rte border border-purple-200 rounded-xl overflow-hidden bg-white">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b border-purple-100 bg-purple-50/40">
      <button v-for="b in buttons" :key="b.title" type="button" :title="b.title"
        @mousedown.prevent="run(b)"
        class="rte-btn" :class="{ 'rte-btn-active': b.state && active[b.state] }">
        <component :is="b.icon" v-if="b.icon" class="w-4 h-4" />
        <span v-else class="text-xs font-bold px-0.5">{{ b.text }}</span>
      </button>
      <span class="mx-1 w-px h-5 bg-purple-200"></span>
      <button type="button" title="Insert image from Media Library" @mousedown.prevent="pickImage = true" class="rte-btn">
        <PhotoIcon class="w-4 h-4" />
      </button>
      <button type="button" title="Clear formatting" @mousedown.prevent="run({ cmd: 'removeFormat' })" class="rte-btn">
        <XMarkIcon class="w-4 h-4" />
      </button>
    </div>

    <!-- Editable area -->
    <div ref="editor" contenteditable="true"
      class="rte-content prose prose-sm max-w-none px-4 py-3 min-h-[16rem] focus:outline-none"
      @input="onInput" @blur="onInput" @keyup="syncActive" @mouseup="syncActive"
      :data-placeholder="placeholder"></div>

    <MediaPicker :open="pickImage" @close="pickImage = false" @select="onImagePicked" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import { PhotoIcon, XMarkIcon, BoldIcon, ItalicIcon, UnderlineIcon,
  ListBulletIcon, LinkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Write your story…' },
})
const emit = defineEmits(['update:modelValue'])

const editor = ref(null)
const pickImage = ref(false)
const active = ref({})

const buttons = [
  { title: 'Bold', cmd: 'bold', state: 'bold', icon: BoldIcon },
  { title: 'Italic', cmd: 'italic', state: 'italic', icon: ItalicIcon },
  { title: 'Underline', cmd: 'underline', state: 'underline', icon: UnderlineIcon },
  { title: 'Heading 2', cmd: 'formatBlock', value: 'H2', text: 'H2' },
  { title: 'Heading 3', cmd: 'formatBlock', value: 'H3', text: 'H3' },
  { title: 'Paragraph', cmd: 'formatBlock', value: 'P', text: 'P' },
  { title: 'Bulleted list', cmd: 'insertUnorderedList', state: 'insertUnorderedList', icon: ListBulletIcon },
  { title: 'Numbered list', cmd: 'insertOrderedList', text: '1.' },
  { title: 'Quote', cmd: 'formatBlock', value: 'BLOCKQUOTE', text: '❝' },
  { title: 'Link', action: 'link', icon: LinkIcon },
]

const run = (b) => {
  editor.value?.focus()
  if (b.action === 'link') {
    const url = window.prompt('Link URL')
    if (url) document.execCommand('createLink', false, url)
  } else if (b.cmd === 'formatBlock') {
    document.execCommand('formatBlock', false, b.value)
  } else {
    document.execCommand(b.cmd, false, null)
  }
  onInput()
  syncActive()
}

const onImagePicked = (media) => {
  pickImage.value = false
  const url = media?.file_url
  if (!url) return
  editor.value?.focus()
  document.execCommand('insertHTML', false, `<img src="${url}" alt="" />`)
  onInput()
}

const onInput = () => emit('update:modelValue', editor.value?.innerHTML || '')

const syncActive = () => {
  const s = {}
  for (const b of buttons) {
    if (b.state) { try { s[b.state] = document.queryCommandState(b.state) } catch { s[b.state] = false } }
  }
  active.value = s
}

onMounted(() => { if (editor.value) editor.value.innerHTML = props.modelValue || '' })

// Keep in sync if the parent resets the value (e.g. after load).
watch(() => props.modelValue, (v) => {
  if (editor.value && v !== editor.value.innerHTML) editor.value.innerHTML = v || ''
})
</script>

<style scoped>
@reference "../../../css/app.css";
.rte-btn { @apply w-8 h-8 rounded-lg flex items-center justify-center text-charcoal hover:bg-white hover:text-ink transition-colors; }
.rte-btn-active { @apply bg-white text-ink shadow-sm; }
.rte-content:empty::before { content: attr(data-placeholder); @apply text-muted/60; }
.rte-content :deep(img) { @apply rounded-lg my-3 max-w-full; }
.rte-content :deep(blockquote) { @apply border-l-4 border-purple-200 pl-4 italic text-muted; }
.rte-content :deep(a) { @apply text-primary underline; }
</style>
