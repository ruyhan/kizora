// Formats plain-text policy bodies (Privacy / Terms) into clean, readable HTML.
// Admins type paragraphs separated by blank lines and bullets starting with
// ● • - or * — this turns that into proper <p> paragraphs and <ul> lists so
// long-form legal content reads well, while keeping the stored data as plain text.

// Escape HTML, then apply lightweight inline markdown: **bold**.
const inline = (s) => String(s)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')

// Lines that begin a bullet item (●, •, ‣, ▪, ◦, ·, -) optionally followed by a tab/space.
// `*` is deliberately excluded so it doesn't clash with **bold**.
const BULLET = /^[●•‣▪◦·\-]\s*\t?\s*(.*)$/

export function formatPolicyBody(raw) {
  if (!raw) return ''
  const lines = String(raw).split(/\r?\n/)
  let html = ''
  let inList = false
  const closeList = () => { if (inList) { html += '</ul>'; inList = false } }

  for (const line of lines) {
    const t = line.trim()
    if (t === '') { closeList(); continue }

    const m = t.match(BULLET)
    if (m) {
      if (!inList) { html += '<ul>'; inList = true }
      html += `<li>${inline(m[1])}</li>`
      continue
    }

    closeList()
    html += `<p>${inline(t)}</p>`
  }
  closeList()
  return html
}
