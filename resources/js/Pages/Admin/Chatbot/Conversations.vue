<template>
  <AdminLayout>
    <Head title="Chatbot Conversations" />
    <ChatbotTabs />

    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input v-model="f.search" @input="applyDebounced" type="text" placeholder="Search conversations…" class="input-field text-sm pl-9 py-2 w-full">
      </div>
      <DateRangeFilter route-name="admin.chatbot.conversations" :filters="filters" :params="f" />
    </div>

    <div class="space-y-3">
      <div v-for="c in conversations.data" :key="c.id" class="bg-white rounded-2xl border border-purple-50 overflow-hidden">
        <button @click="toggle(c.id)" class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-purple-50/30">
          <ChatBubbleLeftRightIcon class="w-5 h-5 text-primary shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-charcoal">{{ c.user }} <span class="text-muted font-normal">· {{ c.messages_count }} messages</span></p>
            <p class="text-muted text-xs">{{ c.created_at }}</p>
          </div>
          <span v-if="c.order" class="badge badge-success text-xs">Ordered: {{ c.order }}</span>
          <ChevronDownIcon class="w-4 h-4 text-muted transition-transform" :class="openId === c.id ? 'rotate-180' : ''" />
        </button>
        <div v-if="openId === c.id" class="px-5 py-4 border-t border-purple-50 bg-purple-50/20 space-y-2 max-h-80 overflow-y-auto">
          <div v-for="(m, i) in c.messages" :key="i" :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
            <div :class="m.role === 'user' ? 'bg-ink text-white' : 'bg-white border border-purple-100 text-charcoal'" class="max-w-[75%] px-3 py-2 rounded-xl text-sm whitespace-pre-line">{{ m.content }}</div>
          </div>
        </div>
      </div>
      <p v-if="!conversations.data.length" class="text-center text-muted text-sm py-12 bg-white rounded-2xl border border-purple-50">No conversations yet.</p>
    </div>
    <Pagination :links="conversations.links" />
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ChatbotTabs from '@/Components/admin/ChatbotTabs.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { ChatBubbleLeftRightIcon, ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ conversations: Object, filters: { type: Object, default: () => ({}) } })

const f = ref({ search: props.filters?.search || '', range: props.filters?.range, from: props.filters?.from, to: props.filters?.to })
let _t
const apply = () => router.get(route('admin.chatbot.conversations'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const applyDebounced = () => { clearTimeout(_t); _t = setTimeout(apply, 350) }
const openId = ref(null)
const toggle = (id) => { openId.value = openId.value === id ? null : id }
</script>
