<template>
  <AppLayout>
    <Head :title="post.meta_title || post.title">
      <meta name="description" :content="metaDescription" head-key="description" />
      <meta v-if="post.meta_keywords" name="keywords" :content="post.meta_keywords" head-key="keywords" />
      <meta property="og:title" :content="post.meta_title || post.title" head-key="og:title" />
      <meta property="og:description" :content="metaDescription" head-key="og:description" />
      <meta v-if="post.og_image_url" property="og:image" :content="post.og_image_url" head-key="og:image" />
      <meta property="og:type" content="article" head-key="og:type" />
    </Head>

    <!-- Same max-w-7xl container as the site header & blog listing, so the article
         header and content line up with the header's left edge. -->
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
      <div class="grid lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- Main column -->
        <article class="lg:col-span-8 min-w-0">
          <!-- Breadcrumb -->
          <nav class="flex items-center gap-1.5 text-xs text-muted mb-6">
            <Link :href="route('blog.index')" class="hover:text-ink">Blog</Link>
            <span>/</span>
            <Link v-if="post.category" :href="route('blog.category', post.category.slug)" class="hover:text-ink">{{ post.category.name }}</Link>
            <span v-if="post.category">/</span>
            <span class="text-charcoal truncate">{{ post.title }}</span>
          </nav>

          <!-- Header -->
          <header class="mb-8">
            <Link v-if="post.category" :href="route('blog.category', post.category.slug)" class="text-sm font-semibold text-primary hover:underline">{{ post.category.name }}</Link>
            <h1 class="font-display text-3xl md:text-4xl font-bold text-charcoal mt-2 leading-tight">{{ post.title }}</h1>
            <div class="flex flex-wrap items-center gap-3 text-sm text-muted mt-4">
              <span v-if="post.author" class="font-medium text-charcoal">{{ post.author }}</span>
              <span v-if="post.published_at">· {{ post.published_at }}</span>
              <span>· {{ post.reading_time }} min read</span>
            </div>
          </header>

          <!-- Featured image -->
          <div v-if="post.featured_image_url" class="rounded-2xl overflow-hidden mb-8 bg-purple-50">
            <img :src="post.featured_image_url" :alt="post.title" class="w-full object-cover">
          </div>

          <!-- Excerpt -->
          <p v-if="post.excerpt" class="text-lg text-charcoal/80 leading-relaxed mb-8 font-medium">{{ post.excerpt }}</p>

          <!-- Content -->
          <div class="prose prose-purple max-w-none prose-img:rounded-xl prose-headings:font-display prose-headings:text-charcoal" v-html="post.content"></div>

          <!-- Content image gallery -->
          <div v-if="post.content_images && post.content_images.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-8">
            <a v-for="(img, i) in post.content_images" :key="i" :href="img" target="_blank" class="block aspect-square rounded-xl overflow-hidden bg-purple-50">
              <img :src="img" alt="" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
            </a>
          </div>

          <!-- Tags -->
          <div v-if="post.tags.length" class="flex flex-wrap gap-2 mt-8 pt-8 border-t border-border">
            <Link v-for="t in post.tags" :key="t.slug" :href="route('blog.tag', t.slug)"
              class="px-3 py-1 rounded-full text-xs border border-border text-muted hover:border-ink hover:text-ink transition-colors">#{{ t.name }}</Link>
          </div>
        </article>

        <!-- Sidebar -->
        <aside class="lg:col-span-4 space-y-8">
          <Link :href="route('blog.index')" class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:text-accent transition-colors">
            <ArrowLeftIcon class="w-4 h-4" /> All articles
          </Link>

          <div v-if="categories.length">
            <h3 class="font-display font-semibold text-charcoal mb-3">Categories</h3>
            <ul class="space-y-1.5">
              <li v-for="c in categories" :key="c.slug">
                <Link :href="route('blog.category', c.slug)"
                  class="flex items-center justify-between text-sm text-muted hover:text-ink transition-colors py-1"
                  :class="post.category && post.category.slug === c.slug ? 'text-ink font-semibold' : ''">
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
                class="px-3 py-1 rounded-full text-xs border border-border text-muted hover:border-ink hover:text-ink transition-colors">#{{ t.name }}</Link>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- Related — carousel slider -->
    <BlogRail v-if="related.length" title="Related articles" :posts="related" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BlogRail from '@/Components/home/BlogRail.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  post: Object,
  related: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  tags: { type: Array, default: () => [] },
})

const metaDescription = computed(() => props.post.meta_description || props.post.excerpt || props.post.title)
</script>
