// Top-level navigation links (Corporate/B2B removed).
// `mega: true` marks the entry that opens the category mega menu.

export const primaryNav = [
  { label: 'Home',  route: 'home' },
  { label: 'Shop',  route: 'shop.index', mega: true },
  { label: 'About', route: 'about' },
  { label: 'Contact', route: 'contact' },
]

export const footerQuickLinks = [
  { label: 'Home',        route: 'home' },
  { label: 'Shop',        route: 'shop.index' },
  { label: 'About Us',    route: 'about' },
  { label: 'Contact',     route: 'contact' },
  { label: 'Track Order', route: 'order.track' },
  { label: 'FAQ',         route: 'faq' },
]
