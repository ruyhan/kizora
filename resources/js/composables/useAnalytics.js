// Lightweight dataLayer push (GA4 / GTM friendly). No-ops if dataLayer absent.
export function useAnalytics() {
  const push = (event, data = {}) => {
    if (typeof window === 'undefined') return
    window.dataLayer = window.dataLayer || []
    window.dataLayer.push({ event, ...data })
  }
  return { push }
}
