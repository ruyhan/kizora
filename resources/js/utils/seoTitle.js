// Browser-tab title helper for CMS meta titles.
// A meta title set in the admin is shown EXACTLY as typed (no " - Site" suffix);
// the zero-width-space prefix tells app.js's title callback to skip the suffix.
// When no meta title is set, the page's default title (with suffix) is used.
export const EXACT_TITLE_MARK = '\u200B'

export const seoTitle = (metaTitle, fallback) =>
  (metaTitle ? `${EXACT_TITLE_MARK}${metaTitle}` : fallback)
