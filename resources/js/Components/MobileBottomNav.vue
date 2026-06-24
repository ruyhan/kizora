<template>
  <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-surface border-t border-border safe-area-inset">
    <div class="flex items-end justify-around px-2 pt-1.5">

      <Link :href="route('home')" class="bottom-nav-item" :class="active('home')">
        <HomeIcon class="w-6 h-6" />
        <span>Home</span>
      </Link>

      <button @click="openCategories" class="bottom-nav-item text-muted">
        <Squares2X2Icon class="w-6 h-6" />
        <span>Categories</span>
      </button>

      <!-- Cart (center, elevated) -->
      <button @click="openCart" class="relative flex flex-1 basis-0 flex-col items-center gap-1 -mt-5">
        <span data-cart-fab class="w-14 h-14 rounded-2xl bg-ink flex items-center justify-center shadow-lg relative">
          <ShoppingBagIcon class="w-6 h-6 text-white" />
          <span v-if="cart_count > 0" class="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full bg-accent text-white text-[10px] flex items-center justify-center font-bold shadow">
            {{ cart_count }}
          </span>
        </span>
        <span class="text-[11px] font-semibold text-ink">Cart</span>
      </button>

      <Link v-if="auth?.user" :href="route('wishlist.index')" class="bottom-nav-item relative" :class="active('wishlist')">
        <HeartIcon class="w-6 h-6" />
        <span v-if="wishlist_count > 0" class="absolute top-0 right-2.5 min-w-4 h-4 px-1 rounded-full bg-accent text-white text-[10px] flex items-center justify-center font-bold">{{ wishlist_count }}</span>
        <span>Saved</span>
      </Link>
      <button v-else @click="openSearch" class="bottom-nav-item text-muted">
        <MagnifyingGlassIcon class="w-6 h-6" />
        <span>Search</span>
      </button>

      <Link v-if="auth?.user" :href="route('account.dashboard')" class="bottom-nav-item" :class="active('account')">
        <span class="w-7 h-7 rounded-full bg-ink flex items-center justify-center text-white text-xs font-semibold">{{ auth.user.name[0].toUpperCase() }}</span>
        <span>Account</span>
      </Link>
      <Link v-else :href="route('login')" class="bottom-nav-item" :class="active('login')">
        <UserIcon class="w-6 h-6" />
        <span>Login</span>
      </Link>

    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { HomeIcon, Squares2X2Icon, ShoppingBagIcon, HeartIcon, UserIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const auth = page.props.auth
const cart_count = computed(() => page.props.cart_count ?? 0)
const wishlist_count = computed(() => page.props.wishlist_count ?? 0)

const active = (name) => (route().current(name) || route().current(name + '*') ? 'text-ink' : 'text-muted')
const openCart = () => window.dispatchEvent(new Event('open-cart'))
const openCategories = () => window.dispatchEvent(new Event('open-category-menu'))
const openSearch = () => window.dispatchEvent(new Event('open-search'))
</script>

<style scoped>
@reference "../../css/app.css";
.bottom-nav-item { @apply flex flex-1 basis-0 flex-col items-center gap-1 pb-2 text-[11px] font-medium transition-colors hover:text-ink; }
.safe-area-inset { padding-bottom: env(safe-area-inset-bottom, 0); }
</style>
