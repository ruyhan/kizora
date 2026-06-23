<template>
  <AppLayout>
    <slot name="head" />

    <div class="w-full">

      <!-- ── Mobile: gradient banner + scrollable tab strip (< lg) ── -->
      <div class="lg:hidden">
        <div class="gradient-purple px-4 pt-6 pb-5">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center text-white text-xl font-bold font-display shrink-0">
              {{ user?.name?.[0]?.toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="font-semibold text-white truncate leading-tight">{{ user?.name }}</p>
              <p class="text-white/65 text-sm truncate mt-0.5">{{ user?.email }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white border-b border-purple-100 px-2">
          <div class="flex overflow-x-auto scrollbar-hide">
            <Link :href="route('account.dashboard')"
              class="tab-link" :class="{ 'tab-link-active': isActive('account.dashboard') }">
              <Squares2X2Icon class="w-4 h-4" /> Overview
            </Link>
            <Link :href="route('account.orders')"
              class="tab-link" :class="{ 'tab-link-active': isActive('account.orders') }">
              <ClipboardDocumentListIcon class="w-4 h-4" /> Orders
            </Link>
            <Link :href="route('wishlist.index')"
              class="tab-link" :class="{ 'tab-link-active': isActive('wishlist') }">
              <HeartIcon class="w-4 h-4" /> Wishlist
            </Link>
            <Link :href="route('account.profile')"
              class="tab-link" :class="{ 'tab-link-active': isActive('account.profile') }">
              <UserCircleIcon class="w-4 h-4" /> Profile
            </Link>
          </div>
        </div>
      </div>

      <!-- ── Page content ── -->
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10">
        <div class="lg:flex lg:gap-8">

          <!-- Desktop sidebar -->
          <aside class="hidden lg:flex flex-col w-64 xl:w-72 shrink-0">
            <!-- User card -->
            <div class="gradient-purple rounded-2xl p-6 mb-4">
              <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center text-white text-2xl font-bold font-display mb-4">
                {{ user?.name?.[0]?.toUpperCase() }}
              </div>
              <p class="font-semibold text-white text-lg leading-tight">{{ user?.name }}</p>
              <p class="text-white/65 text-sm mt-1 truncate">{{ user?.email }}</p>
            </div>

            <!-- Nav -->
            <nav class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
              <Link :href="route('account.dashboard')"
                class="sidebar-link" :class="{ 'sidebar-link-active': isActive('account.dashboard') }">
                <Squares2X2Icon class="w-5 h-5 shrink-0" /> Overview
              </Link>
              <Link :href="route('account.orders')"
                class="sidebar-link" :class="{ 'sidebar-link-active': isActive('account.orders') }">
                <ClipboardDocumentListIcon class="w-5 h-5 shrink-0" /> My Orders
              </Link>
              <Link :href="route('wishlist.index')"
                class="sidebar-link" :class="{ 'sidebar-link-active': isActive('wishlist') }">
                <HeartIcon class="w-5 h-5 shrink-0" /> Wishlist
              </Link>
              <Link :href="route('account.profile')"
                class="sidebar-link" :class="{ 'sidebar-link-active': isActive('account.profile') }">
                <UserCircleIcon class="w-5 h-5 shrink-0" /> Profile
              </Link>
              <div class="border-t border-purple-100 mx-4"></div>
              <Link :href="route('logout')" method="post" as="button"
                class="sidebar-link text-rose-500 hover:bg-rose-50 hover:text-rose-600 w-full">
                <ArrowRightOnRectangleIcon class="w-5 h-5 shrink-0" /> Sign Out
              </Link>
            </nav>
          </aside>

          <!-- Main content slot -->
          <main class="flex-1 min-w-0">
            <slot />
          </main>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Squares2X2Icon,
  ClipboardDocumentListIcon,
  HeartIcon,
  UserCircleIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const isActive = (name) => route().current(name + '*')
</script>

<style scoped>
@reference "../../css/app.css";

.sidebar-link {
  @apply flex items-center gap-3 px-5 py-3.5 text-sm font-medium transition-all text-charcoal hover:bg-purple-50 hover:text-primary;
}
.sidebar-link-active {
  @apply bg-purple-50 text-primary;
}

.tab-link {
  @apply flex items-center gap-1.5 px-4 py-3.5 text-sm font-medium whitespace-nowrap transition-all text-muted hover:text-charcoal border-b-2 border-transparent shrink-0;
}
.tab-link-active {
  @apply text-primary border-primary;
}
</style>
