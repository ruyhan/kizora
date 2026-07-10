<template>
  <AppLayout>
    <Head :title="pageTitle">
      <meta name="description" :content="metaDescription" head-key="description" />
      <meta property="og:title" :content="pageTitle" head-key="og:title" />
    </Head>

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
      <!-- Header -->
      <div class="mb-8">
        <p v-if="context.type !== 'all'" class="text-sm text-primary font-semibold mb-1">
          {{ context.type === 'category' ? 'Category' : 'Tag' }}
        </p>
        <h1 class="font-display text-3xl md:text-4xl font-bold text-charcoal">{{ heading }}</h1>
        <p class="text-muted mt-2">Stories, guides and tips from the Kizora team.</p>
      </div>

      <div class="grid lg:grid-cols-4 gap-8 lg:gap-10">
        <!-- Main column -->
        <div class="lg:col-span-3">
          <!-- Featured banner -->
          <Link v-if="featured" :href="route('blog.show', featured.slug)"
            class="group block mb-8 rounded-2xl overflow-hidden border border-border bg-surface">
            <div class="grid md:grid-cols-2">
              <div class="aspect-video md:aspect-auto bg-purple-50 overflow-hidden">
                <img v-if="featured.featured_image_url" :src="featured.featured_image_url" :alt="featured.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
              </div>
              <div class="p-6 md:p-8 flex flex-col justify-center">
                <span class="badge badge-accent w-fit mb-3">Featured</span>
                <h2 class="font-display text-2xl font-bold text-charcoal group-hover:text-primary transition-colors">{{ featured.title }}</h2>
                <p class="text-muted mt-2 line-clamp-3">{{ featured.excerpt }}</p>
                <div class="flex items-center gap-3 text-xs text-muted mt-4">
                  <span v-if="featured.author">{{ featured.author }}</span>
                  <span v-if="featured.published_at">· {{ featured.published_at }}</span>
                  <span>· {{ featured.reading_time }} min read</span>
                </div>
              </div>
            </div>
          </Link>

          <!-- Grid -->
          <div v-if="posts.data.length" class="grid sm:grid-cols-2 gap-6">
            <article v-for="p in posts.data" :key="p.id" class="group flex flex-col rounded-2xl overflow-hidden border border-border bg-surface hover:shadow-lg transition-shadow">
              <Link :href="route('blog.show', p.slug)" class="block aspect-video bg-purple-50 overflow-hidden">
                <img v-if="p.featured_image_url" :src="p.featured_image_url" :alt="p.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
              </Link>
              <div class="p-5 flex flex-col flex-1">
                <Link v-if="p.category" :href="route('blog.category', p.category.slug)" class="text-xs font-semibold text-primary hover:underline w-fit">{{ p.category.name }}</Link>
                <Link :href="route('blog.show', p.slug)" class="block mt-1.5">
                  <h3 class="font-display text-lg font-bold text-charcoal group-hover:text-primary transition-colors line-clamp-2">{{ p.title }}</h3>
                </Link>
                <p class="text-muted text-sm mt-2 line-clamp-3 flex-1">{{ p.excerpt }}</p>
                <div class="flex items-center gap-2 text-xs text-muted mt-4 pt-4 border-t border-border">
                  <span v-if="p.author">{{ p.author }}</span>
                  <span v-if="p.published_at">· {{ p.published_at }}</span>
                  <span class="ml-auto">{{ p.reading_time }} min</span>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="text-center py-20 text-muted">No posts found.</div>

          <!-- Pagination -->
          <div v-if="posts.links.length > 3" class="flex flex-wrap justify-center gap-1.5 mt-10">
            <template v-for="link in posts.links" :key="link.label">
              <Link v-if="link.url" :href="link.url" preserve-scroll
                :class="link.active ? 'bg-ink text-white' : 'bg-surface text-charcoal hover:bg-[#F4F4F5]'"
                class="min-w-9 px-3 py-2 rounded-xl text-sm font-medium border border-border text-center" v-html="link.label" />
              <span v-else class="min-w-9 px-3 py-2 rounded-xl text-sm text-muted/40 text-center" v-html="link.label" />
            </template>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1 space-y-8">
          <div>
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
              <input v-model="search" @keydown.enter="doSearch" type="text" placeholder="Search articles…"
                class="w-full h-11 pl-10 pr-3 rounded-xl bg-[#F4F4F5] border border-transparent text-sm focus:bg-surface focus:border-border focus:outline-none">
            </div>
          </div>

          <div v-if="categories.length">
            <h3 class="font-display font-semibold text-charcoal mb-3">Categories</h3>
            <ul class="space-y-1.5">
              <li v-for="c in categories" :key="c.slug">
                <Link :href="route('blog.category', c.slug)"
                  class="flex items-center justify-between text-sm text-muted hover:text-ink transition-colors py-1"
                  :class="context.slug === c.slug ? 'text-ink font-semibold' : ''">
                  <span>{{ c.name }}</span>
                  <span class="text-xs bg-[#F4F4F5] px-2 py-0.5 rounded-full">{{ c.count }}</span>
                </Link>
              </li>
            </ul>
          </div>

          <div v-if="tags.length">
            <h3 class="font-display font-semibold text-charcoal mb-3">Tags</h3>
            <div class="flex flex-wrap gap-2">
              <Link v-for="t in tags" :key="t.slug" :href="route('blog.tag', t.slug)"
                class="px-3 py-1 rounded-full text-xs border border-border text-muted hover:border-ink hover:text-ink transition-colors"
                :class="context.slug === t.slug ? 'border-ink text-ink' : ''">#{{ t.name }}</Link>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  posts: Object,
  featured: Object,
  categories: { type: Array, default: () => [] },
  tags: { type: Array, default: () => [] },
  context: { type: Object, default: () => ({ type: 'all' }) },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')
const heading = computed(() => props.context.type === 'all' ? 'The Kizora Blog' : props.context.name)
const pageTitle = computed(() => props.context.type === 'all' ? 'Blog' : `${props.context.name} — Blog`)
const metaDescription = computed(() => `Read the latest articles${props.context.name ? ' about ' + props.context.name : ''} from Kizora.`)

const doSearch = () => router.get(route('blog.index'), { search: search.value }, { preserveScroll: true })
</script>
