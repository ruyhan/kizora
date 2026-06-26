<template>
  <AdminLayout>
    <Head title="Email · Templates" />

    <div class="space-y-6 w-full max-w-4xl">
      <SettingsTabs />
      <EmailTabs />

      <div>
        <h1 class="text-2xl font-bold font-display text-charcoal">Email Templates</h1>
        <p class="text-muted text-sm mt-1">Edit the subject and design of every transactional email. Disabled templates are skipped.</p>
      </div>

      <div class="bg-white rounded-2xl border border-purple-100 shadow-sm divide-y divide-purple-50">
        <div v-for="t in templates" :key="t.id" class="flex items-center gap-4 p-4 hover:bg-purple-50/30 transition-colors">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-semibold text-charcoal truncate">{{ t.name }}</p>
              <span class="badge badge-purple text-[10px]">{{ t.group }}</span>
              <span v-if="t.is_custom" class="badge badge-gray text-[10px]">Custom</span>
            </div>
            <p class="text-xs text-muted truncate mt-0.5">{{ t.subject }}</p>
          </div>

          <button type="button" role="switch" :aria-checked="t.is_enabled" @click="toggle(t)"
            :title="t.is_enabled ? 'Enabled' : 'Disabled'"
            :class="t.is_enabled ? 'bg-ink' : 'bg-gray-300'"
            class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors shrink-0">
            <span :class="t.is_enabled ? 'translate-x-[22px]' : 'translate-x-[3px]'"
              class="inline-block w-[18px] h-[18px] bg-white rounded-full shadow transition-transform"></span>
          </button>

          <div class="flex items-center gap-1 shrink-0">
            <Link :href="route('admin.email.templates.edit', t.id)" class="icon-btn" title="Edit">
              <PencilSquareIcon class="w-4 h-4" />
            </Link>
            <button type="button" @click="duplicate(t)" class="icon-btn" title="Duplicate">
              <DocumentDuplicateIcon class="w-4 h-4" />
            </button>
            <button v-if="!t.is_custom" type="button" @click="reset(t)" class="icon-btn" title="Reset to default">
              <ArrowPathIcon class="w-4 h-4" />
            </button>
            <button v-else type="button" @click="destroy(t)" class="icon-btn text-error" title="Delete">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import EmailTabs from '@/Components/admin/EmailTabs.vue'
import { PencilSquareIcon, DocumentDuplicateIcon, ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline'

defineProps({ templates: Array })

const opts = { preserveScroll: true }
const toggle = (t) => router.patch(route('admin.email.templates.toggle', t.id), {}, opts)
const duplicate = (t) => router.post(route('admin.email.templates.duplicate', t.id), {}, opts)
const reset = (t) => { if (confirm(`Reset "${t.name}" to its default design?`)) router.post(route('admin.email.templates.reset', t.id), {}, opts) }
const destroy = (t) => { if (confirm(`Delete "${t.name}"?`)) router.delete(route('admin.email.templates.destroy', t.id), opts) }
</script>

<style scoped>
@reference "../../../../css/app.css";
.icon-btn { @apply w-8 h-8 rounded-lg border border-purple-100 flex items-center justify-center text-muted hover:text-ink hover:bg-purple-50 transition-colors; }
</style>
