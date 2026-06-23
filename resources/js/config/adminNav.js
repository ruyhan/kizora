// Admin navigation — single source for the desktop sidebar + mobile drawer.
// Each item carries the `permission` slug required to see it (nav is hidden otherwise).
import {
  Squares2X2Icon, ArchiveBoxIcon, TagIcon, TicketIcon, PhotoIcon,
  ClipboardDocumentListIcon, EnvelopeIcon, DocumentTextIcon, Cog6ToothIcon,
  UsersIcon, UserGroupIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon, ChartBarIcon,
  CubeIcon, BuildingStorefrontIcon, ShoppingCartIcon,
  ChatBubbleLeftRightIcon, SparklesIcon, PresentationChartLineIcon,
} from '@heroicons/vue/24/outline'

export const adminNavSections = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', route: 'admin.dashboard', match: 'admin.dashboard*', icon: Squares2X2Icon, permission: 'dashboard.view' },
      { label: 'Reports', route: 'admin.reports.index', match: 'admin.reports.index', icon: ChartBarIcon, permission: 'reports.view' },
      { label: 'Sales Analysis', route: 'admin.reports.sales', match: 'admin.reports.sales', icon: PresentationChartLineIcon, permission: 'reports.view' },
    ],
  },
  {
    label: 'Catalog',
    items: [
      { label: 'Products',      route: 'admin.products.index',   match: 'admin.products*',   icon: ArchiveBoxIcon, permission: 'products.view' },
      { label: 'Categories',    route: 'admin.categories.index', match: 'admin.categories*', icon: TagIcon, permission: 'categories.view' },
      { label: 'Inventory',     route: 'admin.inventory.index',  match: 'admin.inventory*',  icon: CubeIcon, permission: 'inventory.view' },
      { label: 'Media Library', route: 'admin.media.index',      match: 'admin.media.index', icon: PhotoIcon, permission: 'media.view' },
    ],
  },
  {
    label: 'Sales',
    items: [
      { label: 'Orders',   route: 'admin.orders.index',      match: 'admin.orders*',           icon: ClipboardDocumentListIcon, permission: 'orders.view' },
      { label: 'Customers', route: 'admin.customers.index',  match: 'admin.customers*',         icon: UserGroupIcon, permission: 'customers.view' },
      { label: 'Coupons',  route: 'admin.coupons.index',     match: 'admin.coupons*',           icon: TicketIcon, permission: 'marketing.view' },
      { label: 'Messages', route: 'admin.messages.contacts', match: 'admin.messages.contacts*', icon: EnvelopeIcon, permission: 'customers.view' },
    ],
  },
  {
    label: 'Point of Sale',
    items: [
      { label: 'Create POS Order', route: 'admin.pos.create', match: 'admin.pos.create', icon: ShoppingCartIcon, permission: 'pos.create' },
      { label: 'POS Orders',       route: 'admin.pos.orders', match: 'admin.pos.orders', icon: BuildingStorefrontIcon, permission: 'pos.orders' },
    ],
  },
  {
    label: 'AI Chatbot',
    items: [
      { label: 'Chatbot Orders',    route: 'admin.chatbot.orders',        match: 'admin.chatbot.orders',        icon: ChatBubbleLeftRightIcon, permission: 'chatbot.orders.view' },
      { label: 'Chatbot Analytics', route: 'admin.chatbot.analytics',     match: 'admin.chatbot.analytics',     icon: PresentationChartLineIcon, permission: 'chatbot.analytics.view' },
      { label: 'Conversations',     route: 'admin.chatbot.conversations', match: 'admin.chatbot.conversations', icon: SparklesIcon, permission: 'chatbot.analytics.view' },
    ],
  },
  {
    label: 'Access Control',
    items: [
      { label: 'Users',                route: 'admin.users.index',      match: 'admin.users*',      icon: UsersIcon, permission: 'users.view' },
      { label: 'Roles & Permissions',  route: 'admin.roles.index',      match: 'admin.roles*',      icon: ShieldCheckIcon, permission: 'roles.view' },
      { label: 'Audit Logs',           route: 'admin.audit-logs.index', match: 'admin.audit-logs*', icon: ClipboardDocumentCheckIcon, permission: 'reports.view' },
    ],
  },
  {
    label: 'Content',
    items: [
      { label: 'Pages & Content', route: 'admin.content.index', match: 'admin.content.index', icon: DocumentTextIcon, permission: 'cms.view' },
    ],
  },
  {
    label: 'Configuration',
    items: [
      {
        label: 'Settings',
        route: 'admin.content.edit',
        params: { group: 'settings' },
        match: ['admin.content.edit', 'admin.payments*', 'admin.delivery-cities*', 'admin.delivery-areas*', 'admin.recaptcha*'],
        matchParams: { group: 'settings' },
        icon: Cog6ToothIcon,
        permission: 'settings.view',
      },
    ],
  },
]
