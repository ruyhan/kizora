import { ref } from 'vue'

const KEY = 'kz_recently_viewed'
const MAX = 12

const load = () => {
  if (typeof window === 'undefined') return []
  try { return JSON.parse(localStorage.getItem(KEY)) || [] } catch { return [] }
}

// module-level ref so every component shares one reactive list
const items = ref(load())

const compact = (p) => ({
  id: p.id,
  name: p.name,
  slug: p.slug,
  price: p.price,
  discount_price: p.discount_price,
  final_price: p.final_price,
  discount_percentage: p.discount_percentage,
  featured_image_url: p.featured_image_url,
  category: p.category,
  stock: p.stock,
  average_rating: p.average_rating,
  review_count: p.review_count,
})

export function useRecentlyViewed() {
  const track = (p) => {
    if (!p?.slug || typeof window === 'undefined') return
    items.value = [compact(p), ...items.value.filter((x) => x.slug !== p.slug)].slice(0, MAX)
    try { localStorage.setItem(KEY, JSON.stringify(items.value)) } catch { /* ignore quota */ }
  }
  return { items, track }
}
