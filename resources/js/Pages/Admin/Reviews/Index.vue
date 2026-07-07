<template>
  <AdminLayout>
    <Head title="Reviews" />

    <div class="space-y-6">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <StarSolid class="w-6 h-6 text-amber-400" />
          <h1 class="font-display text-2xl font-bold text-charcoal">Product Reviews</h1>
        </div>
        <DateRangeFilter route-name="admin.reviews.index" :filters="filters" :params="cleaned()" />
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-purple-100 p-4">
          <p class="text-xs text-muted">Total</p>
          <p class="text-2xl font-bold text-charcoal">{{ stats.total }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-purple-100 p-4">
          <p class="text-xs text-muted">Published</p>
          <p class="text-2xl font-bold text-success">{{ stats.approved }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-purple-100 p-4">
          <p class="text-xs text-muted">Hidden / pending</p>
          <p class="text-2xl font-bold text-amber-600">{{ stats.pending }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-purple-100 p-4">
          <p class="text-xs text-muted">Average rating</p>
          <p class="text-2xl font-bold text-charcoal flex items-center gap-1">{{ stats.avg }} <StarSolid class="w-5 h-5 text-amber-400" /></p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-purple-100 p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="f.search" type="text" class="input-field" placeholder="Search product, reviewer or text…" @keyup.enter="apply">
        <SelectMenu v-model="f.status" :options="statusOptions" placeholder="All statuses" />
        <SelectMenu v-model="f.rating" :options="ratingOptions" placeholder="All ratings" />
        <div class="flex gap-2 justify-end">
          <button type="button" @click="reset" class="btn-secondary text-sm px-4">Clear</button>
          <button type="button" @click="apply" class="btn-primary text-sm px-5">Apply</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-purple-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[860px]">
            <thead>
              <tr class="text-left text-xs text-muted uppercase tracking-wide border-b border-purple-100 bg-purple-50/40">
                <th class="px-4 py-3 w-10"><input v-if="canManage" type="checkbox" :checked="allOn" @change="toggleAll" class="kz-check"></th>
                <th class="px-4 py-3 font-semibold">Product</th>
                <th class="px-4 py-3 font-semibold">Reviewer</th>
                <th class="px-4 py-3 font-semibold">Rating</th>
                <th class="px-4 py-3 font-semibold">Review</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Date</th>
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-purple-50">
              <tr v-for="r in reviews.data" :key="r.id" class="hover:bg-purple-50/30 align-top">
                <td class="px-4 py-3"><input v-if="canManage" type="checkbox" :value="r.id" v-model="selected" class="kz-check"></td>
                <td class="px-4 py-3 font-medium text-charcoal whitespace-nowrap max-w-[180px] truncate">{{ r.product }}</td>
                <td class="px-4 py-3 text-muted whitespace-nowrap">
                  <p class="text-charcoal">{{ r.reviewer }}</p>
                  <p v-if="r.email" class="text-[11px] text-muted">{{ r.email }}</p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span class="inline-flex">
                    <StarSolid v-for="n in 5" :key="n" class="w-4 h-4" :class="n <= r.rating ? 'text-amber-400' : 'text-gray-200'" />
                  </span>
                </td>
                <td class="px-4 py-3 text-charcoal max-w-[280px]">
                  <p class="line-clamp-2">{{ r.comment }}</p>
                  <p v-if="r.admin_reply" class="mt-1 text-[11px] text-primary font-medium">↩ Replied: <span class="text-muted font-normal">{{ r.admin_reply }}</span></p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span :class="r.is_approved ? 'badge-success' : 'badge-gray'" class="badge text-[11px]">
                    {{ r.is_approved ? 'Published' : 'Hidden' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-muted text-xs whitespace-nowrap">{{ r.created_at }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div v-if="canManage" class="inline-flex items-center gap-2">
                    <button type="button" @click="toggle(r)" :title="r.is_approved ? 'Hide' : 'Publish'"
                      class="icon-btn" :class="r.is_approved ? 'text-amber-600' : 'text-success'">
                      <EyeSlashIcon v-if="r.is_approved" class="w-4 h-4" />
                      <CheckCircleIcon v-else class="w-4 h-4" />
                    </button>
                    <button type="button" @click="openReply(r)" class="icon-btn" :class="r.admin_reply ? 'text-primary' : 'text-muted'" title="Reply publicly"><ChatBubbleLeftIcon class="w-4 h-4" /></button>
                    <button type="button" @click="openEdit(r)" class="icon-btn text-primary" title="Edit"><PencilSquareIcon class="w-4 h-4" /></button>
                    <button type="button" @click="destroy(r)" class="icon-btn text-error" title="Delete"><TrashIcon class="w-4 h-4" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!reviews.data.length"><td colspan="8" class="px-4 py-12 text-center text-muted">No reviews found.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="reviews.links && reviews.links.length > 3" class="flex flex-wrap gap-1 justify-center">
        <component :is="link.url ? 'button' : 'span'" v-for="(link, i) in reviews.links" :key="i"
          @click="link.url && go(link.url)"
          :class="[link.active ? 'bg-ink text-white' : 'text-muted hover:text-ink', !link.url && 'opacity-40 cursor-default']"
          class="px-3 py-1.5 rounded-lg text-sm font-medium" v-html="link.label" />
      </div>
    </div>

    <!-- Edit modal -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="modal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="modal.open = false"></div>
          <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-border overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
              <h3 class="font-display font-semibold text-charcoal">Edit review</h3>
              <button @click="modal.open = false" class="text-muted hover:text-ink"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <div class="p-5 space-y-4">
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Rating</label>
                <div class="flex gap-1">
                  <button v-for="n in 5" :key="n" type="button" @click="form.rating = n">
                    <StarSolid class="w-7 h-7" :class="n <= form.rating ? 'text-amber-400' : 'text-gray-300'" />
                  </button>
                </div>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Review</label>
                <textarea v-model="form.comment" rows="4" class="input-field"></textarea>
              </div>
              <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox" v-model="form.is_approved" class="kz-check">
                <span class="text-sm text-charcoal">Published (visible on the storefront)</span>
              </label>
            </div>
            <div class="px-5 py-4 border-t border-border flex justify-end gap-2">
              <button @click="modal.open = false" class="btn-secondary text-sm px-4">Cancel</button>
              <button @click="save" :disabled="saving" class="btn-primary text-sm px-5 disabled:opacity-60">{{ saving ? 'Saving…' : 'Save' }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Reply modal -->
    <Teleport to="body">
      <Transition name="kz-fade">
        <div v-if="replyModal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="replyModal.open = false"></div>
          <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-border overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
              <h3 class="font-display font-semibold text-charcoal">Reply to review</h3>
              <button @click="replyModal.open = false" class="text-muted hover:text-ink"><XMarkIcon class="w-5 h-5" /></button>
            </div>
            <div class="p-5 space-y-3">
              <div class="rounded-xl bg-purple-50/60 border border-purple-100 p-3">
                <p class="text-xs text-muted line-clamp-3">"{{ replyModal.comment }}"</p>
              </div>
              <div>
                <label class="text-sm font-medium text-charcoal mb-1.5 block">Your public reply</label>
                <textarea v-model="replyModal.text" rows="4" maxlength="1000" class="input-field" placeholder="e.g. Thank you for the feedback! …"></textarea>
                <p class="text-xs text-muted mt-1">Shown under the review on the product page. Clear the text and save to remove the reply.</p>
              </div>
            </div>
            <div class="px-5 py-4 border-t border-border flex justify-end gap-2">
              <button @click="replyModal.open = false" class="btn-secondary text-sm px-4">Cancel</button>
              <button @click="saveReply" :disabled="replySaving" class="btn-primary text-sm px-5 disabled:opacity-60">{{ replySaving ? 'Saving…' : 'Save reply' }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <BulkDeleteBar v-if="canManage" :count="selected.length" :busy="bulkDeleting" @confirm="bulkDelete" @clear="selected = []" />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SelectMenu from '@/Components/ui/SelectMenu.vue'
import BulkDeleteBar from '@/Components/admin/BulkDeleteBar.vue'
import DateRangeFilter from '@/Components/admin/DateRangeFilter.vue'
import { useToast } from 'vue-toastification'
import { usePermissions } from '@/composables/usePermissions'
import { PencilSquareIcon, TrashIcon, XMarkIcon, EyeSlashIcon, CheckCircleIcon, ChatBubbleLeftIcon } from '@heroicons/vue/24/outline'
import { StarIcon as StarSolid } from '@heroicons/vue/24/solid'

const props = defineProps({ reviews: Object, filters: Object, stats: Object })

const toast = useToast()
const { can } = usePermissions()
const canManage = can('products.edit')

const f = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  rating: props.filters.rating || '',
})
const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'approved', label: 'Published' },
  { value: 'pending', label: 'Hidden' },
]
const ratingOptions = [{ value: '', label: 'All ratings' }, ...[5, 4, 3, 2, 1].map(n => ({ value: String(n), label: `${n} star${n > 1 ? 's' : ''}` }))]

const cleaned = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v))
const dateParams = () => props.filters?.range === 'custom'
  ? { from: props.filters.from, to: props.filters.to }
  : { range: props.filters?.range }
const apply = () => router.get(route('admin.reviews.index'), { ...cleaned(), ...dateParams() }, { preserveState: true, preserveScroll: true, replace: true })
const reset = () => { f.search = ''; f.status = ''; f.rating = ''; apply() }
const go = (url) => router.get(url, {}, { preserveState: true, preserveScroll: true })

// Bulk
const selected = ref([])
const bulkDeleting = ref(false)
const allOn = computed(() => props.reviews.data.length > 0 && selected.value.length === props.reviews.data.length)
const toggleAll = (e) => { selected.value = e.target.checked ? props.reviews.data.map(r => r.id) : [] }
const bulkDelete = () => {
  bulkDeleting.value = true
  router.post(route('admin.reviews.bulk-destroy'), { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { selected.value = [] },
    onError: () => toast.error('Bulk delete failed.'),
    onFinish: () => { bulkDeleting.value = false },
  })
}

const toggle = (r) => router.patch(route('admin.reviews.toggle', r.id), {}, { preserveScroll: true })
const destroy = (r) => {
  if (confirm('Delete this review permanently?')) {
    router.delete(route('admin.reviews.destroy', r.id), { preserveScroll: true })
  }
}

// Edit modal
const modal = reactive({ open: false, id: null })
const form = reactive({ rating: 5, comment: '', is_approved: true })
const saving = ref(false)
const openEdit = (r) => {
  modal.open = true; modal.id = r.id
  form.rating = r.rating; form.comment = r.comment; form.is_approved = r.is_approved
}
const save = () => {
  saving.value = true
  router.put(route('admin.reviews.update', modal.id), { ...form }, {
    preserveScroll: true,
    onSuccess: () => { modal.open = false },
    onError: () => toast.error('Could not save review.'),
    onFinish: () => { saving.value = false },
  })
}

// Public reply modal
const replyModal = reactive({ open: false, id: null, comment: '', text: '' })
const replySaving = ref(false)
const openReply = (r) => {
  replyModal.open = true; replyModal.id = r.id
  replyModal.comment = r.comment; replyModal.text = r.admin_reply || ''
}
const saveReply = () => {
  replySaving.value = true
  router.patch(route('admin.reviews.reply', replyModal.id), { admin_reply: replyModal.text }, {
    preserveScroll: true,
    onSuccess: () => { replyModal.open = false },
    onError: () => toast.error('Could not save reply.'),
    onFinish: () => { replySaving.value = false },
  })
}
</script>

<style scoped>
@reference "../../../../css/app.css";
.icon-btn { @apply w-8 h-8 rounded-lg border border-purple-100 inline-flex items-center justify-center hover:bg-purple-50 transition-colors; }
.kz-fade-enter-active, .kz-fade-leave-active { transition: opacity .2s ease; }
.kz-fade-enter-from, .kz-fade-leave-to { opacity: 0; }
</style>
