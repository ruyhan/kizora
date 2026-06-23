<template>
  <div class="min-h-screen bg-purple-50 flex">
    <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" />
    <div class="flex-1 min-w-0 flex flex-col">
      <!-- Admin Header -->
      <header class="sticky top-0 z-30 bg-white border-b border-purple-100 px-4 sm:px-6 py-4 flex items-center gap-3">
        <button @click="sidebarOpen = true" class="md:hidden text-muted hover:text-primary -ml-1" aria-label="Open menu">
          <Bars3Icon class="w-6 h-6" />
        </button>
        <div class="flex-1 md:ml-0">
          <h2 class="text-lg font-semibold text-charcoal font-display">{{ pageTitle }}</h2>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-sm text-muted">{{ $page.props.auth.user?.name }}</span>
          <Link :href="route('home')" class="text-xs text-primary hover:underline">View Site</Link>
          <Link :href="route('logout')" method="post" as="button"
            class="text-xs text-error hover:underline">Logout</Link>
        </div>
      </header>
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
    <FlashMessage />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AdminSidebar from '@/Components/AdminSidebar.vue'
import FlashMessage from '@/Components/FlashMessage.vue'
import { Bars3Icon } from '@heroicons/vue/24/outline'

const sidebarOpen = ref(false)
const page = usePage()
const pageTitle = computed(() => page.props.title || 'Admin Panel')
</script>
