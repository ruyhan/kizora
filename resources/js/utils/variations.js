// Normalises a product's variations into display groups, regardless of whether
// they were saved in the new grouped format or the legacy flat format.
//
// New (grouped):  [{ name, type: 'color'|'button', options: [{ value, image }] }]
// Legacy (flat):  [{ type, value, image }]  → grouped by `type`
export function variationGroups(product) {
  if (!product?.has_variations) return []
  const v = Array.isArray(product.variations) ? product.variations : []
  if (!v.length) return []

  // Already grouped
  if (v[0] && Array.isArray(v[0].options)) {
    return v.filter((g) => g && g.name && Array.isArray(g.options) && g.options.length)
  }

  // Legacy flat → group by type
  const byType = {}
  v.forEach((o) => {
    const t = o.type || 'color'
    ;(byType[t] ||= []).push({ value: o.value, image: o.image })
  })
  return Object.entries(byType).map(([t, options]) => ({
    name: t.charAt(0).toUpperCase() + t.slice(1),
    type: t === 'color' ? 'color' : 'button',
    options,
  }))
}

// The first colour group (used on product cards for the swatch + image swap).
export function colorGroup(product) {
  return variationGroups(product).find((g) => g.type === 'color') || null
}

// Build the flat selection payload (for the cart) from a { groupIndex: option } map.
export function buildSelectedVariations(product, selectedMap = {}) {
  return variationGroups(product)
    .map((g, gi) => {
      const o = selectedMap[gi]
      return o ? { name: g.name, type: g.type, value: o.value, label: o.label || o.value, image: o.image || null } : null
    })
    .filter(Boolean)
}
