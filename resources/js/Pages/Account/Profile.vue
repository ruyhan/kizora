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
} from '@heroicons/vue/24/outline'

const props = defineProps({
  user:           Object,
  deliveryCities: { type: Array, default: () => [] },
  deliveryAreas:  { type: Array, default: () => [] },
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
    onSuccess: () => { updatingProfile.value = false }, // FlashMessage shows success toast
    onError: (errs) => { profileErrors.value = errs; updatingProfile.value = false },
  })
}

const updatePassword = () => {
  updatingPassword.value = true
  passwordErrors.value = {}
  router.post(route('account.password'), passwordForm.value, {
    onSuccess: () => {
      updatingPassword.value = false
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
      // FlashMessage shows success toast
    },
    onError: (errs) => { passwordErrors.value = errs; updatingPassword.value = false },
  })
}
</script>
