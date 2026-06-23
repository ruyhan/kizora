import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Permission helper shared across the admin UI.
 *   const { can } = usePermissions()
 *   can('products.create')           // single
 *   can(['orders.edit','orders.view']) // any-of
 * Super Admin is granted everything.
 */
export function usePermissions() {
  const user = computed(() => usePage().props.auth?.user || null)
  const perms = computed(() => user.value?.permissions || [])
  const isSuper = computed(() => !!user.value?.is_super)

  const can = (slug) => {
    if (isSuper.value) return true
    if (!slug) return true
    const list = Array.isArray(slug) ? slug : [slug]
    return list.some((s) => perms.value.includes(s))
  }

  return { can, user, isSuper, permissions: perms }
}
