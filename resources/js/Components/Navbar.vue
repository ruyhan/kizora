<template>
  <header class="fixed top-0 inset-x-0 z-50 border-b border-border transition-all duration-300"
    :class="scrolled ? 'bg-white/90 backdrop-blur-md shadow-lg shadow-ink/5' : 'bg-transparent'" @keydown.esc="closeAll">
    <!-- ── Top bar ── -->
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3 sm:gap-5 h-16">

        <!-- Logo -->
        <Link :href="route('home')" class="flex items-center shrink-0 group">
          <img :src="logoSrc" alt="Kizora"
            class="h-7 md:h-8 w-auto object-contain transition-transform duration-200 group-hover:scale-[1.03]">
        </Link>

        <!-- Persistent desktop search (centered, fills the middle) -->
        <div class="hidden md:flex flex-1 justify-center px-4 lg:px-8">
          <div class="relative w-full max-w-xl" ref="deskSearchRef">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" />
          <input
            ref="deskInput"
            v-model="searchQuery" @input="onSearchInput" @focus="showResults = searchResults.length > 0"
            @keydown.enter.prevent="submitSearch" type="text" placeholder="Search backpacks, bottles, flasks…"
            class="w-full h-11 pl-10 pr-10 rounded-full bg-[#F4F4F5] border border-transparent text-sm text-charcoal
                   focus:bg-surface focus:border-[#D5D5D9] focus:outline-none focus:ring-2 focus:ring-ink/10 transition">
          <kbd v-if="!searchQuery"
            class="absolute right-3 top-1/2 -translate-y-1/2 hidden lg:flex items-center h-5 px-1.5 rounded-md border border-border bg-white text-[10px] font-semibold text-muted pointer-events-none">/</kbd>
          <button v-if="searchQuery" @click="clearSearch" aria-label="Clear search"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink">
            <XMarkIcon class="w-4 h-4" />
          </button>
          <!-- results -->
          <Transition name="kz-pop">
            <div v-if="showResults" class="absolute z-50 top-12 left-0 right-0 bg-surface rounded-2xl shadow-xl border border-border p-2">
              <div v-if="searchResults.length" class="max-h-96 overflow-y-auto scrollbar-hide">
                <Link v-for="p in searchResults" :key="p.id" :href="route('shop.show', p.slug)" @click="closeAll"
                  class="flex items-center gap-3 px-2.5 py-2 rounded-xl hover:bg-[#F4F4F5] transition-colors group">
                  <img :src="p.featured_image_url" :alt="p.name" class="w-11 h-11 object-cover rounded-lg bg-[#F1F1F2] shrink-0">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-charcoal truncate group-hover:text-ink">{{ p.name }}</p>
                    <p class="text-xs text-muted truncate">{{ p.category }}</p>
                  </div>
                  <p class="text-sm font-bold text-ink shrink-0">৳{{ Number(p.final_price).toLocaleString() }}</p>
                </Link>
                <button @click="submitSearch" class="w-full mt-1 px-4 py-2.5 rounded-xl text-xs font-semibold text-ink hover:bg-[#F4F4F5] transition-colors">
                  See all results for "{{ searchQuery }}"
                </button>
              </div>
              <p v-else-if="!searchLoading" class="px-4 py-6 text-center text-sm text-muted">No products found.</p>
            </div>
          </Transition>
          </div>
        </div>

        <!-- Right actions -->
        <div class="flex items-center gap-1 shrink-0 ml-auto md:ml-0">
          <!-- mobile search toggle -->
          <button @click="mobileSearchOpen = !mobileSearchOpen" class="icon-btn md:hidden" aria-label="Search">
            <MagnifyingGlassIcon class="w-5 h-5" />
          </button>

          <Link v-if="auth?.user" :href="route('wishlist.index')" class="relative icon-btn hidden sm:flex" aria-label="Wishlist">
            <HeartIcon class="w-5 h-5" />
            <span v-if="wishlist_count > 0" class="badge-count bg-ink">{{ wishlist_count }}</span>
          </Link>

          <button @click="openCart" class="relative icon-btn" aria-label="Cart">
            <ShoppingBagIcon class="w-5 h-5" />
            <span v-if="cart_count > 0" class="badge-count bg-accent">{{ cart_count }}</span>
          </button>

          <!-- account (desktop) -->
          <div class="relative hidden md:block" v-if="auth?.user" ref="userMenuRef">
            <button @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-1.5 px-1.5 py-1 rounded-xl hover:bg-[#F4F4F5] transition-colors ml-1" aria-label="Account">
              <span class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-white text-sm font-semibold">
                {{ auth.user.name[0].toUpperCase() }}
              </span>
              <ChevronDownIcon class="w-3.5 h-3.5 text-muted transition-transform" :class="userMenuOpen ? 'rotate-180' : ''" />
            </button>
            <Transition name="kz-pop">
              <div v-if="userMenuOpen" class="absolute right-0 top-12 bg-surface rounded-2xl shadow-xl border border-border py-2 z-50 min-w-52">
                <div class="px-4 py-2.5 border-b border-border mb-1">
                  <p class="text-sm font-semibold text-ink leading-tight">{{ auth.user.name }}</p>
                  <p class="text-xs text-muted mt-0.5 truncate">{{ auth.user.email }}</p>
                </div>
                <Link :href="route('account.dashboard')" class="dropdown-item"><UserCircleIcon class="w-4 h-4" /> My Dashboard</Link>
                <Link :href="route('account.orders')" class="dropdown-item"><ClipboardDocumentListIcon class="w-4 h-4" /> My Orders</Link>
                <Link :href="route('account.profile')" class="dropdown-item"><Cog6ToothIcon class="w-4 h-4" /> Profile</Link>
                <Link v-if="auth.user.is_staff" :href="route('admin.dashboard')" class="dropdown-item font-medium"><Squares2X2Icon class="w-4 h-4" /> Admin Panel</Link>
                <div class="border-t border-border my-1"></div>
                <Link :href="route('logout')" method="post" as="button" class="dropdown-item text-accent hover:bg-accent-50 w-full"><ArrowRightOnRectangleIcon class="w-4 h-4" /> Sign Out</Link>
              </div>
            </Transition>
          </div>
          <Link v-else :href="route('login')" class="hidden md:inline-flex items-center justify-center btn-primary h-10 w-10 ml-1" aria-label="Login" title="Login">
            <UserIcon class="w-5 h-5" />
          </Link>

          <!-- mobile hamburger -->
          <button @click="mobileMenuOpen = true" class="md:hidden icon-btn ml-0.5" aria-label="Menu">
            <Bars3Icon class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- mobile search row -->
      <Transition name="kz-collapse">
        <div v-if="mobileSearchOpen" class="md:hidden pb-3 relative">
          <MagnifyingGlassIcon class="absolute left-3.5 top-3 w-4 h-4 text-muted" />
          <input v-model="searchQuery" @input="onSearchInput" @keydown.enter.prevent="submitSearch" type="text"
            placeholder="Search products…" autofocus
            class="w-full h-10 pl-10 pr-3 rounded-xl bg-[#F4F4F5] border border-transparent text-sm focus:bg-surface focus:border-[#D5D5D9] focus:outline-none">
          <div v-if="showResults && searchResults.length" class="mt-2 bg-surface rounded-2xl border border-border p-2 max-h-80 overflow-y-auto">
            <Link v-for="p in searchResults" :key="p.id" :href="route('shop.show', p.slug)" @click="closeAll"
              class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-[#F4F4F5]">
              <img :src="p.featured_image_url" :alt="p.name" class="w-10 h-10 object-cover rounded-lg bg-[#F1F1F2]">
              <span class="flex-1 min-w-0 text-sm text-charcoal truncate">{{ p.name }}</span>
              <span class="text-sm font-bold text-ink">৳{{ Number(p.final_price).toLocaleString() }}</span>
            </Link>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ── Desktop nav bar (with mega menu) ── -->
    <nav class="hidden md:block border-t border-border relative" @mouseleave="scheduleMegaClose">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        <ul class="flex items-center gap-7 h-12">
          <li v-for="(link, i) in navItems" :key="i" class="h-full">
            <template v-if="link.mega">
              <button
                class="nav-tab" :class="(megaOpen || isActive(link.url)) ? 'nav-tab-active' : ''"
                @mouseenter="openMega" @focus="openMega" :aria-expanded="megaOpen"
              >
                {{ link.label }} <ChevronDownIcon class="w-3.5 h-3.5 transition-transform duration-200" :class="megaOpen ? 'rotate-180' : ''" />
              </button>
            </template>
            <Link v-else :href="link.url" class="nav-tab" :class="isActive(link.url) ? 'nav-tab-active' : ''">
              {{ link.label }}
            </Link>
          </li>
        </ul>

        <!-- Utility links (editable) -->
        <div v-if="utilityItems.length" class="hidden lg:flex items-center gap-6">
          <Link v-for="(u, i) in utilityItems" :key="i" :href="u.url" class="util-link" :class="isActive(u.url) ? 'text-ink' : ''">
            <component :is="utilIcon(u.icon)" v-if="utilIcon(u.icon)" class="w-4 h-4" /> {{ u.label }}
          </Link>
        </div>
      </div>

      <Transition name="kz-pop">
        <MegaMenu v-if="megaOpen" @navigate="megaOpen = false" @hoverin="cancelMegaClose" @hoverout="scheduleMegaClose" />
      </Transition>
    </nav>
  </header>

  <!-- spacer (top bar 64 + nav 48 desktop) -->
  <div class="h-16 md:h-28"></div>

  <!-- ── Mobile full-screen category drawer ── -->
  <Drawer :open="mobileMenuOpen" @update:open="mobileMenuOpen = $event" side="right" title="Menu">
    <nav class="px-4 py-4">
      <Link v-for="(link, i) in navItems.filter(l => !l.mega)" :key="i" :href="link.url" @click="mobileMenuOpen = false"
        class="panel-link" :class="isActive(link.url) ? 'panel-link-active' : ''">{{ link.label }}</Link>

      <p class="text-[11px] font-bold uppercase tracking-widest text-muted px-4 mt-5 mb-2">Shop by category</p>
      <div class="space-y-1">
        <div v-for="group in categoryGroups" :key="group.id">
          <!-- Parent -->
          <Link :href="route('shop.index', { category: group.slug })" @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-4 py-2 rounded-xl text-sm font-semibold text-charcoal hover:bg-purple-50 transition-colors">
            <span class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
              <CategoryIcon :slug="group.slug" :icon="group.icon" class="w-4 h-4 text-ink" />
            </span>
            {{ group.name }}
          </Link>
          <!-- Children -->
          <div v-if="group.children?.length" class="ml-[1.65rem] mt-0.5 mb-1 pl-4 border-l border-border space-y-0.5">
            <Link v-for="item in group.children" :key="item.slug" :href="route('shop.index', { category: item.slug })" @click="mobileMenuOpen = false"
              class="block px-2.5 py-1.5 rounded-lg text-sm text-muted hover:text-ink hover:bg-purple-50 transition-colors">{{ item.name }}</Link>
          </div>
        </div>
      </div>

      <template v-if="auth?.user">
        <div class="my-3 h-px bg-border"></div>
        <Link :href="route('account.dashboard')" @click="mobileMenuOpen = false" class="panel-link">My Account</Link>
        <Link :href="route('account.orders')" @click="mobileMenuOpen = false" class="panel-link">My Orders</Link>
        <Link :href="route('wishlist.index')" @click="mobileMenuOpen = false" class="panel-link">Wishlist</Link>
      </template>
    </nav>
    <template #footer>
      <!-- Guest: Sign In / Create Account · Logged in: Sign Out — both sticky -->
      <div v-if="!auth?.user" class="px-4 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] flex gap-2.5">
        <Link :href="route('login')" class="btn-primary flex-1 justify-center px-3 py-3 text-sm whitespace-nowrap" @click="mobileMenuOpen = false">Sign In</Link>
        <Link :href="route('register')" class="btn-secondary flex-1 justify-center px-3 py-3 text-sm whitespace-nowrap" @click="mobileMenuOpen = false">Create Account</Link>
      </div>
      <div v-else class="px-4 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
        <Link :href="route('logout')" method="post" as="button" @click="mobileMenuOpen = false"
          class="w-full inline-flex items-center justify-center gap-2 px-3 py-3 text-sm font-semibold rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 active:bg-rose-100 transition-colors">
          <ArrowRightOnRectangleIcon class="w-4 h-4" /> Sign Out
        </Link>
      </div>
    </template>
  </Drawer>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import {
  MagnifyingGlassIcon, HeartIcon, ShoppingBagIcon, XMarkIcon, UserIcon, UserCircleIcon,
  ChevronDownIcon, ClipboardDocumentListIcon, Cog6ToothIcon, Squares2X2Icon,
  ArrowRightOnRectangleIcon, Bars3Icon, TruckIcon, QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline'
import MegaMenu from '@/Components/layout/MegaMenu.vue'
import Drawer from '@/Components/ui/Drawer.vue'
import CategoryIcon from '@/Components/icons/CategoryIcon.vue'

const DEFAULT_NAV = [
  { label: 'Home', url: '/', mega: false },
  { label: 'Shop', url: '/shop', mega: true },
  { label: 'About', url: '/about', mega: false },
  { label: 'Contact', url: '/contact', mega: false },
]

const page = usePage()
const auth = page.props.auth
const logoSrc = computed(() => page.props.site?.logo || '/images/kizora-logo.png')
const cart_count = computed(() => page.props.cart_count ?? 0)
const scrolled = ref(false)
const deskInput = ref(null)
const wishlist_count = computed(() => page.props.wishlist_count ?? 0)
const categoryGroups = computed(() => page.props.categoryTree || [])

// Editable header (Admin → Page Content → Header & Menu)
const navItems = computed(() => page.props.header?.nav?.length ? page.props.header.nav : DEFAULT_NAV)
const utilityItems = computed(() => page.props.header?.utility || [])
const utilIcon = (k) => ({ truck: TruckIcon, help: QuestionMarkCircleIcon }[k] || null)
const currentPath = computed(() => (page.url || '/').split('?')[0])
const isActive = (url) => {
  const p = (url || '/').split('?')[0]
  if (p === '/') return currentPath.value === '/'
  return currentPath.value === p || currentPath.value.startsWith(p + '/')
}

const userMenuOpen = ref(false)
const userMenuRef = ref(null)
const mobileMenuOpen = ref(false)
const mobileSearchOpen = ref(false)
const deskSearchRef = ref(null)

// search
const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const showResults = ref(false)
let debounceTimer = null

const onSearchInput = () => {
  clearTimeout(debounceTimer)
  const q = searchQuery.value.trim()
  if (q.length < 2) { searchResults.value = []; showResults.value = false; return }
  searchLoading.value = true; showResults.value = true
  debounceTimer = setTimeout(async () => {
    try { const { data } = await axios.get(route('search'), { params: { q } }); searchResults.value = data }
    catch { searchResults.value = [] }
    finally { searchLoading.value = false }
  }, 250)
}
const clearSearch = () => { searchQuery.value = ''; searchResults.value = []; showResults.value = false }
const submitSearch = () => { if (searchQuery.value.trim()) { router.get(route('shop.index'), { search: searchQuery.value }); closeAll() } }

// mega menu hover w/ close delay
const megaOpen = ref(false)
let megaTimer = null
const openMega = () => { clearTimeout(megaTimer); megaOpen.value = true }
const scheduleMegaClose = () => { clearTimeout(megaTimer); megaTimer = setTimeout(() => (megaOpen.value = false), 140) }
const cancelMegaClose = () => clearTimeout(megaTimer)

const openCart = () => window.dispatchEvent(new Event('open-cart'))
const closeAll = () => { showResults.value = false; mobileSearchOpen.value = false; userMenuOpen.value = false; megaOpen.value = false }

const handleOutside = (e) => {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) userMenuOpen.value = false
  if (deskSearchRef.value && !deskSearchRef.value.contains(e.target)) showResults.value = false
}
const openMobileMenu = () => { mobileMenuOpen.value = true }
const openMobileSearch = () => { mobileSearchOpen.value = true }
const onScroll = () => { scrolled.value = window.scrollY > 8 }
const onSlash = (e) => {
  if (e.key === '/' && !/^(input|textarea|select)$/i.test(e.target?.tagName) && !e.target?.isContentEditable) {
    e.preventDefault()
    deskInput.value?.focus()
  }
}
onMounted(() => {
  document.addEventListener('click', handleOutside)
  document.addEventListener('keydown', onSlash)
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('open-category-menu', openMobileMenu)
  window.addEventListener('open-search', openMobileSearch)
  onScroll()
})
onUnmounted(() => {
  document.removeEventListener('click', handleOutside)
  document.removeEventListener('keydown', onSlash)
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('open-category-menu', openMobileMenu)
  window.removeEventListener('open-search', openMobileSearch)
  clearTimeout(debounceTimer); clearTimeout(megaTimer)
})
</script>

<style scoped>
@reference "../../css/app.css";
.icon-btn { @apply p-2.5 rounded-xl hover:bg-[#F4F4F5] text-charcoal hover:text-ink transition-colors; }
.badge-count { @apply absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 rounded-full text-white text-[10px] flex items-center justify-center font-bold leading-none; }
.nav-tab {
  @apply h-full inline-flex items-center gap-1 text-sm font-medium text-charcoal border-b-2 border-transparent hover:text-ink transition-colors duration-200 cursor-pointer;
}
.nav-tab-active { @apply text-ink font-semibold border-ink; }
.util-link { @apply inline-flex items-center gap-1.5 text-xs font-medium text-muted hover:text-ink transition-colors; }
.dropdown-item { @apply flex items-center gap-3 px-4 py-2 text-sm text-charcoal hover:bg-[#F4F4F5] hover:text-ink transition-colors; }
.panel-link { @apply flex items-center gap-3 px-4 py-3 rounded-xl text-charcoal hover:bg-[#F4F4F5] hover:text-ink transition-colors font-medium text-sm; }
.panel-link-active { @apply bg-[#F4F4F5] text-ink; }

.kz-pop-enter-active { transition: opacity .18s ease, transform .18s cubic-bezier(.16,1,.3,1); }
.kz-pop-leave-active { transition: opacity .12s ease, transform .12s ease; }
.kz-pop-enter-from, .kz-pop-leave-to { opacity: 0; transform: translateY(-6px); }
.kz-collapse-enter-active, .kz-collapse-leave-active { transition: opacity .2s ease; }
.kz-collapse-enter-from, .kz-collapse-leave-to { opacity: 0; }
</style>
