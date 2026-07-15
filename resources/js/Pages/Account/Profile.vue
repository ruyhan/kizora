<template>
  <AccountLayout>
    <Head title="My Profile" />

    <div class="space-y-6">

      <div>
        <h1 class="font-display text-2xl font-bold text-charcoal">Profile Settings</h1>
        <p class="text-muted text-sm mt-1">Manage your personal information and password</p>
      </div>

      <!-- Personal Information -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-purple-50">
          <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
            <UserCircleIcon class="w-4.5 h-4.5 text-primary" />
          </div>
          <h2 class="font-display font-semibold text-charcoal">Personal Information</h2>
        </div>

        <form @submit.prevent="updateProfile" class="p-6 space-y-5">
          <!-- Name -->
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Full Name</label>
            <div class="relative">
              <UserIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-muted pointer-events-none" />
              <input v-model="profileForm.name" type="text"
                class="input-field pl-10" :class="{ 'border-error': profileErrors.name }"
                placeholder="Your full name" required>
            </div>
            <p v-if="profileErrors.name" class="text-error text-xs mt-1">{{ profileErrors.name }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Email Address</label>
            <div class="relative">
              <EnvelopeIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-muted pointer-events-none" />
              <input v-model="profileForm.email" type="email"
                class="input-field pl-10" :class="{ 'border-error': profileErrors.email }"
                placeholder="your@email.com" required>
            </div>
            <p v-if="profileErrors.email" class="text-error text-xs mt-1">{{ profileErrors.email }}</p>
          </div>

          <!-- Phone -->
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Phone Number</label>
            <div class="relative">
              <PhoneIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-muted pointer-events-none" />
              <input v-model="profileForm.phone" type="tel"
                class="input-field pl-10" :class="{ 'border-error': profileErrors.phone }"
                placeholder="+8801XXXXXXXXX">
            </div>
            <p v-if="profileErrors.phone" class="text-error text-xs mt-1">{{ profileErrors.phone }}</p>
          </div>

          <!-- Delivery City + Area -->
          <DeliveryAreaPicker
            :cities="deliveryCities"
            :areas="deliveryAreas"
            v-model="profileForm.delivery_area_id"
            :error="profileErrors.delivery_area_id"
            warning="Product is not available in your city yet. You can still save your address." />

          <!-- Full Delivery Address -->
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Full Delivery Address</label>
            <textarea v-model="profileForm.delivery_address" rows="2"
              class="input-field" :class="{ 'border-error': profileErrors.delivery_address }"
              placeholder="House, road, flat, landmark…"></textarea>
            <p v-if="profileErrors.delivery_address" class="text-error text-xs mt-1">{{ profileErrors.delivery_address }}</p>
          </div>

          <div class="pt-1">
            <button type="submit" :disabled="updatingProfile" class="btn-primary py-2.5 px-6 text-sm">
              <span v-if="updatingProfile" class="flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Saving...
              </span>
              <span v-else>Save Changes</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Address Book -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-purple-50">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
              <MapPinIcon class="w-4.5 h-4.5 text-primary" />
            </div>
            <div>
              <h2 class="font-display font-semibold text-charcoal">Address Book</h2>
              <p class="text-xs text-muted">Saved addresses you can pick at checkout</p>
            </div>
          </div>
          <button type="button" @click="startAddAddress"
            class="text-xs font-semibold text-primary px-3 py-2 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
            + Add address
          </button>
        </div>

        <div class="p-6 space-y-3">
          <!-- Saved addresses -->
          <div v-for="a in addresses" :key="a.id"
            class="rounded-xl border p-4 flex items-start justify-between gap-3"
            :class="a.is_default ? 'border-primary/40 bg-purple-50/40' : 'border-purple-100'">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-charcoal">
                {{ a.label }}
                <span v-if="a.is_default" class="badge badge-purple text-[10px] ml-1.5">Default</span>
              </p>
              <p class="text-sm text-charcoal mt-1">{{ a.name }} · {{ a.phone }}</p>
              <p class="text-sm text-muted">{{ a.address_line }}<span v-if="a.city">, {{ a.city }}</span><span v-if="a.postal_code"> {{ a.postal_code }}</span></p>
            </div>
            <div class="flex flex-col items-end gap-1.5 shrink-0">
              <button type="button" @click="startEditAddress(a)" class="text-xs text-primary hover:underline">Edit</button>
              <button v-if="!a.is_default" type="button" @click="makeDefault(a)" class="text-xs text-muted hover:text-primary hover:underline">Set default</button>
              <button type="button" @click="removeAddress(a)" class="text-xs text-error hover:underline">Remove</button>
            </div>
          </div>
          <p v-if="!addresses.length && !addressFormOpen" class="text-sm text-muted text-center py-2">
            No saved addresses yet — add one to speed up checkout.
          </p>

          <!-- Add / edit form -->
          <form v-if="addressFormOpen" @submit.prevent="saveAddress" class="rounded-xl border border-purple-100 bg-purple-50/30 p-4 space-y-3">
            <p class="text-sm font-semibold text-charcoal">{{ addressForm.id ? 'Edit address' : 'New address' }}</p>
            <div class="grid sm:grid-cols-2 gap-3">
              <input v-model="addressForm.label" type="text" class="input-field text-sm" placeholder="Label — e.g. Home, Office">
              <input v-model="addressForm.name" type="text" class="input-field text-sm" placeholder="Recipient name *" required>
              <input v-model="addressForm.phone" type="tel" class="input-field text-sm" placeholder="Phone *" required>
              <input v-model="addressForm.city" type="text" class="input-field text-sm" placeholder="City">
              <input v-model="addressForm.postal_code" type="text" class="input-field text-sm" placeholder="Postal code">
              <label class="flex items-center gap-2 text-sm text-charcoal">
                <input v-model="addressForm.is_default" type="checkbox" class="rounded text-primary"> Default address
              </label>
            </div>
            <textarea v-model="addressForm.address_line" rows="2" class="input-field text-sm" placeholder="House, road, flat, landmark… *" required></textarea>
            <p v-if="addressErrors.address_line || addressErrors.name || addressErrors.phone" class="text-error text-xs">
              {{ addressErrors.address_line || addressErrors.name || addressErrors.phone }}
            </p>
            <div class="flex gap-2">
              <button type="submit" :disabled="savingAddress" class="btn-primary py-2 px-5 text-sm">{{ savingAddress ? 'Saving…' : 'Save address' }}</button>
              <button type="button" @click="addressFormOpen = false" class="btn-secondary py-2 px-5 text-sm">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-purple-50">
          <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
            <LockClosedIcon class="w-4.5 h-4.5 text-primary" />
          </div>
          <h2 class="font-display font-semibold text-charcoal">Change Password</h2>
        </div>

        <form @submit.prevent="updatePassword" class="p-6 space-y-5">
          <!-- Current Password -->
          <div>
            <label class="text-sm font-medium text-charcoal mb-1.5 block">Current Password</label>
            <div class="relative">
              <LockClosedIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-muted pointer-events-none" />
              <input v-model="passwordForm.current_password" type="password"
                class="input-field pl-10" :class="{ 'border-error': passwordErrors.current_password }"
                placeholder="••••••••" required>
            </div>
            <p v-if="passwordErrors.current_password" class="text-error text-xs mt-1">{{ passwordErrors.current_password }}</p>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <!-- New Password -->
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">New Password</label>
              <div class="relative">
                <KeyIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-muted pointer-events-none" />
                <input v-model="passwordForm.password" type="password"
                  class="input-field pl-10" :class="{ 'border-error': passwordErrors.password }"
                  placeholder="••••••••" required>
              </div>
              <p v-if="passwordErrors.password" class="text-error text-xs mt-1">{{ passwordErrors.password }}</p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="text-sm font-medium text-charcoal mb-1.5 block">Confirm Password</label>
              <div class="relative">
                <KeyIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-muted pointer-events-none" />
                <input v-model="passwordForm.password_confirmation" type="password"
                  class="input-field pl-10"
                  placeholder="••••••••" required>
              </div>
            </div>
          </div>

          <div class="pt-1">
            <button type="submit" :disabled="updatingPassword" class="btn-primary py-2.5 px-6 text-sm">
              <span v-if="updatingPassword" class="flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Updating...
              </span>
              <span v-else>Change Password</span>
            </button>
          </div>
        </form>
      </div>

    </div>
  </AccountLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import DeliveryAreaPicker from '@/Components/DeliveryAreaPicker.vue'
import {
  UserCircleIcon,
  UserIcon,
  EnvelopeIcon,
  PhoneIcon,
  LockClosedIcon,
  KeyIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  user:           Object,
  deliveryCities: { type: Array, default: () => [] },
  deliveryAreas:  { type: Array, default: () => [] },
  addresses:      { type: Array, default: () => [] },
})

const profileForm = ref({
  name:             props.user.name,
  email:            props.user.email,
  phone:            props.user.phone,
  delivery_area_id: props.user.delivery_area_id ?? null,
  delivery_address: props.user.delivery_address ?? '',
})
const profileErrors = ref({})
const updatingProfile = ref(false)

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref({})
const updatingPassword = ref(false)

const updateProfile = () => {
  updatingProfile.value = true
  profileErrors.value = {}
  router.post(route('account.profile.update'), profileForm.value, {
    preserveScroll: true,
    onSuccess: () => { updatingProfile.value = false }, // FlashMessage shows success toast
    onError: (errs) => { profileErrors.value = errs; updatingProfile.value = false },
  })
}

// ── Address book ──
const addressFormOpen = ref(false)
const savingAddress = ref(false)
const addressErrors = ref({})
const emptyAddress = () => ({ id: null, label: '', name: props.user.name || '', phone: props.user.phone || '', address_line: '', city: '', postal_code: '', is_default: false })
const addressForm = ref(emptyAddress())

const startAddAddress = () => { addressForm.value = emptyAddress(); addressErrors.value = {}; addressFormOpen.value = true }
const startEditAddress = (a) => { addressForm.value = { ...a }; addressErrors.value = {}; addressFormOpen.value = true }

const saveAddress = () => {
  savingAddress.value = true
  addressErrors.value = {}
  const opts = {
    preserveScroll: true,
    onSuccess: () => { addressFormOpen.value = false },
    onError: (errs) => { addressErrors.value = errs },
    onFinish: () => { savingAddress.value = false },
  }
  if (addressForm.value.id) {
    router.put(route('account.addresses.update', addressForm.value.id), addressForm.value, opts)
  } else {
    router.post(route('account.addresses.store'), addressForm.value, opts)
  }
}

const makeDefault = (a) => {
  router.put(route('account.addresses.update', a.id), { ...a, is_default: true }, { preserveScroll: true })
}

const removeAddress = (a) => {
  if (!confirm(`Remove "${a.label}"?`)) return
  router.delete(route('account.addresses.destroy', a.id), { preserveScroll: true })
}

const updatePassword = () => {
  updatingPassword.value = true
  passwordErrors.value = {}
  router.post(route('account.password'), passwordForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      updatingPassword.value = false
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
      // FlashMessage shows success toast
    },
    onError: (errs) => { passwordErrors.value = errs; updatingPassword.value = false },
  })
}
</script>
