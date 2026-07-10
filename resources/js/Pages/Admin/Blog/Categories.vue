<template>
  <AdminLayout>
    <Head title="Blog Categories" />

    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Blog Categories</h1>
        <p class="text-muted text-sm">Group your posts into topics.</p>
      </div>
      <Link :href="route('admin.blog.index')" class="text-primary text-sm hover:underline">← Back to Posts</Link>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Form -->
      <div class="lg:col-span-1">
        <form @submit.prevent="submit" class="bg-white rounded-2xl border border-purple-50 p-6 space-y-4 sticky top-4">
          <h3 class="font-display font-semibold text-charcoal">{{ editing ? 'Edit Category' : 'Add Category' }}</h3>
          <div>
            <label class="label">Name *</label>
            <input v-model="form.name" @input="onName" type="text" class="input-field" required>
            <p v-if="errors.name" class="text-error text-xs mt-1">{{ errors.name }}</p>
          </div>
          <div>
            <label class="label">Slug</label>
            <input v-model="form.slug" @input="slugTouched = true" type="text" class="input-field" placeholder="auto">
            <p v-if="errors.slug" class="text-error text-xs mt-1">{{ errors.slug }}</p>
          </div>
          <div>
            <label class="label">Description</label>
            <textarea v-model="form.description" rows="2" class="input-field"></textarea>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex-1">
              <label class="label">Sort order</label>
              <input v-model.number="form.sort_order" type="number" min="0" class="input-field">
            </div>
            <label class="flex items-center gap-2 mt-6 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="rounded text-primary">
              <span class="text-sm text-charcoal">Active</span>
            </label>
          </div>
          <div class="flex items-center gap-2 pt-1">
            <button type="submit" :disabled="processing" class="btn-primary flex-1 py-2.5">{{ editing ? 'Update' : 'Add' }}</button>
            <button v-if="editing" type="button" @click="reset" class="btn-secondary px-4 py-2.5">Cancel</button>
          </div>
        </form>
      </div>

      <!-- List -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs uppercase tracking-wider text-muted border-b border-purple-50">
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3 hidden sm:table-cell">Posts</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in categories" :key="c.id" class="border-b border-purple-50/60 hover:bg-purple-50/30">
                <td class="px-4 py-3">
                  <p class="font-medium text-charcoal">{{ c.name }}</p>
                  <p class="text-xs text-muted">/blog/category/{{ c.slug }}</p>
                </td>
                <td class="px-4 py-3 hidden sm:table-cell text-muted">{{ c.posts_count }}</td>
                <td class="px-4 py-3">
                  <span :class="c.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2.5 py-1 rounded-full text-xs font-semibold">{{ c.is_active ? 'Active' : 'Hidden' }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="startEdit(c)" class="icon-act" title="Edit"><PencilSquareIcon class="w-4 h-4" /></button>
                    <button @click="destroy(c)" class="icon-act text-rose-500" title="Delete"><TrashIcon class="w-4 h-4" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!categories.length"><td colspan="4" class="px-4 py-14 text-center text-muted">No categories yet.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ categories: { type: Array, default: () => [] } })

const blank = () => ({ name: '', slug: '', description: '', sort_order: 0, is_active: true })
const form = ref(blank())
const editing = ref(null)
const errors = ref({})
const processing = ref(false)

const slugify = (s) => (s || '').toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s_]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')
const slugTouched = ref(false)
const onName = () => { if (!slugTouched.value) form.value.slug = slugify(form.value.name) }

const reset = () => { form.value = blank(); editing.value = null; errors.value = {}; slugTouched.value = false }
const startEdit = (c) => { editing.value = c.id; slugTouched.value = true; errors.value = {}; form.value = { name: c.name, slug: c.slug, description: c.description || '', sort_order: c.sort_order, is_active: c.is_active } }

const submit = () => {
  processing.value = true; errors.value = {}
  const opts = { preserveScroll: true, onError: (e) => { errors.value = e }, onFinish: () => { processing.value = false }, onSuccess: () => reset() }
  if (editing.value) router.put(route('admin.blog-categories.update', editing.value), form.value, opts)
  else router.post(route('admin.blog-categories.store'), form.value, opts)
}
const destroy = (c) => {
  if (confirm(`Delete “${c.name}”? Posts in it will become uncategorized.`)) {
    router.delete(route('admin.blog-categories.destroy', c.id), { preserveScroll: true })
  }
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.label { @apply text-sm font-medium text-charcoal mb-1.5 block; }
.icon-act { @apply w-8 h-8 rounded-lg bg-white border border-purple-100 flex items-center justify-center text-muted hover:text-ink transition-colors; }
</style>
