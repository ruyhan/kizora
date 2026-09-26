<template>
  <AdminLayout>
    <Head title="Content" />
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-charcoal font-display">Pages &amp; Content</h1>
        <p class="text-muted text-sm mt-1">Edit the copy, images and sections of every page. Changes apply instantly — no deploy needed.</p>
      </div>
      <div class="relative w-full sm:w-64">
        <MagnifyingGlassIcon class="w-4 h-4 text-muted absolute left-3 top-1/2 -translate-y-1/2" />
        <input v-model="search" type="text" placeholder="Search pages…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-purple-50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-purple-50/40 text-left text-[11px] uppercase tracking-wider text-muted">
            <tr>
              <th class="px-5 py-3">Page</th>
              <th class="px-5 py-3 text-center">Sections</th>
              <th class="px-5 py-3">Last updated</th>
              <th class="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-50">
            <tr v-for="g in filtered" :key="g.key" class="hover:bg-purple-50/30 transition-colors">
              <td class="px-5 py-3.5">
                <Link :href="route('admin.content.edit', g.key)" class="flex items-center gap-3 group">
                  <span class="w-10 h-10 rounded-xl bg-purple-50 group-hover:bg-ink flex items-center justify-center shrink-0 transition-colors">
                    <component :is="icon(g.icon)" class="w-5 h-5 text-primary group-hover:text-white transition-colors" />
                  </span>
                  <span class="min-w-0">
                    <span class="block font-semibold text-charcoal group-hover:text-ink">{{ g.label }}</span>
                    <span class="block text-muted text-xs mt-0.5 leading-relaxed">{{ g.desc }}</span>
                  </span>
                </Link>
              </td>
              <td class="px-5 py-3.5 text-center">
                <span class="inline-flex items-center justify-center min-w-7 px-2 py-0.5 rounded-full bg-purple-50 text-xs font-semibold text-charcoal">{{ g.sections }}</span>
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap">
                <template v-if="g.updated_at">
                  <p class="text-charcoal">{{ g.updated_ago }}</p>
                  <p class="text-[11px] text-muted">{{ g.updated_at }}</p>
                </template>
                <span v-else class="text-xs text-muted">Default content</span>
              </td>
              <td class="px-5 py-3.5">
                <div class="flex items-center justify-end gap-2">
                  <a v-if="g.view_url" :href="g.view_url" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-purple-100 text-xs font-semibold text-muted hover:text-ink hover:border-ink transition-colors"
                    title="Open this page on the website">
                    <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" /> View
                  </a>
                  <Link :href="route('admin.content.edit', g.key)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-ink text-white text-xs font-semibold hover:opacity-90 transition-opacity">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!filtered.length">
              <td colspan="4" class="px-5 py-10 text-center text-muted text-sm">No pages match “{{ search }}”.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <p class="text-xs text-muted mt-4">
      Header, menus and footer are edited under
      <Link :href="route('admin.content.edit', { group: 'header' })" class="text-primary font-semibold hover:underline">Header &amp; Footer</Link><template v-if="can('settings.view')">;
      store details, delivery, product page, invoice and chatbot options under
      <Link :href="route('admin.content.edit', { group: 'settings' })" class="text-primary font-semibold hover:underline">Settings</Link></template>.
    </p>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import {
  Cog6ToothIcon, HomeIcon, ShoppingBagIcon, InformationCircleIcon, EnvelopeIcon,
  QuestionMarkCircleIcon, DocumentTextIcon, RectangleGroupIcon, TruckIcon,
  MagnifyingGlassIcon, PencilSquareIcon, ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ groups: { type: Array, default: () => [] } })
const { can } = usePermissions()

const map = {
  cog: Cog6ToothIcon, home: HomeIcon, bag: ShoppingBagIcon, info: InformationCircleIcon,
  mail: EnvelopeIcon, help: QuestionMarkCircleIcon, doc: DocumentTextIcon, layout: RectangleGroupIcon,
  truck: TruckIcon,
}
const icon = (k) => map[k] ?? DocumentTextIcon

const search = ref('')
const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return q ? props.groups.filter(g => `${g.label} ${g.desc}`.toLowerCase().includes(q)) : props.groups
})
</script>
