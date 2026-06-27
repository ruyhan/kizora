<template>
  <AdminLayout>
    <Head title="Categories" />

    <div class="space-y-6 w-full">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Categories</h1>
          <p class="text-muted text-sm mt-1">Manage product categories</p>
        </div>
        <button @click="openCreate" class="btn-primary flex items-center gap-2">
          <PlusIcon class="w-4 h-4" />
          Add Category
        </button>
      </div>

      <!-- Search + date filter -->
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[200px]">
          <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search categories…" class="input-field text-sm pl-9 py-2 w-full">
        </div>
        <DateRangeFilter route-name="admin.categories.index" :filters="filters" :params="f" />
      </div>

      <!-- Over-limit notice -->
      <div v-if="featuredCount > featuredLimit" class="flex items-start gap-2.5 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm">
        <ExclamationTriangleIcon class="w-5 h-5 shrink-0 mt-px" />
        <span><strong>{{ featuredCount }}</strong> categories are marked featured, but the homepage "Shop by Category" section only shows the top <strong>{{ featuredLimit }}</strong> (lowest Featured Sort Order). The rest remain saved but hidden on the homepage.</span>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
        <table class="w-full">
          <thead class="bg-purple-50 border-b border-purple-100">
            <tr>
              <th v-if="canDelete" class="px-6 py-4 w-10"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Icon</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Name</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Slug</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Products</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Sort</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Featured</th>
              <th class="text-left px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Status</th>
              <th class="text-right px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-purple-50/30 transition-colors" :class="selected.includes(cat.id) ? 'bg-purple-50/40' : ''">
              <td v-if="canDelete" class="px-6 py-4"><input type="checkbox" :value="cat.id" v-model="selected" class="kz-check"></td>
              <td class="px-6 py-4">
                <span class="w-9 h-9 rounded-xl bg-purple-50 overflow-hidden flex items-center justify-center text-charcoal">
                  <img v-if="cat.image_url" :src="cat.image_url" :alt="cat.name" class="w-full h-full object-cover">
                  <CategoryIcon v-else :slug="cat.slug" :icon="cat.icon" class="w-5 h-5" />
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2" :class="cat.parent_id ? 'pl-6' : ''">
                  <span v-if="cat.parent_id" class="text-muted select-none">↳</span>
                  <div>
                    <p class="font-semibold text-charcoal flex items-center gap-2">
                      {{ cat.name }}
                      <span v-if="!cat.parent_id" class="badge badge-purple text-[10px] font-medium">Parent</span>
                    </p>
                    <p class="text-xs text-muted mt-0.5">{{ cat.description }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-muted font-mono">{{ cat.slug }}</td>
              <td class="px-6 py-4">
                <span class="badge badge-purple">{{ cat.products_count }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-muted">{{ cat.sort_order }}</td>
              <td class="px-6 py-4">
                <span v-if="cat.is_featured" class="inline-flex items-center gap-1 badge badge-purple text-xs">
                  <StarIcon class="w-3.5 h-3.5 text-amber-500" /> #{{ cat.featured_sort_order }}
                </span>
                <span v-else class="text-muted text-xs">—</span>
              </td>
              <td class="px-6 py-4">
                <span :class="cat.is_active ? 'badge-success' : 'badge-error'" class="badge text-xs">
                  {{ cat.is_active ? 'Active' : 'Hidden' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openEdit(cat)" class="p-2 text-muted hover:text-primary hover:bg-purple-50 rounded-lg transition-colors">
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteCategory(cat)" :disabled="cat.products_count > 0"
                    class="p-2 text-muted hover:text-error hover:bg-red-50 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!categories.length" class="py-16 text-center">
          <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
            <FolderOpenIcon class="w-7 h-7 text-primary/40" />
          </div>
          <p class="text-muted">No categories yet.</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[92vh] flex flex-col">
          <div class="p-6 border-b border-purple-100 flex items-center justify-between shrink-0">
            <h3 class="font-display font-semibold text-charcoal text-lg">{{ editing ? 'Edit' : 'New' }} Category</h3>
            <button @click="closeModal" class="p-2 text-muted hover:text-charcoal rounded-lg hover:bg-gray-100">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <form @submit.prevent="save" class="flex flex-col min-h-0 flex-1">
            <div class="p-6 overflow-y-auto flex-1">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Name *</label>
                <input v-model="form.name" @input="autoSlug" type="text" class="input-field" required placeholder="e.g. Backpacks">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Slug *</label>
                <input v-model="form.slug" type="text" class="input-field font-mono text-sm" required placeholder="backpacks">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Parent category</label>
                <SelectMenu v-model="form.parent_id" :options="parentSelectOptions" placeholder="None (top-level)" />
                <p class="text-xs text-muted mt-1">Nest this as a sub-category under a mother category.</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-charcoal mb-1">Icon</label>
                <div class="flex items-center gap-2">
                  <span class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-charcoal shrink-0">
                    <CategoryIcon :icon="form.icon" :slug="form.slug" class="w-5 h-5" />
                  </span>
                  <SelectMenu v-model="form.icon" :options="iconSelectOptions" class="flex-1" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-charcoal mb-1">Sort Order</label>
                <input v-model.number="form.sort_order" type="number" class="input-field" min="0">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Description</label>
                <textarea v-model="form.description" rows="2" class="input-field resize-none" placeholder="Short description..."></textarea>
              </div>

              <!-- Category image (chosen from the Media Library; used on the homepage card) -->
              <div class="col-span-2">
                <label class="block text-sm font-medium text-charcoal mb-1">Category Image</label>
                <div class="flex items-center gap-3">
                  <div class="w-16 h-16 rounded-xl bg-purple-50 overflow-hidden flex items-center justify-center shrink-0">
                    <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" alt="">
                    <PhotoIcon v-else class="w-7 h-7 text-primary/30" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <button type="button" @click="showMediaPicker = true" class="btn-secondary text-sm px-3 py-2 inline-flex items-center gap-1.5">
                        <PhotoIcon class="w-4 h-4" /> {{ imagePreview ? 'Change image' : 'Choose from Media Library' }}
                      </button>
                      <button v-if="imagePreview" type="button" @click="clearImage" class="text-xs text-error hover:underline">Remove</button>
                    </div>
                    <p class="text-xs text-muted mt-1.5">Pick or upload via the Media Library. Shown on the homepage "Shop by Category" card.</p>
                  </div>
                </div>
              </div>

              <div class="col-span-2 flex items-center gap-3">
                <input v-model="form.is_active" type="checkbox" id="cat_active" class="w-4 h-4 accent-primary">
                <label for="cat_active" class="text-sm font-medium text-charcoal">Active (show in shop)</label>
              </div>

              <!-- Featured (homepage Shop by Category) — requires Manage Featured Categories -->
              <div v-if="canManageFeatured" class="col-span-2 rounded-xl border border-purple-100 bg-purple-50/30 p-4 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 accent-primary">
                  <span class="text-sm font-semibold text-charcoal inline-flex items-center gap-1.5"><StarIcon class="w-4 h-4 text-amber-500" /> Featured Category</span>
                </label>
                <p class="text-xs text-muted -mt-1">Show this category in the homepage "Shop by Category" section.</p>
                <div v-if="form.is_featured">
                  <label class="block text-sm font-medium text-charcoal mb-1">Featured Sort Order</label>
                  <input v-model.number="form.featured_sort_order" type="number" min="0" class="input-field w-32">
                  <p class="text-xs text-muted mt-1">Lower number shows first. Homepage displays the top {{ featuredLimit }} featured categories.</p>
                </div>
              </div>
            </div>
            </div>
            <div class="flex gap-3 p-6 pt-4 border-t border-purple-100 shrink-0">
              <button type="button" @click="closeModal" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary flex-1">
                {{ saving ? 'Saving...' : 'Save Category' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Media Library picker for the category image -->
    <MediaPicker :open="showMediaPicker" @close="showMediaPicker = false" @select="onImageSelect" />

    <BulkDeleteBar :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected = []" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon, FolderOpenIcon, PhotoIcon, ExclamationTriangleIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { StarIcon } from '@heroicons/vue/24/solid'
import CategoryIcon, { CATEGORY_ICON_OPTIONS as iconOptions } from '@/Components/icons/CategoryIcon.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { useToast } from 'vue-toastification'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  categories: Array,
  parents: { type: Array, default: () => [] },
  featuredCount: { type: Number, default: 0 },
  featuredLimit: { type: Number, default: 8 },
  canManageFeatured: { type: Boolean, default: false },
  filters: { type: Object, default: () => ({}) },
})

// Search + date filter
const f = ref({ search: props.filters?.search || '', range: props.filters?.range, from: props.filters?.from, to: props.filters?.to })
let _t
const apply = () => router.get(route('admin.categories.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(_t); _t = setTimeout(apply, 350) }

// Bulk selection / delete
const toast = useToast()
const { can } = usePermissions()
const canDelete = can('categories.delete')
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.categories.length > 0 && selected.value.length === props.categories.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.categories.map((c) => c.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.categories.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { selected.value = [] },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

const modalOpen = ref(false)
const editing = ref(null)
const saving = ref(false)
const blank = () => ({ name: '', slug: '', icon: '', description: '', sort_order: 0, is_active: true, parent_id: '', is_featured: false, featured_sort_order: 0 })
const form = ref(blank())

// Category image is chosen from the Media Library. imagePath = newly-picked path
// (null = unchanged), removeImage = clear the existing one, imagePreview = thumbnail.
const showMediaPicker = ref(false)
const imagePath = ref(null)
const removeImage = ref(false)
const imagePreview = ref(null)

// Can't be its own parent (keeps the tree to two levels).
const parentOptions = computed(() => props.parents.filter(p => !editing.value || p.id !== editing.value.id))
const parentSelectOptions = computed(() => [{ value: '', label: 'None (top-level)' }, ...parentOptions.value.map(p => ({ value: p.id, label: p.name }))])
const iconSelectOptions = [{ value: '', label: 'Auto (by name)' }, ...iconOptions]

const resetImage = () => { imagePath.value = null; removeImage.value = false; imagePreview.value = null }

const openCreate = () => {
  editing.value = null
  form.value = blank()
  resetImage()
  modalOpen.value = true
}

const openEdit = (cat) => {
  editing.value = cat
  form.value = {
    name: cat.name, slug: cat.slug, icon: cat.icon || '', description: cat.description || '',
    sort_order: cat.sort_order, is_active: cat.is_active, parent_id: cat.parent_id || '',
    is_featured: !!cat.is_featured, featured_sort_order: cat.featured_sort_order || 0,
  }
  resetImage()
  imagePreview.value = cat.image_url || null
  modalOpen.value = true
}

const closeModal = () => { modalOpen.value = false }

const onImageSelect = (media) => {
  imagePath.value = media.file_path
  imagePreview.value = media.file_url
  removeImage.value = false
  showMediaPicker.value = false
}
const clearImage = () => {
  imagePath.value = null
  imagePreview.value = null
  removeImage.value = true
}

const autoSlug = () => {
  if (!editing.value) {
    form.value.slug = form.value.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  }
}

const save = () => {
  saving.value = true
  const payload = {
    name: form.value.name,
    slug: form.value.slug,
    parent_id: form.value.parent_id || null,
    icon: form.value.icon,
    sort_order: form.value.sort_order,
    description: form.value.description,
    is_active: form.value.is_active ? 1 : 0,
    image_path: imagePath.value || null,
    remove_image: removeImage.value ? 1 : 0,
  }
  if (props.canManageFeatured) {
    payload.is_featured = form.value.is_featured ? 1 : 0
    payload.featured_sort_order = form.value.featured_sort_order || 0
  }

  const opts = { preserveScroll: true, onSuccess: () => closeModal(), onFinish: () => { saving.value = false } }
  if (editing.value) {
    router.patch(route('admin.categories.update', editing.value.id), payload, opts)
  } else {
    router.post(route('admin.categories.store'), payload, opts)
  }
}

const deleteCategory = (cat) => {
  if (!confirm(`Delete "${cat.name}"?`)) return
  router.delete(route('admin.categories.destroy', cat.id))
}
</script>
