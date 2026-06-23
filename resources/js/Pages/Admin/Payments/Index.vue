<template>
  <AdminLayout>
    <Head title="Payment Gateways" />

    <div class="space-y-6 w-full">

      <SettingsTabs />

      <!-- Page header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold font-display text-charcoal">Payment Gateways</h1>
          <p class="text-muted text-sm mt-1">Configure gateways — only enabled ones appear at checkout</p>
        </div>
        <!-- Gateway overview pills -->
        <div class="hidden sm:flex items-center gap-2">
          <span v-for="gw in gateways" :key="gw.key"
            :class="settings[gw.key]?.enabled === '1' ? 'bg-green-50 text-success border-green-200' : 'bg-gray-50 text-muted border-gray-200'"
            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border">
            <span class="w-1.5 h-1.5 rounded-full"
              :class="settings[gw.key]?.enabled === '1' ? 'bg-success' : 'bg-gray-300'">
            </span>
            {{ gw.label }}
          </span>
        </div>
      </div>

      <!-- Tab bar -->
      <div class="flex overflow-x-auto scrollbar-hide gap-1 border-b border-purple-100 pb-0">
        <button v-for="gw in gateways" :key="gw.key"
          @click="activeTab = gw.key"
          :class="activeTab === gw.key
            ? 'border-b-2 border-primary text-primary font-semibold bg-purple-50/50'
            : 'text-muted hover:text-charcoal hover:bg-gray-50'"
          class="flex items-center gap-2.5 px-5 py-3 text-sm rounded-t-xl transition-all duration-150 whitespace-nowrap shrink-0 -mb-px">

          <!-- Status dot -->
          <span class="w-2 h-2 rounded-full shrink-0"
            :class="settings[gw.key]?.enabled === '1' ? 'bg-success' : 'bg-gray-300'">
          </span>

          <!-- Logo or initial -->
          <img v-if="settings[gw.key]?.logo_url"
            :src="settings[gw.key].logo_url"
            :alt="gw.label"
            class="h-4 object-contain max-w-[50px]">
          <span v-else class="font-bold text-[10px]" :style="{ color: gw.color }">{{ gw.label[0] }}</span>

          {{ gw.label }}
        </button>
      </div>

      <!-- Tab content -->
      <template v-for="gw in gateways" :key="gw.key">
        <GatewaySettings
          v-if="activeTab === gw.key"
          :gateway="gw.key"
          :color="gw.color"
          :title="gw.label"
          :initial="settings[gw.key]"
          @saved="onSaved"
          @logo-saved="onLogoSaved"
          @logo-deleted="onLogoDeleted"
        />
      </template>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import GatewaySettings from './GatewaySettings.vue'

const props = defineProps({ settings: Object })

const gateways = [
  { key: 'bkash',      label: 'bKash',      color: '#E2136E' },
  { key: 'nagad',      label: 'Nagad',      color: '#F05829' },
  { key: 'sslcommerz', label: 'SSLCommerz', color: '#0074BC' },
]

const activeTab = ref('bkash')

// Toast is handled by FlashMessage.vue via the server flash message.
// Do NOT add toast.success() here — that would create a duplicate.
const onSaved      = () => {}
const onLogoSaved  = () => {}
const onLogoDeleted = () => {}
</script>
