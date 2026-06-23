<template>
  <AdminLayout>
    <Head title="Access denied" />
    <div class="min-h-[60vh] flex items-center justify-center">
      <div class="text-center max-w-md">
        <div class="w-20 h-20 rounded-3xl bg-red-50 flex items-center justify-center mx-auto mb-6">
          <LockClosedIcon class="w-10 h-10 text-error" />
        </div>
        <h1 class="font-display text-2xl font-bold text-charcoal mb-2">Access denied</h1>
        <p class="text-muted mb-1">You don't have permission to view this page.</p>
        <p v-if="required?.length" class="text-xs text-muted/70 mb-6">
          Requires: <span class="font-mono">{{ required.join(' or ') }}</span>
        </p>
        <Link :href="route('admin.dashboard')" class="btn-primary inline-flex px-5 py-2.5 text-sm">
          Back to dashboard
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { LockClosedIcon } from '@heroicons/vue/24/outline'

defineProps({ required: { type: Array, default: () => [] } })
</script>
