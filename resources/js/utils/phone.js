/**
 * Bangladeshi mobile numbers (mirror of App\Support\Phone on the server).
 * Valid locally = 11 digits: 01 + operator digit 3–9 + 8 digits.
 */
export const BD_PHONE_MESSAGE = 'Enter a valid 11-digit mobile number (e.g. 01712345678).'

export function normalizeBdPhone(value) {
  let digits = String(value || '').replace(/\D/g, '')
  if (digits.startsWith('880')) digits = digits.slice(3)
  if (!digits.startsWith('0')) digits = '0' + digits
  return /^01[3-9]\d{8}$/.test(digits) ? '+88' + digits : null
}

export const isValidBdPhone = (value) => normalizeBdPhone(value) !== null

/** "+8801712345678" → "01712345678" for display in a national-format field. */
export function toLocalBdPhone(value) {
  const e164 = normalizeBdPhone(value)
  if (e164) return e164.slice(3)
  return String(value || '').replace(/\D/g, '').replace(/^880/, '')
}
