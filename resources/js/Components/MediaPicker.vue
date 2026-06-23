<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

      <div class="relative bg-white rounded-2xl w-full max-w-4xl max-h-[88vh] shadow-2xl flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-purple-100 shrink-0">
          <div>
            <h3 class="font-display font-semibold text-charcoal text-lg">Media Library</h3>
            <p class="text-xs text-muted">{{ multiple ? 'Select one or more images' : 'Select an image' }}</p>
          </div>
          <button @click="close" class="p-2 text-muted hover:text-charcoal rounded-lg hover:bg-gray-100">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center gap-3 px-6 py-3 border-b border-purple-50 shrink-0 flex-wrap">
          <div class="relative flex-1 min-w-48">
            <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
            <input v-model="search" @input="onSearch" type="text" placeholder="Search images…"
              class="input-field pl-10 text-sm py-2">
          </div>
          <label class="btn-primary text-sm px-4 py-2 inline-flex items-center gap-2 cursor-pointer shrink-0">
            <ArrowUpTrayIcon class="w-4 h-4" />
            <span>{{ uploading ? 'Uploading…' : 'Upload' }}</span>
            <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
              multiple class="hidden" @change="onInputChange" :disabled="uploading">
          </label>
        </div>

        <!-- Grid — also a drop target -->
        <div class="relative flex-1 overflow-y-auto p-6"
          v-bind="dragHandlers"
          :class="isDragging ? 'ring-2 ring-inset ring-primary bg-purple-50/40' : ''">

          <!-- Drag overlay -->
          <div v-if="isDragging"
            class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 backdrop-blur-sm pointer-events-none">
            <ArrowUpTrayIcon class="w-10 h-10 text-primary mb-2" />
            <p class="text-primary font-semibold">Drop images to upload</p>
          </div>

          <div v-if="loading" class="flex items-center justify-center h-40">
            <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
          </div>

          <!-- Empty = prominent drop zone -->
          <label v-else-if="items.length === 0"
            class="flex flex-col items-center justify-center h-48 text-center border-2 border-dashed border-purple-200 rounded-2xl cursor-pointer hover:border-primary transition-colors">
            <PhotoIcon class="w-10 h-10 text-primary/30 mb-2" />
            <p class="text-charcoal text-sm font-medium">Drag &amp; drop images here</p>
            <p class="text-muted text-xs mt-1">or click to browse · JPG, PNG, WebP, SVG · Max 5 MB</p>
            <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
              multiple class="hidden" @change="onInputChange" :disabled="uploading">
          </label>

          <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
            <button v-for="item in items" :key="item.id" type="button"
              @click="toggle(item)"
              :class="isSelected(item) ? 'ring-2 ring-primary ring-offset-2' : 'ring-1 ring-purple-100 hover:ring-purple-300'"
              class="relative aspect-square rounded-xl overflow-hidden bg-purple-50 transition-all">
              <img :src="item.file_url" :alt="item.original_name" class="w-full h-full object-cover" loading="lazy">
              <span v-if="isSelected(item)"
                class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center">
                <CheckIcon class="w-3 h-3" />
              </span>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-purple-100 shrink-0">
          <p class="text-xs text-muted">{{ selected.length }} selected</p>
          <div class="flex gap-3">
            <button @click="close" class="btn-secondary text-sm px-5 py-2">Cancel</button>
            <button @click="confirm" :disabled="selected.length === 0"
              class="btn-primary text-sm px-5 py-2 disabled:opacity-50 disabled:cursor-not-allowed">
              Select{{ selected.length > 1 ? ` (${selected.length})` : '' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import { useDragUpload, validateImages } from '@/composables/useDragUpload'
import {
  XMarkIcon, MagnifyingGlassIcon, ArrowUpTrayIcon, PhotoIcon, CheckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  open:     { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'select'])

const toast    = useToast()
const items    = ref([])
const selected = ref([])
const loading  = ref(false)
const uploading = ref(false)
const search   = ref('')
let searchTimer = null

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    selected.value = []
    search.value = ''
    fetchMedia()
  }
})

const fetchMedia = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(route('admin.media.list'), { params: { search: search.value } })
    items.value = data.data ?? []
  } catch {
    toast.error('Could not load media.')
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchMedia, 300)
}

const isSelected = (item) => selected.value.some(s => s.id === item.id)

const toggle = (item) => {
  if (props.multiple) {
    const i = selected.value.findIndex(s => s.id === item.id)
    if (i === -1) selected.value.push(item)
    else selected.value.splice(i, 1)
  } else {
    selected.value = [item]
  }
}

const onInputChange = (e) => {
  handleFiles(e.target.files)
  e.target.value = ''
}

// Drag-and-drop into the grid
const { isDragging, dragHandlers } = useDragUpload((files) => handleFiles(files))

const handleFiles = async (fileList) => {
  const { valid, errors } = validateImages(fileList, 5)

  // One combined error toast (no duplicates)
  if (errors.length) {
    toast.error(errors.length === 1 ? errors[0] : `${errors.length} files were skipped (wrong type or too large).`)
  }
  if (!valid.length) return

  uploading.value = true
  let uploaded = 0
  try {
    for (const file of valid) {
      const fd = new FormData()
      fd.append('file', file)
      const { data } = await axios.post(route('admin.media.upload'), fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      items.value.unshift(data) // newest first
      if (!props.multiple) selected.value = [data]
      else selected.value.push(data)
      uploaded++
    }
    if (uploaded) toast.success(uploaded > 1 ? `${uploaded} images uploaded.` : 'Image uploaded.')
  } catch (err) {
    const msg = err?.response?.data?.errors?.file?.[0] || 'Upload failed.'
    toast.error(msg)
  } finally {
    uploading.value = false
  }
}

const confirm = () => {
  if (selected.value.length === 0) return
  emit('select', props.multiple ? [...selected.value] : selected.value[0])
  close()
}

const close = () => emit('close')
</script>
