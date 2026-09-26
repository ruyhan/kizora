<template>
  <AdminLayout>
    <Head :title="`Edit · ${label}`" />

    <!-- Settings / Delivery / Product are presented as tabbed "Settings"; other groups keep the back-link header -->
    <SettingsTabs v-if="['settings', 'delivery', 'product'].includes(group)" />
    <HeaderFooterTabs v-else-if="['header', 'megamenu', 'footer'].includes(group)" />
    <div v-else class="flex items-center gap-3 mb-6">
      <Link :href="route('admin.content.index')" class="w-9 h-9 rounded-xl bg-white border border-purple-100 flex items-center justify-center text-muted hover:text-ink">
        <ArrowLeftIcon class="w-4 h-4" />
      </Link>
      <div>
        <h1 class="text-xl font-semibold text-charcoal font-display">{{ label }}</h1>
        <p class="text-muted text-xs">Editing data/{{ group }}.json</p>
      </div>
    </div>

    <form @submit.prevent="submit" class="max-w-3xl space-y-3 pb-24">
      <!-- Expand / collapse all -->
      <div v-if="sectionList.length > 1" class="flex items-center justify-between gap-3 mb-1">
        <p class="text-xs text-muted">{{ sectionList.length }} sections · click a section to open it</p>
        <div class="flex items-center gap-3 text-xs font-semibold">
          <button type="button" @click="setAll(true)" class="text-primary hover:underline">Expand all</button>
          <span class="text-purple-200">|</span>
          <button type="button" @click="setAll(false)" class="text-primary hover:underline">Collapse all</button>
        </div>
      </div>

      <section v-for="sec in sectionList" :key="sec.label"
        class="bg-white rounded-2xl border overflow-hidden transition-colors"
        :class="isOpen(sec) ? 'border-ink/20 shadow-sm' : 'border-purple-100'">
        <!-- Section header (click to expand/collapse) -->
        <button type="button" @click="toggle(sec)" :aria-expanded="isOpen(sec)"
          class="w-full flex items-center gap-3 px-5 py-4 text-left hover:bg-purple-50/40 transition-colors">
          <span class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
            :class="isOpen(sec) ? 'bg-ink text-white' : 'bg-purple-50 text-charcoal'">{{ sec.index }}</span>
          <span class="flex-1 min-w-0">
            <span class="block font-semibold text-charcoal">{{ sec.label }}</span>
            <span class="block text-xs text-muted truncate">{{ sec.summary }}</span>
          </span>
          <span v-if="sec.toggle" class="text-[11px] font-semibold px-2 py-0.5 rounded-full shrink-0"
            :class="form.content[sec.toggle] ? 'bg-emerald-50 text-success' : 'bg-gray-100 text-muted'">
            {{ form.content[sec.toggle] ? 'On' : 'Off' }}
          </span>
          <span v-if="sec.hasError" class="w-2 h-2 rounded-full bg-error shrink-0" title="This section has an error"></span>
          <ChevronDownIcon class="w-5 h-5 text-muted shrink-0 transition-transform duration-200" :class="isOpen(sec) ? 'rotate-180' : ''" />
        </button>

        <div v-show="isOpen(sec)" class="border-t border-purple-100 divide-y divide-purple-50">
      <div v-for="field in sec.items" :key="field.key" class="p-5">
        <label class="block text-sm font-semibold text-charcoal mb-2">{{ field.label }}</label>
        <p v-if="field.help" class="text-muted text-xs -mt-1 mb-3 leading-relaxed">{{ field.help }}</p>

        <!-- scalar types -->
        <input v-if="['text','url'].includes(field.type)" v-model="form.content[field.key]" type="text" class="input-field">
        <input v-else-if="field.type === 'number'" v-model.number="form.content[field.key]" type="number" class="input-field">
        <textarea v-else-if="field.type === 'textarea'" v-model="form.content[field.key]" rows="3" class="input-field"></textarea>
        <div v-else-if="field.type === 'toggle'" class="flex items-center gap-3">
          <button type="button" role="switch" :aria-checked="!!form.content[field.key]"
            @click="form.content[field.key] = !form.content[field.key]"
            :class="form.content[field.key] ? 'bg-ink' : 'bg-gray-300'"
            class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors shrink-0 focus:outline-none focus:ring-2 focus:ring-ink/30">
            <span :class="form.content[field.key] ? 'translate-x-[22px]' : 'translate-x-[3px]'"
              class="inline-block w-[18px] h-[18px] bg-white rounded-full shadow transition-transform"></span>
          </button>
          <span class="text-sm font-medium" :class="form.content[field.key] ? 'text-success' : 'text-muted'">
            {{ form.content[field.key] ? 'Enabled' : 'Disabled' }}
          </span>
        </div>
        <select v-else-if="field.type === 'select'" v-model="form.content[field.key]" class="input-field">
          <option v-for="opt in field.options" :key="optVal(opt)" :value="optVal(opt)">{{ optLabel(opt) }}</option>
        </select>
        <!-- segmented control (options: [{value,label}]) -->
        <div v-else-if="field.type === 'segmented'" class="inline-flex flex-wrap gap-1 p-1 rounded-xl bg-[#F1F1F2]">
          <button v-for="opt in field.options" :key="opt.value" type="button"
            @click="form.content[field.key] = opt.value"
            :class="form.content[field.key] === opt.value ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-charcoal'"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors">{{ opt.label }}</button>
        </div>
        <ColorField v-else-if="field.type === 'color'" v-model="form.content[field.key]" />

        <!-- image type -->
        <div v-else-if="field.type === 'image'" class="flex items-center gap-3">
          <div class="w-20 h-20 rounded-xl border border-purple-100 bg-purple-50/40 flex items-center justify-center overflow-hidden shrink-0">
            <img v-if="form.content[field.key]" :src="form.content[field.key]" class="w-full h-full object-cover" alt="">
            <PhotoIcon v-else class="w-6 h-6 text-muted/40" />
          </div>
          <div class="flex items-center gap-2">
            <button type="button" @click="openPicker(u => form.content[field.key] = u)" class="btn-secondary text-sm px-3 py-2">
              {{ form.content[field.key] ? 'Change' : 'Choose image' }}
            </button>
            <button v-if="form.content[field.key]" type="button" @click="form.content[field.key] = ''" class="text-error text-sm hover:underline">Remove</button>
          </div>
        </div>

        <!-- list type -->
        <div v-else-if="field.type === 'list'" class="space-y-3">
          <div v-for="(item, i) in form.content[field.key]" :key="i" class="rounded-xl border border-purple-100 bg-purple-50/40 p-4">
            <div class="flex items-center justify-between gap-2" :class="itemOpen(item) ? 'mb-3' : ''">
              <button type="button" @click="toggleItem(item)" class="flex items-center gap-2 min-w-0 flex-1 text-left">
                <ChevronRightIcon class="w-4 h-4 text-muted shrink-0 transition-transform duration-200" :class="itemOpen(item) ? 'rotate-90' : ''" />
                <span class="text-xs font-semibold text-muted uppercase tracking-wider shrink-0">#{{ i + 1 }}</span>
                <span class="text-sm font-medium text-charcoal truncate">{{ itemTitle(item, field) }}</span>
              </button>
              <div class="flex items-center gap-1 shrink-0">
                <button type="button" @click="move(field.key, i, -1)" :disabled="i === 0" class="icon-sq" title="Move up"><ChevronUpIcon class="w-4 h-4" /></button>
                <button type="button" @click="move(field.key, i, 1)" :disabled="i === form.content[field.key].length - 1" class="icon-sq" title="Move down"><ChevronDownIcon class="w-4 h-4" /></button>
                <button type="button" @click="duplicateItem(field.key, i)" class="icon-sq" title="Duplicate"><DocumentDuplicateIcon class="w-4 h-4" /></button>
                <button type="button" @click="removeItem(field.key, i)" class="icon-sq text-error" title="Remove"><TrashIcon class="w-4 h-4" /></button>
              </div>
            </div>
            <div v-show="itemOpen(item)" class="space-y-3">
              <template v-for="sub in field.item" :key="sub.key">
              <div v-if="visibleSub(sub, item)">
                <label v-if="sub.type !== 'toggle'" class="block text-xs font-medium text-muted mb-1">{{ sub.label }}</label>
                <textarea v-if="sub.type === 'textarea'" v-model="item[sub.key]" rows="2" class="input-field text-sm"></textarea>
                <select v-else-if="sub.type === 'select'" v-model="item[sub.key]" class="input-field text-sm">
                  <option v-for="opt in sub.options" :key="optVal(opt)" :value="optVal(opt)">{{ optLabel(opt) }}</option>
                </select>
                <select v-else-if="sub.type === 'category'" v-model="item[sub.key]" class="input-field text-sm">
                  <option value="">All categories</option>
                  <option v-for="opt in categorySelectOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <input v-else-if="sub.type === 'number'" v-model.number="item[sub.key]" type="number" class="input-field text-sm">
                <div v-else-if="sub.type === 'image'" class="flex items-center gap-3">
                  <div class="w-16 h-16 rounded-lg border border-purple-100 bg-purple-50/40 flex items-center justify-center overflow-hidden shrink-0">
                    <img v-if="item[sub.key]" :src="item[sub.key]" class="w-full h-full object-cover" alt="">
                    <PhotoIcon v-else class="w-5 h-5 text-muted/40" />
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" @click="openPicker(u => item[sub.key] = u)" class="btn-secondary text-xs px-3 py-1.5">
                      {{ item[sub.key] ? 'Change' : 'Choose image' }}
                    </button>
                    <button v-if="item[sub.key]" type="button" @click="item[sub.key] = ''" class="text-error text-xs hover:underline">Remove</button>
                  </div>
                </div>
                <label v-else-if="sub.type === 'toggle'" class="flex items-center gap-2.5 cursor-pointer">
                  <input type="checkbox" v-model="item[sub.key]" class="w-4 h-4 rounded border-purple-200" style="accent-color:#17181B">
                  <span class="text-sm text-charcoal">{{ sub.label }}</span>
                </label>
                <ColorField v-else-if="sub.type === 'color'" v-model="item[sub.key]" />
                <HeroTypographyField v-else-if="sub.type === 'hero_typography'" :item="item" />
                <input v-else v-model="item[sub.key]" type="text" class="input-field text-sm">
                <p v-if="sub.help" class="text-xs text-muted mt-1 leading-relaxed">{{ sub.help }}</p>
              </div>
              </template>
            </div>
          </div>
          <button type="button" @click="addItem(field)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-ink hover:text-accent transition-colors">
            <PlusIcon class="w-4 h-4" /> {{ field.add_label || `Add ${field.label.replace(/s$/, '')}` }}
          </button>
        </div>

        <!-- categories picker (dynamic checklist from the live catalogue) -->
        <div v-else-if="field.type === 'categories'">
          <p v-if="!categoryOptions.length" class="text-muted text-sm">No categories yet.</p>
          <div v-else class="grid sm:grid-cols-2 gap-x-6 gap-y-1.5">
            <label v-for="opt in categoryOptions" :key="opt.value" class="flex items-center gap-2.5 cursor-pointer py-1">
              <input type="checkbox" :checked="isChecked(field.key, opt.value)" @change="toggleCat(field.key, opt.value)"
                class="w-4 h-4 rounded border-purple-200" style="accent-color:#17181B">
              <span class="text-sm text-charcoal">{{ opt.label }}</span>
            </label>
          </div>
          <p class="text-muted text-xs pt-2">
            {{ (form.content[field.key] || []).length ? `${form.content[field.key].length} selected` : 'None selected → showing all' }}
          </p>
        </div>

        <!-- ordered category columns (mega menu) -->
        <CategoryColumnsField v-else-if="field.type === 'category_columns'" v-model="form.content[field.key]" :options="categoryOptions" />

        <!-- delivery charges (per-city manager; each change persists immediately via its own endpoints) -->
        <DeliveryChargesField v-else-if="field.type === 'delivery_charges'" :cities="deliveryCities" />
        <p v-if="form.errors[`content.${field.key}`]" class="text-error text-xs mt-2">{{ form.errors[`content.${field.key}`] }}</p>
      </div>
        </div>
      </section>

      <!-- sticky save bar -->
      <div class="fixed bottom-0 inset-x-0 md:left-64 bg-white border-t border-purple-100 px-6 py-3 flex items-center justify-end gap-3 z-20">
        <span v-if="form.recentlySuccessful" class="text-success text-sm font-medium">Saved ✓</span>
        <Button type="submit" :loading="form.processing">Save changes</Button>
      </div>

      <MediaPicker :open="picker.open" @close="picker.open = false" @select="onPicked" />
    </form>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/Button.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import SettingsTabs from '@/Components/admin/SettingsTabs.vue'
import HeaderFooterTabs from '@/Components/admin/HeaderFooterTabs.vue'
import CategoryColumnsField from '@/Components/admin/CategoryColumnsField.vue'
import ColorField from '@/Components/ui/ColorField.vue'
import HeroTypographyField from '@/Components/admin/HeroTypographyField.vue'
import DeliveryChargesField from '@/Components/admin/DeliveryChargesField.vue'
import { ArrowLeftIcon, ChevronUpIcon, ChevronDownIcon, ChevronRightIcon, TrashIcon, PlusIcon, PhotoIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  group:  String,
  label:  String,
  fields: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  values: { type: Object, default: () => ({}) },
  categoryOptions: { type: Array, default: () => [] },
  categorySelectOptions: { type: Array, default: () => [] },
  deliveryCities:  { type: Array, default: () => [] },
})

const form = useForm({ content: JSON.parse(JSON.stringify(props.values)) })

// Conditional field visibility. A field may declare `show_if` as a single
// { key, value } condition or an array of conditions (all must match).
const visible = (field) => {
  if (!field.show_if) return true
  const conds = field.show_if.key !== undefined ? [field.show_if] : field.show_if
  return conds.every((c) => String(form.content[c.key]) === String(c.value))
}

// ── Collapsible sections ──
// `sections` comes from config/content.php: [{ label, toggle?, fields: [keys] }].
const fieldByKey = computed(() => Object.fromEntries(props.fields.map(f => [f.key, f])))
const summaryOf = (items) => items.map(f => f.type === 'list'
  ? `${f.label} (${(form.content[f.key] || []).length})`
  : f.label.replace(/^.*? — /, '')).join(' · ')
const sectionList = computed(() => props.sections
  .map(s => {
    const items = s.fields.map(k => fieldByKey.value[k]).filter(Boolean).filter(visible)
    return {
      ...s,
      items,
      summary: summaryOf(items),
      hasError: s.fields.some(k => Object.keys(form.errors).some(e => e === `content.${k}` || e.startsWith(`content.${k}.`))),
    }
  })
  .filter(s => s.items.length)
  .map((s, i) => ({ ...s, index: i + 1 })))

// Open/closed state is remembered per page for this browser; the first section starts open.
const storeKey = `kz-content-open:${props.group}`
const readOpen = () => {
  try {
    const saved = JSON.parse(localStorage.getItem(storeKey) || 'null')
    if (Array.isArray(saved)) return saved
  } catch { /* storage unavailable */ }
  return props.sections.length ? [props.sections[0].label] : []
}
const openSections = ref(readOpen())
const persistOpen = () => { try { localStorage.setItem(storeKey, JSON.stringify(openSections.value)) } catch { /* ignore */ } }
const isOpen = (sec) => openSections.value.includes(sec.label) || sec.hasError
const toggle = (sec) => {
  openSections.value = isOpen(sec)
    ? openSections.value.filter(l => l !== sec.label)
    : [...openSections.value, sec.label]
  persistOpen()
}
const setAll = (open) => { openSections.value = open ? sectionList.value.map(s => s.label) : []; persistOpen() }

// Same conditional visibility for a `list` field's sub-fields, evaluated
// against that row's own values (e.g. only show an option for a given `sort`).
const visibleSub = (sub, item) => {
  if (!sub.show_if) return true
  const conds = sub.show_if.key !== undefined ? [sub.show_if] : sub.show_if
  return conds.every((c) => String(item?.[c.key]) === String(c.value))
}

// select options accept either plain strings or { value, label } objects
const optVal = (o) => (o && typeof o === 'object') ? o.value : o
const optLabel = (o) => (o && typeof o === 'object') ? o.label : o

// categories checklist (stores an ordered array of selected slugs; empty = show all)
const isChecked = (key, val) => Array.isArray(form.content[key]) && form.content[key].includes(val)
const toggleCat = (key, val) => {
  if (!Array.isArray(form.content[key])) form.content[key] = []
  const arr = form.content[key]
  const i = arr.indexOf(val)
  if (i === -1) arr.push(val); else arr.splice(i, 1)
}

const blankItem = (field) => Object.fromEntries((field.item || []).map((s) => [
  s.key,
  s.default !== undefined ? s.default : (s.type === 'toggle' ? false : ''),
]))
const addItem = (field) => {
  if (!Array.isArray(form.content[field.key])) form.content[field.key] = []
  form.content[field.key].push(blankItem(field))
  openItems.value.add(form.content[field.key].at(-1))   // a new item opens so it can be filled in
}

// Each list item collapses to a one-line header; tracked by object so reordering keeps its state.
const openItems = ref(new Set())
const itemOpen = (item) => openItems.value.has(item)
const toggleItem = (item) => { itemOpen(item) ? openItems.value.delete(item) : openItems.value.add(item) }
const TITLE_KEYS = ['title', 'label', 'question', 'name', 'heading', 'text', 'platform']
const itemTitle = (item, field) => {
  const key = TITLE_KEYS.find(k => typeof item?.[k] === 'string' && item[k].trim())
    ?? (field.item || []).find(s => ['text', undefined].includes(s.type) && typeof item?.[s.key] === 'string' && item[s.key].trim())?.key
  return key ? item[key] : 'Untitled'
}
const removeItem = (key, i) => form.content[key].splice(i, 1)
// Copy an item (all its settings) right after the original and open it for editing.
const duplicateItem = (key, i) => {
  const arr = form.content[key]
  arr.splice(i + 1, 0, JSON.parse(JSON.stringify(arr[i])))
  openItems.value.add(arr[i + 1])
}
const move = (key, i, dir) => {
  const arr = form.content[key]; const j = i + dir
  if (j < 0 || j >= arr.length) return
  ;[arr[i], arr[j]] = [arr[j], arr[i]]
}

// Shared Media Library picker for image fields
const picker = ref({ open: false, apply: null })
const openPicker = (apply) => { picker.value = { open: true, apply } }
const onPicked = (item) => {
  if (picker.value.apply && item?.file_url) picker.value.apply(item.file_url)
  picker.value.open = false
}

const submit = () => form.post(route('admin.content.update', props.group), { preserveScroll: true })
</script>

<style scoped>
@reference "../../../../css/app.css";
.icon-sq { @apply w-7 h-7 rounded-lg bg-white border border-purple-100 flex items-center justify-center text-muted hover:text-ink disabled:opacity-40 transition-colors; }
</style>
