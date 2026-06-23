<template>
  <AdminLayout>
    <Head title="Content" />
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-charcoal font-display">Page content</h1>
      <p class="text-muted text-sm mt-1">Edit the editable copy for every page. Changes save to JSON files and apply instantly — no deploy needed.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <Link v-for="g in groups" :key="g.key" :href="route('admin.content.edit', g.key)"
        class="group bg-white rounded-2xl border border-purple-100 p-5 hover:border-ink hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-start gap-3">
          <span class="w-11 h-11 rounded-xl bg-purple-50 group-hover:bg-ink flex items-center justify-center shrink-0 transition-colors">
            <component :is="icon(g.icon)" class="w-5 h-5 text-primary group-hover:text-white transition-colors" />
          </span>
          <div class="min-w-0">
            <p class="font-semibold text-charcoal">{{ g.label }}</p>
            <p class="text-muted text-xs mt-0.5 leading-relaxed">{{ g.desc }}</p>
          </div>
        </div>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
  Cog6ToothIcon, HomeIcon, ShoppingBagIcon, InformationCircleIcon, EnvelopeIcon,
  QuestionMarkCircleIcon, DocumentTextIcon, RectangleGroupIcon, TruckIcon,
} from '@heroicons/vue/24/outline'

defineProps({ groups: { type: Array, default: () => [] } })

const map = {
  cog: Cog6ToothIcon, home: HomeIcon, bag: ShoppingBagIcon, info: InformationCircleIcon,
  mail: EnvelopeIcon, help: QuestionMarkCircleIcon, doc: DocumentTextIcon, layout: RectangleGroupIcon,
  truck: TruckIcon,
}
const icon = (k) => map[k] ?? DocumentTextIcon
</script>
