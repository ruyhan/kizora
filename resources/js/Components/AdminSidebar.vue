<template>
  <!-- ── Desktop sidebar ── -->
  <aside class="hidden md:flex w-64 bg-[#150e20] min-h-screen flex-col sticky top-0 h-screen border-r border-white/5">
    <div class="px-5 py-5 border-b border-white/8">
      <Link :href="route('admin.dashboard')" class="flex items-center gap-3 group">
        <img :src="darkLogo" alt="Kizora" class="h-7 w-auto object-contain" :style="darkInvert ? 'filter: brightness(0) invert(1); opacity: 0.95;' : 'opacity: 0.95;'">
      </Link>
      <p class="text-[10px] text-gray-500 tracking-widest uppercase mt-2 font-medium">Admin Panel</p>
    </div>
    <nav ref="desktopNav" @scroll.passive="saveScroll" class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">
      <template v-for="(section, i) in sections" :key="section.label">
        <p class="nav-section-label" :class="i > 0 ? 'mt-5' : ''">{{ section.label }}</p>
        <SidebarLink v-for="item in section.items" :key="item.label"
          :href="route(item.route, item.params)" :active="isActive(item)" :badge="badgeFor(item)" @click="saveScroll">
          <template #icon><component :is="item.icon" class="w-4.5 h-4.5" /></template>
          {{ item.label }}
        </SidebarLink>
      </template>
      <div class="my-3 h-px bg-white/8"></div>
      <SidebarLink :href="route('home')" :active="false">
        <template #icon><ArrowTopRightOnSquareIcon class="w-4.5 h-4.5" /></template>
        View Website
      </SidebarLink>
    </nav>
    <div class="p-3 border-t border-white/8">
      <Link :href="route('logout')" method="post" as="button"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 transition-all duration-200 text-sm group">
        <ArrowRightOnRectangleIcon class="w-4.5 h-4.5 transition-transform duration-200 group-hover:translate-x-0.5" />
        <span>Logout</span>
      </Link>
    </div>
  </aside>

  <!-- ── Mobile slide-over ── -->
  <Teleport to="body">
    <Transition name="kz-fade">
      <div v-if="open" class="md:hidden fixed inset-0 z-[100] bg-ink/60 backdrop-blur-sm" @click="$emit('close')"></div>
    </Transition>
    <Transition name="kz-slide">
      <aside v-if="open" class="md:hidden fixed top-0 left-0 z-[101] h-full w-72 bg-[#150e20] flex flex-col shadow-2xl"
        role="dialog" aria-modal="true" tabindex="-1" @keydown.esc="$emit('close')">
        <div class="px-5 py-5 border-b border-white/8 flex items-center justify-between">
          <img :src="darkLogo" alt="Kizora" class="h-7 w-auto object-contain" :style="darkInvert ? 'filter: brightness(0) invert(1); opacity: 0.95;' : 'opacity: 0.95;'">
          <button @click="$emit('close')" aria-label="Close menu" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/15 flex items-center justify-center text-gray-300">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <nav ref="mobileNav" @scroll.passive="saveScroll" class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">
          <template v-for="(section, i) in sections" :key="section.label">
            <p class="nav-section-label" :class="i > 0 ? 'mt-5' : ''">{{ section.label }}</p>
            <SidebarLink v-for="item in section.items" :key="item.label"
              :href="route(item.route, item.params)" :active="isActive(item)" :badge="badgeFor(item)" @click="$emit('close')">
              <template #icon><component :is="item.icon" class="w-4.5 h-4.5" /></template>
              {{ item.label }}
            </SidebarLink>
          </template>
          <div class="my-3 h-px bg-white/8"></div>
          <SidebarLink :href="route('home')" :active="false" @click="$emit('close')">
            <template #icon><ArrowTopRightOnSquareIcon class="w-4.5 h-4.5" /></template>
            View Website
          </SidebarLink>
        </nav>
        <div class="p-3 border-t border-white/8">
          <Link :href="route('logout')" method="post" as="button" @click="$emit('close')"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors text-sm">
            <ArrowRightOnRectangleIcon class="w-4.5 h-4.5" /> <span>Logout</span>
          </Link>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, onMounted, nextTick } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import SidebarLink from '@/Components/SidebarLink.vue'
import { adminNavSections } from '@/config/adminNav'
import { useBrandLogo } from '@/composables/useBrandLogo'
import { usePermissions } from '@/composables/usePermissions'

const { dark: darkLogo, darkInvert } = useBrandLogo()
import { ArrowTopRightOnSquareIcon, ArrowRightOnRectangleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ open: { type: Boolean, default: false } })
defineEmits(['close'])

const { can } = usePermissions()

// ── Keep the sidebar where the admin left it ──
// The layout re-mounts on every page visit, which would reset the menu to the top.
// Remember the scroll offset and restore it on mount; then make sure the active item is visible.
const SCROLL_KEY = 'kz-admin-sidebar-scroll'
const desktopNav = ref(null)
const mobileNav = ref(null)
const saveScroll = (e) => {
  const el = e?.currentTarget?.closest?.('nav') ?? desktopNav.value
  if (!el) return
  try { sessionStorage.setItem(SCROLL_KEY, String(el.scrollTop)) } catch { /* storage unavailable */ }
}
const restoreScroll = (el) => {
  if (!el) return
  let top = 0
  try { top = Number(sessionStorage.getItem(SCROLL_KEY)) || 0 } catch { /* storage unavailable */ }
  el.scrollTop = top
  const active = el.querySelector('[aria-current="page"]')
  if (active) {
    const a = active.getBoundingClientRect(), n = el.getBoundingClientRect()
    if (a.top < n.top || a.bottom > n.bottom) active.scrollIntoView({ block: 'center' })
  }
}
onMounted(() => nextTick(() => restoreScroll(desktopNav.value)))
watch(() => props.open, (o) => { if (o) nextTick(() => restoreScroll(mobileNav.value)) })

// Only show items the user is permitted to see; drop sections left empty.
const sections = computed(() =>
  adminNavSections
    .map((s) => ({ ...s, items: s.items.filter((i) => !i.permission || can(i.permission)) }))
    .filter((s) => s.items.length)
)
// Active state supports a single match or an array (e.g. Settings + its tab routes).
// Content editor routes belong to "Settings" or "Header & Footer" for their tab groups, and to "Pages & Content" otherwise.
const SETTINGS_GROUPS = ['settings', 'delivery', 'product', 'invoice', 'chatbot']
const HEADER_FOOTER_GROUPS = ['header', 'megamenu', 'footer']
const contentOwner = (group) =>
  SETTINGS_GROUPS.includes(group) ? 'settings' : HEADER_FOOTER_GROUPS.includes(group) ? 'headerfooter' : 'pages'
const isActive = (item) => {
  if (item.contentGroups && route().current('admin.content.edit')) {
    return contentOwner(route().params.group) === item.contentGroups
  }
  const ms = Array.isArray(item.match) ? item.match : [item.match]
  return ms.some(m => route().current(m))
}

// Notification badges (new orders, unread messages, chatbot orders, reviews, low stock).
const badgeFor = (item) => {
  const p = usePage().props
  return {
    'admin.orders.index': p.newOrdersCount,
    'admin.messages.contacts': p.unreadMessages,
    'admin.chatbot.orders': p.newChatbotOrders,
    'admin.reviews.index': p.newReviewsCount,
    'admin.inventory.index': p.lowStockCount,
    'admin.privilege.index': p.pendingMemberships,
  }[item.route] || 0
}
</script>

<style scoped>
@reference "../../css/app.css";
.nav-section-label { @apply text-[10px] text-gray-600 uppercase tracking-widest font-semibold px-3 mb-1.5 mt-2 block; }
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .25s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
.kz-slide-enter-active, .kz-slide-leave-active { transition: transform .3s cubic-bezier(.4,0,.2,1); }
.kz-slide-enter-from, .kz-slide-leave-to { transform: translateX(-100%); }
</style>
