<template>
  <AdminLayout>
    <Head :title="post ? 'Edit Post' : 'New Post'" />

    <div class="flex items-center justify-between mb-6">
      <h1 class="font-display text-2xl font-bold text-charcoal">{{ post ? 'Edit Post' : 'New Blog Post' }}</h1>
      <Link :href="route('admin.blog.index', listQuery)" class="text-primary text-sm hover:underline">← Back to Posts</Link>
    </div>

    <form @submit.prevent="submit">
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main -->
        <div class="lg:col-span-2 space-y-5">
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6 space-y-4">
            <div>
              <label class="label">Title *</label>
              <input v-model="form.title" @input="onTitleInput" type="text" class="input-field" required>
              <p v-if="errors.title" class="text-error text-xs mt-1">{{ errors.title }}</p>
            </div>
            <div>
              <label class="label">Slug (URL)</label>
              <div class="flex items-center gap-2">
                <span class="text-muted text-sm shrink-0">/blog/</span>
                <input v-model="form.slug" @input="slugTouched = true" type="text" class="input-field" placeholder="auto-generated-from-title">
              </div>
              <p v-if="errors.slug" class="text-error text-xs mt-1">{{ errors.slug }}</p>
              <p v-else class="text-xs text-muted mt-1">Editable. Leave blank to auto-generate. Must be unique.</p>
            </div>
            <div>
              <label class="label">Excerpt</label>
              <textarea v-model="form.excerpt" rows="2" maxlength="500" class="input-field" placeholder="Short summary shown on the blog listing cards."></textarea>
            </div>
          </div>

          <!-- Content -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <label class="label mb-2">Content</label>
            <RichTextEditor v-model="form.content" />
          </div>

          <!-- Content images -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h3 class="font-display font-semibold text-charcoal">Content Images</h3>
                <p class="text-xs text-muted">Extra images from the Media Library (a gallery shown under the article).</p>
              </div>
              <button type="button" @click="pickContent = true" class="btn-secondary text-sm px-3 py-2">Add images</button>
            </div>
            <div v-if="form.content_images.length" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
              <div v-for="(img, i) in form.content_images" :key="i" class="relative group aspect-square rounded-lg overflow-hidden bg-purple-50">
                <img :src="img" class="w-full h-full object-cover" alt="">
                <button type="button" @click="form.content_images.splice(i, 1)"
                  class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                  <XMarkIcon class="w-4 h-4 text-white" />
                </button>
              </div>
            </div>
            <p v-else class="text-sm text-muted">No content images added.</p>
          </div>

          <!-- SEO -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6 space-y-4">
            <div>
              <h3 class="font-display font-semibold text-charcoal">SEO Settings</h3>
              <p class="text-xs text-muted">How this post appears in search results and social shares.</p>
            </div>
            <div>
              <label class="label">Meta Title</label>
              <input v-model="form.meta_title" type="text" maxlength="255" class="input-field" placeholder="Defaults to the post title">
            </div>
            <div>
              <label class="label">Meta Description</label>
              <textarea v-model="form.meta_description" rows="2" maxlength="500" class="input-field" placeholder="Defaults to the excerpt"></textarea>
            </div>
            <div>
              <label class="label">Meta Keywords</label>
              <input v-model="form.meta_keywords" type="text" maxlength="255" class="input-field" placeholder="comma, separated, keywords">
            </div>
            <div>
              <label class="label">Open Graph Image</label>
              <div class="flex items-center gap-3">
                <div class="w-24 h-16 rounded-lg border border-purple-100 bg-purple-50/40 overflow-hidden shrink-0 flex items-center justify-center">
                  <img v-if="form.og_image" :src="form.og_image" class="w-full h-full object-cover" alt="">
                  <PhotoIcon v-else class="w-5 h-5 text-muted/40" />
                </div>
                <div class="flex items-center gap-2">
                  <button type="button" @click="pickOg = true" class="btn-secondary text-sm px-3 py-2">{{ form.og_image ? 'Change' : 'Choose image' }}</button>
                  <button v-if="form.og_image" type="button" @click="form.og_image = ''" class="text-error text-sm hover:underline">Remove</button>
                </div>
              </div>
              <p class="text-xs text-muted mt-1">Falls back to the featured image if empty.</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5">
          <!-- Publish -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6 space-y-4">
            <h3 class="font-display font-semibold text-charcoal">Publish</h3>
            <div>
              <label class="label">Status</label>
              <select v-model="form.status" class="input-field">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
              </select>
            </div>
            <div>
              <label class="label">Publish Date</label>
              <input v-model="form.published_at" type="date" class="input-field">
              <p class="text-xs text-muted mt-1">Empty = now when first published.</p>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
              <input v-model="form.is_featured" type="checkbox" class="rounded text-primary">
              <span class="text-sm text-charcoal font-medium">Featured post</span>
            </label>
            <button type="submit" :disabled="processing" class="btn-primary w-full py-3">
              {{ processing ? 'Saving…' : (post ? 'Save Changes' : 'Create Post') }}
            </button>
          </div>

          <!-- Featured image -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6">
            <h3 class="font-display font-semibold text-charcoal mb-3">Featured Image</h3>
            <div v-if="form.featured_image" class="mb-3 rounded-xl overflow-hidden">
              <img :src="form.featured_image" class="w-full aspect-video object-cover" alt="">
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="pickFeatured = true" class="btn-secondary text-sm px-3 py-2">{{ form.featured_image ? 'Change' : 'Choose image' }}</button>
              <button v-if="form.featured_image" type="button" @click="form.featured_image = ''" class="text-error text-sm hover:underline">Remove</button>
            </div>
          </div>

          <!-- Organize -->
          <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-6 space-y-4">
            <h3 class="font-display font-semibold text-charcoal">Organize</h3>
            <div>
              <label class="label">Category</label>
              <select v-model="form.blog_category_id" class="input-field">
                <option :value="null">— None —</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Tags</label>
              <input v-model="tagsInput" type="text" class="input-field" list="blog-tags" placeholder="e.g. travel, gear, tips">
              <datalist id="blog-tags"><option v-for="t in allTags" :key="t" :value="t" /></datalist>
              <p class="text-xs text-muted mt-1">Comma separated — new tags are created automatically.</p>
            </div>
            <div>
              <label class="label">Author</label>
              <input v-model="form.author_name" type="text" class="input-field" :placeholder="'Defaults to you'">
            </div>
            <div>
              <label class="label">Reading Time (minutes)</label>
              <input v-model.number="form.reading_time" type="number" min="0" class="input-field" placeholder="Auto-estimated if empty">
            </div>
          </div>
        </div>
      </div>
    </form>

    <MediaPicker :open="pickFeatured" @close="pickFeatured = false" @select="m => { form.featured_image = m.file_url; pickFeatured = false }" />
    <MediaPicker :open="pickOg" @close="pickOg = false" @select="m => { form.og_image = m.file_url; pickOg = false }" />
    <MediaPicker :open="pickContent" multiple @close="pickContent = false" @select="onContentImages" />
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import RichTextEditor from '@/Components/ui/RichTextEditor.vue'
import { PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  post: Object,
  categories: { type: Array, default: () => [] },
  allTags: { type: Array, default: () => [] },
})

const errors = ref({})
const processing = ref(false)
const pickFeatured = ref(false)
const pickOg = ref(false)
const pickContent = ref(false)

const form = ref({
  blog_category_id: props.post?.blog_category_id ?? null,
  title:            props.post?.title || '',
  slug:             props.post?.slug || '',
  author_name:      props.post?.author_name || '',
  excerpt:          props.post?.excerpt || '',
  content:          props.post?.content || '',
  featured_image:   props.post?.featured_image || '',
  content_images:   [...(props.post?.content_images || [])],
  meta_title:       props.post?.meta_title || '',
  meta_description: props.post?.meta_description || '',
  meta_keywords:    props.post?.meta_keywords || '',
  og_image:         props.post?.og_image || '',
  status:           props.post?.status || 'draft',
  is_featured:      props.post?.is_featured ?? false,
  reading_time:     props.post?.reading_time ?? null,
  published_at:     props.post?.published_at || '',
})

const tagsInput = ref((props.post?.tags || []).join(', '))

// List query (?page=…, search, filters) carried over from the Index edit link,
// so after Update the user lands back on the exact list page they came from.
const listQuery = Object.fromEntries(new URLSearchParams(window.location.search))

const slugify = (s) => (s || '').toString().toLowerCase().trim()
  .replace(/[^a-z0-9\s-]/g, '').replace(/[\s_]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')
const slugTouched = ref(!!props.post)
const onTitleInput = () => { if (!slugTouched.value) form.value.slug = slugify(form.value.title) }

const onContentImages = (list) => {
  for (const m of list) if (!form.value.content_images.includes(m.file_url)) form.value.content_images.push(m.file_url)
  pickContent.value = false
}

const submit = () => {
  processing.value = true
  errors.value = {}
  const payload = { ...form.value }
  payload.slug = slugify(payload.slug || payload.title)
  payload.tags = tagsInput.value.split(',').map(t => t.trim()).filter(Boolean)
  payload._list = listQuery

  const url = props.post ? route('admin.blog.update', props.post.id) : route('admin.blog.store')
  if (props.post) payload._method = 'PUT'

  router.post(url, payload, {
    preserveScroll: true,
    onError: (e) => { errors.value = e; processing.value = false },
    onSuccess: () => { processing.value = false },
  })
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.label { @apply text-sm font-medium text-charcoal mb-1.5 block; }
</style>
