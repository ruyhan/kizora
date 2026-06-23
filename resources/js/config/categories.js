// Single source of truth for category navigation (mega menu + mobile drawer + footer).
// Edit here — components read from this, nothing is hardcoded in markup.

export const megaMenuGroups = [
  {
    title: 'Bags',
    items: [
      { name: 'Backpacks',         slug: 'backpacks' },
      { name: 'Laptop Backpacks',  slug: 'laptop-backpacks' },
      { name: 'School Bags',       slug: 'school-bags' },
      { name: "Kids' Trolley Bags", slug: 'kids-trolley-bags' },
    ],
  },
  {
    title: 'Drinkware',
    items: [
      { name: 'Water Bottles',    slug: 'water-bottles' },
      { name: 'Thermos & Flasks', slug: 'thermos-flasks' },
    ],
  },
  {
    title: 'Accessories',
    items: [
      { name: 'Accessories', slug: 'accessories' },
    ],
  },
]

// Featured promo tile shown on the right of the desktop mega-menu panel.
export const megaMenuPromo = {
  eyebrow: 'New in',
  title: 'The 2026 carry collection',
  cta: 'Shop new arrivals',
  categorySlug: 'backpacks',
  // image is optional; the panel falls back to a tonal placeholder if absent
  image: null,
}

// Flat list (mobile drawer / footer)
export const allCategories = megaMenuGroups.flatMap((g) => g.items)
