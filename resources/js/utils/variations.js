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

// Stable signature for a variation selection — mirrors the server's collapsing
// rule (sort by name, compare name+value) so the UI can tell whether a specific
// selection is already a line in the cart.
export function variationSignature(variations) {
  const v = Array.isArray(variations) ? [...variations] : []
  v.sort((a, b) => String(a?.name || '').localeCompare(String(b?.name || '')))
  return JSON.stringify(v.map((x) => [x?.name || '', x?.value || '']))
}

// Price adjustment (±) carried by the currently selected options.
export function selectionPriceDelta(selectedMap = {}) {
  return Object.values(selectedMap).reduce((sum, o) => {
    if (!o || o.price === '' || o.price == null) return sum
    const p = Number(o.price)
    return sum + (Number.isFinite(p) ? p : 0)
  }, 0)
}

// Sellable stock for the selection — min of product stock and any per-option stock.
export function selectionStock(product, selectedMap = {}) {
  let s = Number(product?.stock ?? 0)
  for (const o of Object.values(selectedMap)) {
    if (o && o.stock !== '' && o.stock != null) s = Math.min(s, Number(o.stock) || 0)
  }
  return Math.max(0, s)
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
