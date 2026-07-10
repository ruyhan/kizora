<template>
  <AdminLayout>
    <Head title="Products" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <h1 class="font-display text-2xl font-bold text-charcoal">Products</h1>
      <div class="flex items-center gap-2">
        <a :href="route('admin.products.export')"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-charcoal px-4 py-2.5 bg-white border border-purple-100 rounded-xl hover:bg-purple-50 transition-colors">
          <ArrowDownTrayIcon class="w-4 h-4" /> Export CSV
        </a>
        <button v-if="canCreate" @click="openImport"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-charcoal px-4 py-2.5 bg-white border border-purple-100 rounded-xl hover:bg-purple-50 transition-colors">
          <ArrowUpTrayIcon class="w-4 h-4" /> Import CSV
        </button>
        <Link :href="route('admin.products.create')" class="btn-primary text-sm px-5 py-2.5">
          + Add Product
        </Link>
      </div>
    </div>

    <!-- Search + filters -->
    <div class="flex flex-wrap items-center gap-2 mb-5">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search products or SKU…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <SelectMenu :model-value="f.category" @update:model-value="v => { f.category = v; apply() }" :options="categorySelect" trigger-class="text-sm py-2" class="w-48" />
      <DateRangeFilter route-name="admin.products.index" :filters="filters" :params="f" />
    </div>

    <!-- Bulk actions (appear when rows are selected) -->
    <div v-if="selected.length && canEdit" class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-white rounded-2xl border border-purple-100 shadow-sm">
      <span class="text-xs font-semibold text-muted px-1">{{ selected.length }} selected:</span>
      <button @click="bulkAction('activate')" class="bulk-btn text-success">Activate</button>
      <button @click="bulkAction('deactivate')" class="bulk-btn text-error">Deactivate</button>
      <button @click="bulkAction('feature')" class="bulk-btn text-primary">Feature</button>
      <button @click="bulkAction('unfeature')" class="bulk-btn text-charcoal">Unfeature</button>
      <div class="flex items-center gap-1.5">
        <SelectMenu v-model="bulkCategory" :options="categoryOptions" placeholder="Move to category…" trigger-class="text-xs py-1.5" class="w-44" />
        <button @click="bulkAction('set_category')" :disabled="!bulkCategory" class="bulk-btn text-primary disabled:opacity-40">Move</button>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-purple-50/50">
            <tr>
              <th v-if="canDelete" class="px-5 py-4 w-10"><input type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
              <th class="text-left px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Product</th>
              <th class="text-left px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Category</th>
              <th class="text-left px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Price</th>
              <th class="text-left px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Stock</th>
              <th class="text-left px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Status</th>
              <th class="text-right px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="product in products.data" :key="product.id"
              class="hover:bg-purple-50/20 transition-colors" :class="selected.includes(product.id) ? 'bg-purple-50/40' : ''">
              <td v-if="canDelete" class="px-5 py-4"><input type="checkbox" :value="product.id" v-model="selected" class="kz-check"></td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <img :src="product.featured_image_url" :alt="product.name"
                    class="w-12 h-12 object-cover rounded-xl bg-purple-50">
                  <div>
                    <p class="font-medium text-charcoal text-sm">{{ product.name }}</p>
                    <p class="text-xs text-muted">{{ product.sku || product.slug }}</p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 text-sm text-muted">{{ product.category?.name }}</td>
              <td class="px-5 py-4">
                <p class="font-semibold text-charcoal text-sm">৳{{ Number(product.price).toFixed(2) }}</p>
                <p v-if="product.discount_price" class="text-xs text-success">৳{{ Number(product.discount_price).toFixed(2) }}</p>
              </td>
              <td class="px-5 py-4">
                <span :class="product.stock > 10 ? 'badge-success' : product.stock > 0 ? 'badge-warning' : 'badge-error'"
                  class="badge text-xs">{{ product.stock }}</span>
              </td>
              <td class="px-5 py-4">
                <span :class="product.is_active ? 'badge-success' : 'badge-error'" class="badge text-xs">
                  {{ product.is_active ? 'Active' : 'Inactive' }}
                </span>
                <span v-if="product.is_featured" class="badge badge-purple text-xs ml-1">Featured</span>
              </td>
              <td class="px-5 py-4">
                <div class="flex items-center justify-end gap-2">
                  <a :href="route('shop.show', product.slug)" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1 text-xs text-charcoal px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <EyeIcon class="w-3.5 h-3.5" /> View
                  </a>
                  <button @click="openEdit(product)"
                    class="text-xs text-primary px-3 py-1.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    Edit
                  </button>
                  <button v-if="canCreate" @click="duplicateProduct(product)"
                    class="text-xs text-charcoal px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Duplicate
                  </button>
                  <button @click="deleteProduct(product)"
                    class="text-xs text-error px-3 py-1.5 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data?.length">
              <td :colspan="canDelete ? 7 : 6" class="px-5 py-12 text-center text-muted text-sm">
                No products found.
                <Link :href="route('admin.products.create')" class="text-primary hover:underline">Add your first product</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="products.last_page > 1" class="flex justify-center gap-2 p-5 border-t border-purple-50">
        <template v-for="link in products.links" :key="link.label">
          <Link v-if="link.url" :href="link.url"
            :class="link.active ? 'bg-primary text-white' : 'bg-white text-charcoal hover:bg-purple-50'"
            class="px-4 py-2 rounded-xl text-sm font-medium border border-purple-100 transition-colors"
            v-html="link.label">
          </Link>
        </template>
      </div>
    </div>

    <!-- ── Edit Product Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showEdit" class="fixed inset-0 z-50 flex">

          <!-- Backdrop -->
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeEdit"></div>

          <!-- Drawer panel -->
          <div class="relative ml-auto w-full max-w-2xl h-full bg-white shadow-2xl flex flex-col overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-purple-50 shrink-0">
              <div>
                <h2 class="font-semibold text-charcoal text-lg">Edit Product</h2>
                <p class="text-muted text-xs mt-0.5 truncate max-w-xs">{{ editForm.name }}</p>
              </div>
              <button @click="closeEdit" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center transition-colors text-muted hover:text-charcoal">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Scrollable body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">

              <!-- Current Image -->
              <div class="flex items-center gap-4 p-4 bg-purple-50 rounded-2xl">
                <img v-if="editFeaturedUrl" :src="editFeaturedUrl" alt="Product" class="w-20 h-20 object-cover rounded-xl shrink-0">
                <div v-else class="w-20 h-20 rounded-xl bg-white flex items-center justify-center shrink-0">
                  <PhotoIcon class="w-7 h-7 text-primary/30" />
                </div>
                <div>
                  <p class="font-medium text-charcoal text-sm">Product Image</p>
                  <button type="button" @click="pickerMain = true" class="text-primary text-xs hover:underline mt-1">
                    {{ editFeaturedUrl ? 'Change image' : 'Choose image' }}
                  </button>
                </div>
              </div>

              <!-- ── Gallery Images — directly below featured image ── -->
              <div class="bg-purple-50/50 rounded-2xl p-4">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-sm font-semibold text-charcoal">Gallery Images</p>
                  <span v-if="galleryLoading" class="text-xs text-muted">Loading…</span>
                  <span v-else class="text-xs text-muted bg-white px-2 py-0.5 rounded-full">
                    {{ galleryDisplayCount }} image{{ galleryDisplayCount !== 1 ? 's' : '' }}
                  </span>
                </div>
                <p class="text-xs text-muted mb-3">JPG, PNG, WebP · Max 2 MB each · Drag to reorder</p>

                <!-- Saved thumbnails -->
                <div v-if="editGallery.length" class="grid grid-cols-5 gap-2 mb-3">
                  <div v-for="(img, i) in editGallery" :key="img.id"
                    draggable="true"
                    @dragstart="savedDrag.onDragStart(i, $event)" @dragover="savedDrag.onDragOver(i, $event)"
                    @drop="savedDrag.onDrop(i, $event)" @dragend="savedDrag.onDragEnd"
                    class="relative group aspect-square rounded-lg overflow-hidden bg-white cursor-grab active:cursor-grabbing"
                    :class="[
                      editDeleteIds.includes(img.id) ? 'opacity-40 ring-2 ring-error' : '',
                      savedDrag.dragIndex.value === i ? 'opacity-50' : '',
                      savedDrag.overIndex.value === i && savedDrag.dragIndex.value !== i ? 'ring-2 ring-primary' : '',
                    ]">
                    <img :src="img.image_url" alt="" draggable="false" class="w-full h-full object-cover">
                    <button type="button" @click="toggleGalleryDelete(img.id)"
                      class="absolute inset-0 flex items-center justify-center bg-black/50
                             opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                      <div class="flex flex-col items-center gap-0.5 text-white">
                        <TrashIcon v-if="!editDeleteIds.includes(img.id)" class="w-3.5 h-3.5" />
                        <ArrowUturnLeftIcon v-else class="w-3.5 h-3.5" />
                        <span class="text-[9px]">{{ editDeleteIds.includes(img.id) ? 'Undo' : 'Remove' }}</span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Newly selected from library -->
                <div v-if="editGalleryMedia.length" class="grid grid-cols-5 gap-2 mb-3">
                  <div v-for="(m, i) in editGalleryMedia" :key="m.id"
                    draggable="true"
                    @dragstart="newDrag.onDragStart(i, $event)" @dragover="newDrag.onDragOver(i, $event)"
                    @drop="newDrag.onDrop(i, $event)" @dragend="newDrag.onDragEnd"
                    class="relative group aspect-square rounded-lg overflow-hidden bg-white cursor-grab active:cursor-grabbing"
                    :class="[
                      newDrag.dragIndex.value === i ? 'opacity-50' : '',
                      newDrag.overIndex.value === i && newDrag.dragIndex.value !== i ? 'ring-2 ring-primary' : '',
                    ]">
                    <img :src="m.file_url" alt="" draggable="false" class="w-full h-full object-cover">
                    <button type="button" @click="editGalleryMedia.splice(i, 1)"
                      class="absolute inset-0 flex items-center justify-center bg-black/50
                             opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                      <XMarkIcon class="w-4 h-4 text-white" />
                    </button>
                  </div>
                </div>

                <!-- Choose from library -->
                <button type="button" @click="pickerGallery = true"
                  class="w-full border-2 border-dashed border-purple-200 rounded-xl p-3 text-center hover:border-primary transition-colors bg-white">
                  <PhotoIcon class="w-5 h-5 text-primary/40 mx-auto mb-1" />
                  <p class="text-xs text-primary font-medium">Choose Images</p>
                </button>
                <p v-if="editDeleteIds.length" class="text-xs text-error mt-1.5">
                  {{ editDeleteIds.length }} image{{ editDeleteIds.length !== 1 ? 's' : '' }} will be removed on save.
                </p>
              </div>

              <!-- Media pickers -->
              <MediaPicker :open="pickerMain" @close="pickerMain = false" @select="onEditMainSelect" />
              <MediaPicker :open="pickerGallery" multiple @close="pickerGallery = false" @select="onEditGallerySelect" />

              <!-- Name + Category -->
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Product Name *</label>
                  <input v-model="editForm.name" type="text" class="input-field" required>
                  <p v-if="editErrors.name" class="text-error text-xs mt-1">{{ editErrors.name }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Category *</label>
                  <SelectMenu v-model="editForm.category_id" :options="categoryOptions" placeholder="Select Category" />
                </div>
              </div>

              <!-- Slug -->
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Slug (URL)</label>
                <div class="flex items-center gap-2">
                  <span class="text-muted text-sm shrink-0">/shop/</span>
                  <input v-model="editForm.slug" type="text" class="input-field" placeholder="auto-generated-from-name">
                </div>
                <p v-if="editErrors.slug" class="text-error text-xs mt-1">{{ editErrors.slug }}</p>
                <p v-else class="text-xs text-muted mt-1">Editable. Leave blank to regenerate from the name. Must be unique.</p>
              </div>

              <!-- Short Description -->
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Short Description</label>
                <textarea v-model="editForm.short_description" rows="2" class="input-field" placeholder="Brief description"></textarea>
              </div>

              <!-- Description -->
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Full Description</label>
                <textarea v-model="editForm.description" rows="4" class="input-field" placeholder="Detailed description"></textarea>
              </div>

              <!-- Tags -->
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Tags</label>
                <input v-model="editForm.tagsInput" type="text" class="input-field" placeholder="e.g. black, leather, waterproof">
                <p class="text-xs text-muted mt-1">Comma separated — used for shop filters and recommendations.</p>
              </div>

              <!-- USPs -->
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Key selling points (USP)</label>
                <div class="space-y-2">
                  <div v-for="(u, i) in editForm.usps" :key="i" class="flex gap-2 items-start rounded-lg border border-purple-100 bg-purple-50/40 p-2">
                    <div class="flex-1 space-y-1.5 min-w-0">
                      <input v-model="u.title" type="text" class="input-field" placeholder="Title — e.g. 25 L capacity">
                      <input v-model="u.text" type="text" class="input-field text-sm" placeholder="Short line (optional)">
                    </div>
                    <button type="button" @click="editForm.usps.splice(i, 1)" aria-label="Remove"
                      class="mt-1 w-8 h-8 rounded-lg border border-purple-100 bg-white text-error flex items-center justify-center shrink-0 hover:bg-error/5">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
                <button type="button" @click="editForm.usps.push({ title: '', text: '' })"
                  class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-accent transition-colors">
                  <PlusIcon class="w-4 h-4" /> Add selling point
                </button>
              </div>

              <!-- Pricing + Inventory -->
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Price (৳) *</label>
                  <input v-model="editForm.price" type="number" step="0.01" class="input-field" required>
                  <p v-if="editErrors.price" class="text-error text-xs mt-1">{{ editErrors.price }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Sale Price (৳)</label>
                  <input v-model="editForm.discount_price" type="number" step="0.01" class="input-field" placeholder="Optional">
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Stock *</label>
                  <input v-model="editForm.stock" type="number" class="input-field" required>
                  <p v-if="editErrors.stock" class="text-error text-xs mt-1">{{ editErrors.stock }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">SKU</label>
                  <input v-model="editForm.sku" type="text" class="input-field" placeholder="e.g. KZ-BP-004">
                  <p v-if="editErrors.sku" class="text-error text-xs mt-1">{{ editErrors.sku }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Sale starts</label>
                  <input v-model="editForm.sale_starts_at" type="date" class="input-field">
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Sale ends</label>
                  <input v-model="editForm.sale_ends_at" type="date" class="input-field">
                  <p v-if="editErrors.sale_ends_at" class="text-error text-xs mt-1">{{ editErrors.sale_ends_at }}</p>
                </div>
              </div>

              <!-- SEO -->
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Meta title (SEO)</label>
                  <input v-model="editForm.meta_title" type="text" maxlength="255" class="input-field" placeholder="Empty = product name">
                </div>
                <div>
                  <label class="text-sm font-medium text-charcoal mb-1.5 block">Meta description (SEO)</label>
                  <input v-model="editForm.meta_description" type="text" maxlength="500" class="input-field" placeholder="Empty = short description">
                </div>
              </div>

              <!-- Variations -->
              <VariationsEditor v-model:enabled="editForm.has_variations" v-model:items="editForm.variations" />

              <!-- Toggles -->
              <div class="flex flex-wrap gap-x-6 gap-y-3">
                <label class="flex items-center gap-2.5 cursor-pointer">
                  <input v-model="editForm.is_active" type="checkbox" class="rounded text-primary w-4 h-4">
                  <span class="text-sm font-medium text-charcoal">Active</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer">
                  <input v-model="editForm.is_featured" type="checkbox" class="rounded text-primary w-4 h-4">
                  <span class="text-sm font-medium text-charcoal">Featured</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer">
                  <input v-model="editForm.show_on_hero" type="checkbox" class="rounded text-primary w-4 h-4">
                  <div>
                    <p class="text-sm font-medium text-charcoal leading-tight">New Arrival</p>
                    <p class="text-xs text-muted">Homepage New Arrival section</p>
                  </div>
                </label>
              </div>


            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-purple-50 flex gap-3 shrink-0 bg-white">
              <button @click="closeEdit" class="btn-secondary flex-1 py-2.5 text-sm">Cancel</button>
              <button @click="submitEdit" :disabled="editProcessing" class="btn-primary flex-1 py-2.5 text-sm">
                {{ editProcessing ? 'Saving…' : 'Save Changes' }}
              </button>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

    <BulkDeleteBar :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected = []" />

    <!-- ── CSV Import Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="importOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="importBusy ? null : (importOpen = false)"></div>
          <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-semibold text-charcoal text-lg">Import products from CSV</h2>
              <button @click="importOpen = false" :disabled="importBusy" class="w-9 h-9 rounded-full hover:bg-purple-50 flex items-center justify-center text-muted hover:text-charcoal">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <p class="text-xs text-muted mb-4">
              Rows are matched by <b>SKU</b>: existing SKUs are updated, unknown ones are created (name, category &amp; price required).
              Columns: <code class="text-[11px]">sku, name, category, price, discount_price, sale_starts_at, sale_ends_at, stock, is_active, is_featured, tags, short_description</code>.
              Use <b>Export CSV</b> to get a template with your current products.
            </p>

            <input ref="importInput" type="file" accept=".csv,text/csv" @change="onImportFile"
              class="block w-full text-sm text-muted file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-purple-50 file:text-primary file:text-xs file:font-semibold hover:file:bg-purple-100 mb-4">

            <!-- Preview result -->
            <div v-if="importPreview" class="rounded-xl border p-3 mb-4 text-sm"
              :class="importPreview.error_count ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50'">
              <p class="font-semibold text-charcoal mb-1">
                Preview: {{ importPreview.updated }} will be updated · {{ importPreview.created }} will be created
                <span v-if="importPreview.error_count" class="text-error">· {{ importPreview.error_count }} row(s) skipped</span>
              </p>
              <ul v-if="importPreview.errors?.length" class="text-xs text-error/90 list-disc ml-4 space-y-0.5 max-h-28 overflow-y-auto">
                <li v-for="(e, i) in importPreview.errors" :key="i">{{ e }}</li>
              </ul>
            </div>

            <div class="flex gap-3">
              <button @click="runImport(true)" :disabled="!importFile || importBusy" class="btn-secondary flex-1 py-2.5 text-sm disabled:opacity-50">
                {{ importBusy && importDry ? 'Checking…' : 'Preview' }}
              </button>
              <button @click="runImport(false)" :disabled="!importFile || importBusy || !importPreview" class="btn-primary flex-1 py-2.5 text-sm disabled:opacity-50">
                {{ importBusy && !importDry ? 'Importing…' : 'Import now' }}
              </button>
            </div>
            <p class="text-[11px] text-muted mt-2">Run Preview first — nothing is saved until you press "Import now".</p>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import VariationsEditor from '@/Components/admin/VariationsEditor.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { useToast } from 'vue-toastification'
import { usePermissions } from '@/composables/usePermissions'
import { useDragReorder } from '@/composables/useDragReorder'
import axios from 'axios'
import { XMarkIcon, TrashIcon, ArrowUturnLeftIcon, PhotoIcon, PlusIcon, EyeIcon, MagnifyingGlassIcon, ArrowDownTrayIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  products: Object,
  categories: Array,
  filters: { type: Object, default: () => ({}) },
})

const categoryOptions = computed(() => (props.categories || []).map(c => ({ value: c.id, label: c.name })))
const categorySelect = computed(() => [{ value: '', label: 'All categories' }, ...categoryOptions.value])

// Search + category + date filters
const f = ref({
  search: props.filters?.search || '',
  category: props.filters?.category || '',
  range: props.filters?.range, from: props.filters?.from, to: props.filters?.to,
})
let _t
const apply = () => router.get(route('admin.products.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(_t); _t = setTimeout(apply, 350) }

// Bulk selection / delete
const toast = useToast()
const { can } = usePermissions()
const canDelete = can('products.delete')
const canCreate = can('products.create')
const canEdit   = can('products.edit')
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.products.data?.length > 0 && selected.value.length === props.products.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.products.data.map((p) => p.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.products.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { toast.success(`${selected.value.length} product(s) deleted.`); selected.value = []; router.reload({ only: ['products'] }) },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

// ── Bulk actions (beyond delete) ──
const bulkCategory = ref('')
const bulkAction = (action) => {
  const payload = { ids: selected.value, action }
  if (action === 'set_category') {
    if (!bulkCategory.value) return
    payload.category_id = bulkCategory.value
  }
  router.post(route('admin.products.bulk-update'), payload, {
    preserveScroll: true,
    onSuccess: () => { selected.value = []; bulkCategory.value = ''; router.reload({ only: ['products'] }) },
    onError: () => toast.error('Bulk update failed.'),
  })
}

// ── Duplicate ──
const duplicateProduct = (product) => {
  router.post(route('admin.products.duplicate', product.id), {}, { preserveScroll: true })
}

// ── CSV import ──
const importOpen    = ref(false)
const importFile    = ref(null)
const importPreview = ref(null)
const importBusy    = ref(false)
const importDry     = ref(true)
const importInput   = ref(null)

const openImport = () => { importOpen.value = true; importPreview.value = null; importFile.value = null }
const onImportFile = (e) => { importFile.value = e.target.files?.[0] || null; importPreview.value = null }

const runImport = async (dryRun) => {
  if (!importFile.value || importBusy.value) return
  importBusy.value = true
  importDry.value = dryRun
  try {
    const fd = new FormData()
    fd.append('file', importFile.value)
    fd.append('dry_run', dryRun ? 1 : 0)
    const { data } = await axios.post(route('admin.products.import'), fd)
    if (dryRun) {
      importPreview.value = data
    } else {
      toast.success(`Imported — ${data.updated} updated, ${data.created} created` + (data.error_count ? `, ${data.error_count} skipped.` : '.'))
      importOpen.value = false
      router.reload({ only: ['products'] })
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Import failed — check the file format.')
  } finally {
    importBusy.value = false
  }
}

// ── Delete ──
const deleteProduct = (product) => {
  if (!confirm(`Delete "${product.name}"? This cannot be undone.`)) return
  router.delete(route('admin.products.destroy', product.id), {
    onSuccess: () => {}, // FlashMessage handles success toast via server flash
  })
}

// ── Edit Modal ──
const showEdit        = ref(false)
const editProcessing  = ref(false)
const editErrors      = ref({})

// Media picker state
const pickerMain      = ref(false)
const pickerGallery   = ref(false)
const editFeaturedPath = ref(null)
const editFeaturedUrl  = ref(null)

// Gallery state
const galleryLoading   = ref(false)
const editGallery      = ref([])       // existing images from server
const editDeleteIds    = ref([])       // IDs marked for removal
const editGalleryMedia = ref([])       // newly selected media files

const galleryDisplayCount = computed(() =>
  (editGallery.value.length - editDeleteIds.value.length) + editGalleryMedia.value.length
)

// Drag & drop sequencing — saved images persist via gallery_order on save
const savedDrag = useDragReorder(editGallery)
const newDrag   = useDragReorder(editGalleryMedia)

// Slug helpers — keep an untouched slug verbatim; only normalize a changed one.
const slugify = (s) => (s || '').toString().toLowerCase().trim()
  .replace(/[^a-z0-9\s-]/g, '').replace(/[\s_]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')
const editOriginalSlug = ref('')

const editForm = ref({
  id: null, name: '', slug: '', sku: '', tagsInput: '', category_id: '', short_description: '',
  description: '', usps: [], has_variations: false, variations: [],
  price: '', discount_price: '', sale_starts_at: '', sale_ends_at: '',
  meta_title: '', meta_description: '',
  stock: 0, is_active: true, is_featured: false, show_on_hero: false,
})

const openEdit = (product) => {
  editErrors.value      = {}
  editOriginalSlug.value = product.slug || ''
  editFeaturedPath.value = null
  editFeaturedUrl.value  = product.featured_image_url || null
  editGallery.value      = []
  editDeleteIds.value    = []
  editGalleryMedia.value = []

  editForm.value = {
    id:                product.id,
    name:              product.name,
    slug:              product.slug || '',
    sku:               product.sku || '',
    tagsInput:         (product.tags || []).join(', '),
    category_id:       product.category_id,
    short_description: product.short_description || '',
    description:       product.description || '',
    usps:              JSON.parse(JSON.stringify(product.usps || [])),
    has_variations:    product.has_variations ?? false,
    variations:        JSON.parse(JSON.stringify(product.variations || [])),
    price:             product.price,
    discount_price:    product.discount_price || '',
    sale_starts_at:    product.sale_starts_at || '',
    sale_ends_at:      product.sale_ends_at || '',
    meta_title:        product.meta_title || '',
    meta_description:  product.meta_description || '',
    stock:             product.stock,
    is_active:         product.is_active,
    is_featured:       product.is_featured,
    show_on_hero:      product.show_on_hero,
  }

  showEdit.value = true
  document.body.style.overflow = 'hidden'

  galleryLoading.value = true
  axios.get(route('admin.products.gallery', product.id))
    .then(res => { editGallery.value = res.data })
    .catch(() => {})
    .finally(() => { galleryLoading.value = false })
}

const onEditMainSelect = (media) => {
  editFeaturedPath.value = media.file_path
  editFeaturedUrl.value  = media.file_url
}

const onEditGallerySelect = (list) => {
  for (const m of list) {
    if (!editGalleryMedia.value.some(x => x.id === m.id)) editGalleryMedia.value.push(m)
  }
}

const toggleGalleryDelete = (id) => {
  const idx = editDeleteIds.value.indexOf(id)
  if (idx === -1) editDeleteIds.value.push(id)
  else editDeleteIds.value.splice(idx, 1)
}

const closeEdit = () => {
  showEdit.value = false
  document.body.style.overflow = ''
}

const submitEdit = () => {
  editProcessing.value = true
  editErrors.value = {}

  const payload = {
    _method: 'PUT',
    name:              editForm.value.name,
    slug:              (editForm.value.slug === editOriginalSlug.value && editOriginalSlug.value)
                         ? editOriginalSlug.value
                         : slugify(editForm.value.slug || editForm.value.name),
    sku:               editForm.value.sku || '',
    tags:              editForm.value.tagsInput.split(',').map(t => t.trim()).filter(Boolean),
    category_id:       editForm.value.category_id,
    short_description: editForm.value.short_description || '',
    description:       editForm.value.description || '',
    usps:              editForm.value.usps,
    has_variations:    editForm.value.has_variations ? 1 : 0,
    variations:        editForm.value.variations,
    price:             editForm.value.price,
    discount_price:    editForm.value.discount_price || '',
    sale_starts_at:    editForm.value.sale_starts_at || '',
    sale_ends_at:      editForm.value.sale_ends_at || '',
    meta_title:        editForm.value.meta_title || '',
    meta_description:  editForm.value.meta_description || '',
    stock:             editForm.value.stock,
    is_active:         editForm.value.is_active   ? 1 : 0,
    is_featured:       editForm.value.is_featured ? 1 : 0,
    show_on_hero:      editForm.value.show_on_hero ? 1 : 0,
    gallery_paths:      editGalleryMedia.value.map(m => m.file_path),
    delete_gallery_ids: editDeleteIds.value,
    gallery_order:      editGallery.value.map(g => g.id),
  }
  if (editFeaturedPath.value) payload.featured_image_path = editFeaturedPath.value

  router.post(route('admin.products.update', editForm.value.id), payload, {
    onSuccess: () => { closeEdit(); editProcessing.value = false },
    onError:   (errs) => { editErrors.value = errs; editProcessing.value = false },
  })
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from,
.modal-leave-to    { opacity: 0; }

.modal-enter-active .relative,
.modal-leave-active .relative { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from .relative    { transform: translateX(100%); }
.modal-leave-to .relative      { transform: translateX(100%); }

.bulk-btn {
  font-size: 12px;
  font-weight: 600;
  padding: 6px 12px;
  background: rgba(245, 243, 255, 0.6);
  border: 1px solid #ede9fe;
  border-radius: 8px;
  transition: background-color 0.15s ease;
}
.bulk-btn:hover:not(:disabled) { background: #ede9fe; }
</style>
