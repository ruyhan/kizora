<template>
  <AdminLayout>
    <Head title="Media Library" />

    <div class="space-y-6 w-full">

      <!-- Header -->
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Media Library</h1>
          <p class="text-muted text-sm mt-1">Manage and reuse uploaded images across the admin panel</p>
        </div>
        <label class="btn-primary text-sm px-5 py-2.5 inline-flex items-center gap-2 cursor-pointer">
          <ArrowUpTrayIcon class="w-4 h-4" />
          {{ uploading ? 'Uploading…' : 'Upload Images' }}
          <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
            multiple class="hidden" @change="onInputChange" :disabled="uploading">
        </label>
      </div>

      <!-- Drag & drop zone -->
      <label v-bind="dragHandlers"
        class="block border-2 border-dashed rounded-2xl px-6 py-8 text-center cursor-pointer transition-colors"
        :class="isDragging ? 'border-primary bg-purple-50' : 'border-purple-200 hover:border-primary bg-purple-50/30'">
        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center mx-auto mb-2 shadow-sm">
          <ArrowUpTrayIcon class="w-6 h-6 text-primary/60" />
        </div>
        <p class="text-charcoal font-medium text-sm">
          {{ isDragging ? 'Drop images to upload' : 'Drag & drop images here, or click to browse' }}
        </p>
        <p class="text-muted text-xs mt-1">Supported: JPG, PNG, WebP, SVG · Max 5 MB each</p>
        <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
          multiple class="hidden" @change="onInputChange" :disabled="uploading">
      </label>

      <!-- Search -->
      <div class="relative max-w-sm">
        <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
        <input v-model="search" @input="onSearch" type="text"
          placeholder="Search by file name…" class="input-field pl-10 text-sm">
      </div>

      <!-- Grid -->
      <div v-if="media.data.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div v-for="item in media.data" :key="item.id"
          class="group bg-white rounded-xl border border-purple-100 overflow-hidden">
          <button type="button" @click="preview = item" class="block aspect-square w-full bg-purple-50 overflow-hidden">
            <img :src="item.file_url" :alt="item.original_name" class="w-full h-full object-cover" loading="lazy">
          </button>
          <div class="p-2.5">
            <p class="text-xs font-medium text-charcoal truncate" :title="item.original_name">{{ item.original_name }}</p>
            <p class="text-[10px] text-muted mt-0.5">{{ item.human_size }} · {{ item.width }}×{{ item.height }}</p>
            <div class="flex items-center gap-1 mt-2">
              <button @click="copyUrl(item)" class="flex-1 text-[10px] py-1 rounded-md bg-purple-50 text-primary hover:bg-purple-100 transition-colors flex items-center justify-center gap-1">
                <LinkIcon class="w-3 h-3" /> Copy URL
              </button>
              <button @click="askDelete(item)" class="p-1.5 rounded-md text-gray-300 hover:text-error hover:bg-red-50 transition-colors">
                <TrashIcon class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="py-16 text-center">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
          <PhotoIcon class="w-7 h-7 text-primary/40" />
        </div>
        <p class="text-muted">No images yet. Upload your first image.</p>
      </div>

      <!-- Pagination -->
      <div v-if="media.last_page > 1" class="flex justify-center gap-2">
        <Link v-for="link in media.links" :key="link.label" :href="link.url || '#'"
          :class="[link.active ? 'bg-primary text-white' : 'bg-white text-charcoal hover:bg-purple-50', !link.url ? 'opacity-40 pointer-events-none' : '']"
          class="px-3 py-1.5 rounded-lg text-sm border border-purple-100 transition-colors" v-html="link.label" />
      </div>
    </div>

    <!-- Preview lightbox -->
    <Teleport to="body">
      <div v-if="preview" class="fixed inset-0 z-[60] flex items-center justify-center p-4" @click="preview = null">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="relative max-w-2xl w-full bg-white rounded-2xl overflow-hidden shadow-2xl" @click.stop>
          <img :src="preview.file_url" :alt="preview.original_name" class="w-full max-h-[60vh] object-contain bg-purple-50">
          <div class="p-4 flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="font-medium text-charcoal truncate">{{ preview.original_name }}</p>
              <p class="text-xs text-muted">{{ preview.mime_type }} · {{ preview.human_size }} · {{ preview.width }}×{{ preview.height }}</p>
            </div>
            <button @click="preview = null" class="btn-secondary text-sm px-4 py-2 shrink-0">Close</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete confirm -->
    <Teleport to="body">
      <div v-if="deleting" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleting = null"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-sm shadow-2xl p-6">
          <h3 class="font-display font-semibold text-charcoal text-lg mb-2">Delete image?</h3>
          <img :src="deleting.file_url" class="w-full h-32 object-cover rounded-xl mb-3 bg-purple-50">
          <p v-if="usageChecking" class="text-sm text-muted mb-4">Checking usage…</p>
          <p v-else-if="usageWarning" class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
            ⚠ This image may be used in {{ usageWarning }}. Deleting it could break image display.
          </p>
          <p v-else class="text-sm text-muted mb-4">This image isn't referenced anywhere. Safe to delete.</p>
          <div class="flex gap-3">
            <button @click="deleting = null" class="btn-secondary flex-1 text-sm">Cancel</button>
            <button @click="confirmDelete" class="flex-1 text-sm py-2.5 rounded-full bg-error text-white font-semibold hover:bg-red-700 transition-colors">
              Delete
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useDragUpload, validateImages } from '@/composables/useDragUpload'
import { ArrowUpTrayIcon, MagnifyingGlassIcon, PhotoIcon, TrashIcon, LinkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ media: Object, filters: Object })
const toast = useToast()

const search = ref(props.filters?.search || '')
let searchTimer = null
const onSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('admin.media.index'), { search: search.value }, { preserveState: true, preserveScroll: true, replace: true })
  }, 350)
}

const uploading = ref(false)

const onInputChange = (e) => {
  handleFiles(e.target.files)
  e.target.value = ''
}

const { isDragging, dragHandlers } = useDragUpload((files) => handleFiles(files))

const handleFiles = (fileList) => {
  const { valid, errors } = validateImages(fileList, 5)

  if (errors.length) {
    toast.error(errors.length === 1 ? errors[0] : `${errors.length} files were skipped (wrong type or too large).`)
  }
  if (!valid.length) return

  uploading.value = true
  const fd = new FormData()
  valid.forEach(f => fd.append('files[]', f))
  router.post(route('admin.media.store'), fd, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => { uploading.value = false },
  })
}

const preview = ref(null)

const copyUrl = (item) => {
  navigator.clipboard.writeText(item.file_url)
  toast.success('URL copied to clipboard.')
}

// ── Delete with usage check ──
const deleting      = ref(null)
const usageChecking = ref(false)
const usageWarning  = ref('')

const askDelete = async (item) => {
  deleting.value = item
  usageWarning.value = ''
  usageChecking.value = true
  try {
    const { data } = await axios.get(route('admin.media.usage', item.id))
    if (data.in_use) usageWarning.value = data.where.join(', ')
  } catch { /* ignore */ } finally {
    usageChecking.value = false
  }
}

const confirmDelete = () => {
  const item = deleting.value
  router.delete(route('admin.media.destroy', item.id), {
    preserveScroll: true,
    onFinish: () => { deleting.value = null },
  })
}
</script>
