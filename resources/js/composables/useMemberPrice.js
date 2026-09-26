import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Privilege Member pricing for display. Approved members who are logged in see
 * prices with their discount already taken off; everyone else sees regular prices.
 * The actual charge is still calculated by the server at checkout.
 */
export function useMemberPrice() {
  const percent = computed(() => Number(usePage().props.auth?.user?.member_discount) || 0)
  const isMember = computed(() => percent.value > 0)

  const apply = (amount) => {
    const v = Number(amount) || 0
    return isMember.value ? Math.round(v * (100 - percent.value)) / 100 : v
  }

  return { percent, isMember, apply }
}
