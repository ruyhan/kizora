<template>
  <AdminLayout>
    <Head :title="product ? 'Edit Product' : 'Add Product'" />

    <div class="flex items-center justify-between mb-6">
      <h1 class="font-display text-2xl font-bold text-charcoal">{{ product ? 'Edit Product' : 'Add New Product' }}</h1>
      <Link :href="route('admin.products.index')" class="text-primary text-sm hover:underline">← Back to Products</Link>
    </div>

    <form @submit.prevent="submit" enctype="multipart/form-data">
      <div class="grid lg:grid-cols-3 gap-6">

        <!-- ── Main Info ── -->
        <div class="lg:col-span-2 space-y-5">
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <h3 class="font-display font-semibold text-charcoal text-lg mb-4">Product Information</h3>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Product Name *</label>
                <input v-model="form.name" type="text" class="input-field" required>
                <p v-if="errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Category *</label>
                <SelectMenu v-model="form.category_id" :options="categoryOptions" placeholder="Select Category" :error="!!errors.category_id" />
                <p v-if="errors.category_id" class="text-error text-xs mt-1">{{ errors.category_id }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Short Description</label>
                <textarea v-model="form.short_description" rows="2" class="input-field" placeholder="Brief product description"></textarea>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Full Description</label>
                <textarea v-model="form.description" rows="5" class="input-field" placeholder="Detailed product description"></textarea>
              </div>
            </div>
          </div>

          <!-- USPs -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <h3 class="font-display font-semibold text-charcoal text-lg mb-1">Key Selling Points (USP)</h3>
            <p class="text-xs text-muted mb-4">Highlighted on the product page — each a short title with a one-line detail (capacity, material, warranty…).</p>
            <div class="space-y-3">
              <div v-for="(u, i) in form.usps" :key="i" class="flex gap-2 items-start rounded-xl border border-purple-100 bg-purple-50/40 p-3">
                <span class="mt-2 text-xs font-bold text-muted w-5 shrink-0">{{ i + 1 }}</span>
                <div class="flex-1 space-y-2 min-w-0">
                  <input v-model="u.title" type="text" class="input-field" placeholder="Title — e.g. 25 L capacity">
                  <input v-model="u.text" type="text" class="input-field text-sm" placeholder="Short line — e.g. Fits a 15&quot; laptop + daily gear">
                </div>
                <button type="button" @click="form.usps.splice(i, 1)" aria-label="Remove"
                  class="mt-1 w-9 h-9 rounded-lg border border-purple-100 bg-white text-error flex items-center justify-center shrink-0 hover:bg-error/5">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <button type="button" @click="form.usps.push({ title: '', text: '' })"
              class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-accent transition-colors">
              <PlusIcon class="w-4 h-4" /> Add selling point
            </button>
            <p v-if="errors['usps.0.title'] || errors.usps" class="text-error text-xs mt-2">Each selling point needs a title.</p>
          </div>

          <!-- Pricing -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <h3 class="font-display font-semibold text-charcoal text-lg mb-4">Pricing &amp; Inventory</h3>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Regular Price (৳) *</label>
                <input v-model="form.price" type="number" step="0.01" class="input-field" required>
                <p v-if="errors.price" class="text-error text-xs mt-1">{{ errors.price }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Sale Price (৳)</label>
                <input v-model="form.discount_price" type="number" step="0.01" class="input-field" placeholder="Optional">
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Stock Quantity *</label>
                <input v-model="form.stock" type="number" class="input-field" required>
                <p v-if="errors.stock" class="text-error text-xs mt-1">{{ errors.stock }}</p>
              </div>
            </div>
          </div>

          <!-- Variations -->
          <VariationsEditor v-model:enabled="form.has_variations" v-model:items="form.variations" />

        </div>

        <!-- ── Sidebar ── -->
        <div class="space-y-5">

          <!-- Main Image -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <h3 class="font-display font-semibold text-charcoal text-lg mb-4">Main Image</h3>
            <div v-if="featuredUrl" class="mb-4">
              <img :src="featuredUrl" alt="Main image" class="w-full aspect-square object-cover rounded-xl">
            </div>
            <button type="button" @click="pickerMain = true"
              class="w-full border-2 border-dashed border-purple-200 rounded-xl p-6 text-center hover:border-primary transition-colors">
              <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-2">
                <PhotoIcon class="w-6 h-6 text-primary/50" />
              </div>
              <p class="text-sm text-primary font-medium">{{ featuredUrl ? 'Change Image' : 'Choose Image' }}</p>
              <p class="text-xs text-muted mt-1">Select from Media Library</p>
            </button>
          </div>

          <!-- Gallery Images -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-display font-semibold text-charcoal text-base">Gallery Images</h3>
              <span class="text-xs text-muted bg-purple-50 px-2 py-0.5 rounded-full">
                {{ galleryCount }} image{{ galleryCount !== 1 ? 's' : '' }}
              </span>
            </div>
            <p class="text-xs text-muted mb-3">Clickable thumbnails on the product page.</p>

            <!-- Saved gallery (edit mode) -->
            <div v-if="existingGallery.length" class="mb-3">
              <p class="text-[10px] font-semibold text-muted uppercase tracking-wider mb-2">Saved</p>
              <div class="grid grid-cols-3 gap-2">
                <div v-for="img in existingGallery" :key="img.id"
                  class="relative group aspect-square rounded-lg overflow-hidden bg-purple-50"
                  :class="pendingDeleteIds.includes(img.id) ? 'opacity-40 ring-2 ring-error' : ''">
                  <img :src="img.image_url" alt="" class="w-full h-full object-cover">
                  <button type="button" @click="toggleDelete(img.id)"
                    class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                    <div class="flex flex-col items-center gap-0.5 text-white">
                      <ArrowUturnLeftIcon v-if="pendingDeleteIds.includes(img.id)" class="w-3.5 h-3.5" />
                      <TrashIcon v-else class="w-3.5 h-3.5" />
                      <span class="text-[9px]">{{ pendingDeleteIds.includes(img.id) ? 'Undo' : 'Remove' }}</span>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Newly selected from library -->
            <div v-if="galleryMedia.length" class="mb-3">
              <p class="text-[10px] font-semibold text-muted uppercase tracking-wider mb-2">Selected</p>
              <div class="grid grid-cols-3 gap-2">
                <div v-for="(m, i) in galleryMedia" :key="m.id"
                  class="relative group aspect-square rounded-lg overflow-hidden bg-purple-50">
                  <img :src="m.file_url" alt="" class="w-full h-full object-cover">
                  <button type="button" @click="galleryMedia.splice(i, 1)"
                    class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                    <XMarkIcon class="w-4 h-4 text-white" />
                  </button>
                </div>
              </div>
            </div>

            <button type="button" @click="pickerGallery = true"
              class="w-full border-2 border-dashed border-purple-200 rounded-xl p-4 text-center hover:border-primary transition-colors">
              <PhotoIcon class="w-5 h-5 text-primary/40 mx-auto mb-1" />
              <p class="text-xs text-primary font-medium">Choose Images</p>
            </button>
          </div>

          <!-- Media pickers -->
          <MediaPicker :open="pickerMain" @close="pickerMain = false" @select="onMainSelect" />
          <MediaPicker :open="pickerGallery" multiple @close="pickerGallery = false" @select="onGallerySelect" />

          <!-- Settings -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <h3 class="font-display font-semibold text-charcoal text-lg mb-4">Settings</h3>
            <div class="space-y-3">
              <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-purple-50 transition-colors">
                <input v-model="form.is_active" type="checkbox" class="rounded text-primary">
                <div>
                  <p class="font-medium text-charcoal text-sm">Active</p>
                  <p class="text-muted text-xs">Visible to customers</p>
                </div>
              </label>
              <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-purple-50 transition-colors">
                <input v-model="form.is_featured" type="checkbox" class="rounded text-primary">
                <div>
                  <p class="font-medium text-charcoal text-sm">Featured</p>
                  <p class="text-muted text-xs">Show in featured products section</p>
                </div>
              </label>
              <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-purple-50 transition-colors border border-purple-100">
                <input v-model="form.show_on_hero" type="checkbox" class="rounded text-primary">
                <div>
                  <p class="font-medium text-charcoal text-sm">Show on Hero</p>
                  <p class="text-muted text-xs">Display in homepage hero banner</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Submit -->
          <button type="submit" :disabled="processing" class="btn-primary w-full py-4 text-base">
            <span v-if="processing">{{ product ? 'Saving...' : 'Creating...' }}</span>
            <span v-else>{{ product ? 'Save Changes' : 'Create Product' }}</span>
          </button>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import VariationsEditor from '@/Components/admin/VariationsEditor.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import { PhotoIcon, TrashIcon, XMarkIcon, ArrowUturnLeftIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  product:    Object,
  categories: Array,
})

const categoryOptions = computed(() => (props.categories || []).map(c => ({ value: c.id, label: c.name })))

const errors     = ref({})
const processing = ref(false)

const form = ref({
  category_id:       props.product?.category_id       || '',
  name:              props.product?.name               || '',
  short_description: props.product?.short_description  || '',
  description:       props.product?.description        || '',
  usps:              JSON.parse(JSON.stringify(props.product?.usps || [])),
  has_variations:    props.product?.has_variations     ?? false,
  variations:        JSON.parse(JSON.stringify(props.product?.variations || [])),
  price:             props.product?.price              || '',
  discount_price:    props.product?.discount_price     || '',
  stock:             props.product?.stock              ?? 0,
  is_active:         props.product?.is_active          ?? true,
  is_featured:       props.product?.is_featured        ?? false,
  show_on_hero:      props.product?.show_on_hero       ?? false,
})

// ── Media picker state ─────────────────────────────────────────────────────────
const pickerMain    = ref(false)
const pickerGallery = ref(false)

// Main image — path stored when chosen from library
const featuredPath = ref(null)
const featuredUrl  = ref(props.product?.featured_image_url || null)

const onMainSelect = (media) => {
  featuredPath.value = media.file_path
  featuredUrl.value  = media.file_url
}

// ── Gallery ───────────────────────────────────────────────────────────────────
const existingGallery  = ref(props.product?.gallery_images ?? [])
const pendingDeleteIds = ref([])
const galleryMedia     = ref([])   // newly chosen media files

const galleryCount = computed(() =>
  (existingGallery.value.length - pendingDeleteIds.value.length) + galleryMedia.value.length
)

const toggleDelete = (id) => {
  const idx = pendingDeleteIds.value.indexOf(id)
  if (idx === -1) pendingDeleteIds.value.push(id)
  else pendingDeleteIds.value.splice(idx, 1)
}

const onGallerySelect = (list) => {
  for (const m of list) {
    if (!galleryMedia.value.some(x => x.id === m.id)) galleryMedia.value.push(m)
  }
}

// ── Submit ────────────────────────────────────────────────────────────────────
const submit = () => {
  processing.value = true
  errors.value = {}

  const payload = { ...form.value }
  if (featuredPath.value) payload.featured_image_path = featuredPath.value
  payload.gallery_paths      = galleryMedia.value.map(m => m.file_path)
  payload.delete_gallery_ids = pendingDeleteIds.value

  const url = props.product
    ? route('admin.products.update', props.product.id)
    : route('admin.products.store')

  if (props.product) payload._method = 'PUT'

  router.post(url, payload, {
    onError:   (errs) => { errors.value = errs; processing.value = false },
    onSuccess: () => { processing.value = false },
  })
}
</script>
