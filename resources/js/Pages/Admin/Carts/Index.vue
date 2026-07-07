<template>
  <AdminLayout>
    <Head title="Abandoned Carts" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Abandoned Carts</h1>
        <p class="text-muted text-xs mt-1">Carts idle for 1+ hour that never became an order — potential sales to recover.</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-5">
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-4">
        <p class="text-xs text-muted mb-1">Abandoned carts</p>
        <p class="font-display text-2xl font-bold text-charcoal">{{ stats.carts }}</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-4">
        <p class="text-xs text-muted mb-1">Items waiting</p>
        <p class="font-display text-2xl font-bold text-charcoal">{{ stats.items }}</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-purple-50 p-4">
        <p class="text-xs text-muted mb-1">Potential value</p>
        <p class="font-display text-2xl font-bold text-primary">৳{{ Number(stats.value).toLocaleString() }}</p>
      </div>
    </div>

    <!-- Search -->
    <div class="relative max-w-sm mb-5">
      <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
      <input v-model="search" @input="applyDebounced" type="text" placeholder="Search customer name or email…" class="input-field text-sm pl-9 py-2 w-full">
    </div>

    <!-- Cart list -->
    <div class="space-y-4">
      <div v-for="cart in carts" :key="cart.key" class="bg-white rounded-2xl shadow-sm border border-purple-50 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
          <div>
            <p v-if="cart.customer" class="font-semibold text-charcoal text-sm">
              {{ cart.customer.name }}
              <span class="text-muted font-normal text-xs">· {{ cart.customer.email }}</span>
              <span v-if="cart.customer.phone" class="text-muted font-normal text-xs">· {{ cart.customer.phone }}</span>
            </p>
            <p v-else class="font-semibold text-muted text-sm">Guest visitor <span class="font-normal text-xs">(not signed in — no contact details)</span></p>
            <p class="text-xs text-muted mt-0.5">Last activity {{ cart.updated_at }}</p>
          </div>
          <div class="text-right">
            <p class="font-display text-lg font-bold text-charcoal">৳{{ Number(cart.total).toLocaleString() }}</p>
            <p class="text-xs text-muted">{{ cart.item_count }} item{{ cart.item_count !== 1 ? 's' : '' }}</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <div v-for="item in cart.items" :key="item.id" class="flex items-center gap-2.5 bg-purple-50/50 rounded-xl p-2 pr-4">
            <img :src="item.image" alt="" class="w-11 h-11 rounded-lg object-cover bg-white shrink-0">
            <div class="min-w-0">
              <p class="text-xs font-medium text-charcoal truncate max-w-[180px]">{{ item.name }}</p>
              <p class="text-[11px] text-muted">
                {{ item.quantity }} × ৳{{ Number(item.price).toLocaleString() }}
                <span v-if="item.variations?.length" class="text-muted/70">· {{ item.variations.map(v => v.label || v.value).join(', ') }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!carts.length" class="bg-white rounded-2xl shadow-sm border border-purple-50 p-12 text-center">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-3">
          <ShoppingCartIcon class="w-7 h-7 text-primary/40" />
        </div>
        <p class="font-semibold text-charcoal mb-1">No abandoned carts</p>
        <p class="text-muted text-sm">Every active cart either checked out or is still being shopped.</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { MagnifyingGlassIcon, ShoppingCartIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  carts:   { type: Array, default: () => [] },
  stats:   { type: Object, default: () => ({ carts: 0, items: 0, value: 0 }) },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters?.search || '')
let _t
const applyDebounced = () => {
  clearTimeout(_t)
  _t = setTimeout(() => router.get(route('admin.carts.index'), { search: search.value }, { preserveState: true, replace: true }), 350)
}
</script>
