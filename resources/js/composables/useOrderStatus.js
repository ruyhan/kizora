/**
 * Reusable order & payment status badge helpers.
 * Import anywhere: import { statusBadge, paymentBadge } from '@/composables/useOrderStatus'
 */

const ORDER_STATUS_MAP = {
  pending:          'badge-warning',   // amber  — awaiting action
  confirmed:        'badge-blue',      // blue   — accepted by store
  processing:       'badge-blue',      // blue   — being prepared
  out_for_delivery: 'badge-teal',      // teal   — on the way
  delivered:        'badge-success',   // green  — delivered
  completed:        'badge-success',   // green  — fully done
  cancelled:        'badge-gray',      // gray   — cancelled
  failed:           'badge-error',     // red    — failed
  refunded:         'badge-orange',    // orange — refunded
  on_hold:          'badge-orange',    // orange — on hold
}

const PAYMENT_STATUS_MAP = {
  pending:    'badge-warning',   // amber
  processing: 'badge-blue',      // blue
  paid:       'badge-success',   // green
  failed:     'badge-error',     // red
  refunded:   'badge-orange',    // orange
  cancelled:  'badge-gray',      // gray
}

/**
 * Returns the badge CSS class for a given order status.
 * Normalises the value (trim, lowercase, spaces→underscores).
 */
export function statusBadge(status) {
  const key = String(status ?? '').trim().toLowerCase().replace(/\s+/g, '_')
  return ORDER_STATUS_MAP[key] ?? 'badge-gray'
}

/**
 * Returns the badge CSS class for a given payment status.
 */
export function paymentBadge(status) {
  const key = String(status ?? '').trim().toLowerCase().replace(/\s+/g, '_')
  return PAYMENT_STATUS_MAP[key] ?? 'badge-gray'
}
