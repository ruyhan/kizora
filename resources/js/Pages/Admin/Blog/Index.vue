<template>
  <AdminLayout>
    <Head title="Blog Posts" />

    <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Blog Posts</h1>
        <p class="text-muted text-sm">Write, edit and publish articles.</p>
      </div>
      <div class="flex items-center gap-2">
        <Link :href="route('admin.blog-categories.index')" class="btn-secondary text-sm px-4 py-2.5">Categories</Link>
        <Link :href="route('admin.blog.create')" class="btn-primary text-sm px-4 py-2.5 inline-flex items-center gap-1.5">
          <PlusIcon class="w-4 h-4" /> New Post
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-purple-50 p-4 mb-5 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-52">
        <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
        <input v-model="form.search" @input="debouncedFilter" type="text" placeholder="Search posts…" class="input-field pl-10 py-2.5 text-sm">
      </div>
      <select v-model="form.category" @change="applyFilter" class="input-field py-2.5 text-sm w-auto min-w-40">
        <option value="">All categories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="form.status" @change="applyFilter" class="input-field py-2.5 text-sm w-auto min-w-36">
        <option value="">All status</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
      </select>
    </div>

    <!-- Bulk bar -->
    <div v-if="selected.length" class="flex items-center justify-between bg-ink text-white rounded-xl px-4 py-2.5 mb-3 text-sm">
      <span>{{ selected.length }} selected</span>
      <button @click="bulkDelete" class="inline-flex items-center gap-1.5 hover:text-rose-300"><TrashIcon class="w-4 h-4" /> Delete</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wider text-muted border-b border-purple-50">
              <th class="px-4 py-3 w-8"><input type="checkbox" :checked="allChecked" @change="toggleAll"></th>
              <th class="px-4 py-3">Post</th>
              <th class="px-4 py-3 hidden md:table-cell">Category</th>
              <th class="px-4 py-3 hidden lg:table-cell">Author</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3 hidden sm:table-cell">Date</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in posts.data" :key="p.id" class="border-b border-purple-50/60 hover:bg-purple-50/30">
              <td class="px-4 py-3"><input type="checkbox" :value="p.id" v-model="selected"></td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg bg-purple-50 overflow-hidden shrink-0">
                    <img v-if="p.featured_image_url" :src="p.featured_image_url" class="w-full h-full object-cover" alt="">
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-charcoal truncate max-w-xs">{{ p.title }}</p>
                    <p class="text-xs text-muted flex items-center gap-1"><StarIcon v-if="p.is_featured" class="w-3 h-3 text-amber-500" /> /blog/{{ p.slug }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 hidden md:table-cell text-muted">{{ p.category?.name || '—' }}</td>
              <td class="px-4 py-3 hidden lg:table-cell text-muted">{{ p.author || '—' }}</td>
              <td class="px-4 py-3">
                <span :class="p.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                  class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">{{ p.status }}</span>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell text-muted whitespace-nowrap">{{ p.published_at || p.created_at }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <a :href="`/blog/${p.slug}`" target="_blank" class="icon-act" title="View"><EyeIcon class="w-4 h-4" /></a>
                  <Link :href="route('admin.blog.edit', p.id) + listQuery()" class="icon-act" title="Edit"><PencilSquareIcon class="w-4 h-4" /></Link>
                  <button @click="destroy(p)" class="icon-act text-rose-500" title="Delete"><TrashIcon class="w-4 h-4" /></button>
                </div>
              </td>
            </tr>
            <tr v-if="!posts.data.length"><td colspan="7" class="px-4 py-16 text-center text-muted">No blog posts yet. Click “New Post” to write your first one.</td></tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="posts.links" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { PlusIcon, MagnifyingGlassIcon, TrashIcon, PencilSquareIcon, EyeIcon, StarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  posts: Object,
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const form = ref({
  search: props.filters.search || '',
  category: props.filters.category || '',
  status: props.filters.status || '',
})
const selected = ref([])

const allChecked = computed(() => props.posts.data.length > 0 && selected.value.length === props.posts.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.posts.data.map(p => p.id) : [] }

let timer = null
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 350) }
const applyFilter = () => {
  router.get(route('admin.blog.index'), { ...form.value }, { preserveState: true, replace: true, preserveScroll: true })
}

// Carry the current list URL query (?page=…, search, filters) onto the edit
// link so "Update" can send the user back to the exact same list page.
const listQuery = () => window.location.search || ''

const destroy = (p) => {
  if (confirm(`Delete “${p.title}”? This cannot be undone.`)) {
    router.delete(route('admin.blog.destroy', p.id), { preserveScroll: true })
  }
}
const bulkDelete = () => {
  if (confirm(`Delete ${selected.value.length} post(s)?`)) {
    router.post(route('admin.blog.bulk-destroy'), { ids: selected.value }, {
      preserveScroll: true, onSuccess: () => { selected.value = [] },
    })
  }
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.icon-act { @apply w-8 h-8 rounded-lg bg-white border border-purple-100 flex items-center justify-center text-muted hover:text-ink transition-colors; }
</style>
