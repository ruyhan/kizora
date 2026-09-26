<template>
  <!-- Admin area: shown inside the admin panel -->
  <AdminLayout v-if="admin">
    <Head title="Page not found" />
    <div class="min-h-[60vh] flex items-center justify-center">
      <div class="text-center max-w-md">
        <p class="font-display text-7xl font-bold text-ink/10 leading-none select-none">404</p>
        <h1 class="font-display text-2xl font-bold text-charcoal mt-2 mb-2">Page not found</h1>
        <p class="text-muted mb-1">This admin page doesn't exist, or the record was deleted.</p>
        <p class="text-xs text-muted/70 font-mono break-all mb-6">{{ path }}</p>
        <div class="flex items-center justify-center gap-3">
          <button type="button" @click="goBack" class="btn-secondary inline-flex items-center gap-1.5 px-5 py-2.5 text-sm">
            <ArrowLeftIcon class="w-4 h-4" /> Go back
          </button>
          <Link :href="route('admin.dashboard')" class="btn-primary inline-flex px-5 py-2.5 text-sm">Back to dashboard</Link>
        </div>
      </div>
    </div>
  </AdminLayout>

  <!-- Storefront -->
  <AppLayout v-else>
    <Head title="Page not found">
      <meta name="robots" content="noindex" head-key="robots" />
    </Head>

    <section class="w-full max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center">
      <div class="relative inline-block">
        <span class="absolute inset-0 -z-10 blur-3xl rounded-full bg-accent/15"></span>
        <p class="font-display font-bold leading-none tracking-tight text-[7rem] sm:text-[10rem] bg-gradient-to-b from-ink to-ink/40 bg-clip-text text-transparent select-none">
          404
        </p>
      </div>

      <h1 class="font-display text-3xl sm:text-4xl font-bold text-charcoal mt-2">This page wandered off</h1>
      <p class="text-muted text-base sm:text-lg mt-3 max-w-xl mx-auto">
        The page you're looking for doesn't exist or may have moved. Try searching, or pick up where you left off below.
      </p>

      <!-- Search the shop -->
      <form @submit.prevent="search" class="mt-8 max-w-md mx-auto flex gap-2" role="search">
        <div class="relative flex-1">
          <MagnifyingGlassIcon class="w-5 h-5 text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input v-model="q" type="search" placeholder="Search products…" aria-label="Search products"
            class="input-field pl-11 w-full">
        </div>
        <button type="submit" class="btn-primary px-5 shrink-0">Search</button>
      </form>

      <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <Link :href="route('home')" class="btn-primary inline-flex items-center gap-1.5 px-6 py-2.5">
          <HomeIcon class="w-4 h-4" /> Go to homepage
        </Link>
        <Link :href="route('shop.index')" class="btn-secondary inline-flex items-center gap-1.5 px-6 py-2.5">
          <ShoppingBagIcon class="w-4 h-4" /> Browse the shop
        </Link>
      </div>

      <!-- Popular categories -->
      <div v-if="categories.length" class="mt-12">
        <p class="text-[11px] uppercase tracking-[0.2em] text-muted font-semibold mb-3">Or explore a category</p>
        <div class="flex flex-wrap justify-center gap-2">
          <Link v-for="c in categories" :key="c.slug" :href="route('shop.index', { category: c.slug })"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-border bg-surface text-sm text-charcoal hover:border-ink hover:text-ink transition-colors">
            <CategoryIcon :icon="c.icon" :slug="c.slug" class="w-4 h-4" /> {{ c.name }}
          </Link>
        </div>
      </div>

      <p class="text-sm text-muted mt-12">
        Think something's broken?
        <Link :href="route('contact')" class="text-ink font-semibold hover:text-accent">Contact us</Link>
        or read the <Link :href="route('faq')" class="text-ink font-semibold hover:text-accent">FAQ</Link>.
      </p>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CategoryIcon from '@/Components/icons/CategoryIcon.vue'
import { MagnifyingGlassIcon, HomeIcon, ShoppingBagIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

defineProps({
  admin: { type: Boolean, default: false },   // true → render inside the admin panel
  path:  { type: String, default: '' },
})

const page = usePage()
const categories = computed(() => (page.props.categoryTree || []).slice(0, 6))

const q = ref('')
const search = () => router.get(route('shop.index'), q.value.trim() ? { search: q.value.trim() } : {})

const goBack = () => (window.history.length > 1 ? window.history.back() : router.visit(route('admin.dashboard')))
</script>
