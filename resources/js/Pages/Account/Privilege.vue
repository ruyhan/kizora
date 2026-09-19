<template>
  <AccountLayout>
    <Head :title="isMember ? 'My Privilege Card' : 'Privilege Membership'" />

    <div class="space-y-6">

      <!-- ═══════════════ APPROVED — virtual card ═══════════════ -->
      <template v-if="isMember">
        <section class="lux-panel">
          <div class="lux-glow"></div>
          <div class="relative grid xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)] gap-8 xl:gap-10 p-5 sm:p-8 lg:p-10">

            <!-- Card stage -->
            <div>
              <div class="flex items-center justify-between gap-3 mb-6">
                <div>
                  <p class="eyebrow">Kizora Privilege</p>
                  <h1 class="font-display text-2xl sm:text-3xl font-semibold text-white mt-1">Welcome, <span class="gold-text">{{ firstName }}</span></h1>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold tracking-wide bg-emerald-400/10 text-emerald-300 border border-emerald-400/25 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ACTIVE
                </span>
              </div>

              <div class="max-w-[520px] mx-auto card-float">
                <PrivilegeCard v-model:flipped="flipped" :card-number="membership.card_number" :holder-name="holder.name"
                  :tier-line1="card.tier_line_1" :tier-line2="card.tier_line_2"
                  :terms-heading="card.terms_heading" :terms="card.terms" shine />
              </div>
              <div class="card-shadow"></div>

              <div class="flex flex-col items-center gap-3 mt-2">
                <div class="inline-flex p-1 rounded-full bg-white/5 border border-white/10 backdrop-blur">
                  <button v-for="side in ['front', 'back']" :key="side" @click="flipped = side === 'back'"
                    :class="(side === 'back') === flipped ? 'gold-btn shadow-lg' : 'text-white/60 hover:text-white'"
                    class="px-6 py-2 rounded-full text-sm font-semibold capitalize transition-all inline-flex items-center gap-2">
                    <ArrowPathRoundedSquareIcon v-if="side === 'back'" class="w-4 h-4" />
                    <CreditCardIcon v-else class="w-4 h-4" />
                    {{ side }}
                  </button>
                </div>
                <p class="text-[11px] text-white/35 tracking-wide">Tap the card to flip it</p>
              </div>
            </div>

            <!-- Details + terms -->
            <div class="space-y-5">
              <div class="rounded-2xl border border-white/10 bg-white/[0.03] divide-y divide-white/10">
                <div class="px-5 py-4">
                  <p class="detail-label">Card holder</p>
                  <p class="text-white font-medium mt-1 uppercase tracking-wide">{{ holder.name }}</p>
                </div>
                <div class="px-5 py-4 flex items-end justify-between gap-3">
                  <div class="min-w-0">
                    <p class="detail-label">Card number</p>
                    <p class="font-display font-bold text-lg sm:text-xl tracking-wider gold-text mt-1 whitespace-nowrap">{{ membership.card_number }}</p>
                  </div>
                  <button @click="copyNumber" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#d4b891] border border-[#d4b891]/30 hover:bg-[#d4b891]/10 transition-colors">
                    <component :is="copied ? CheckIcon : DocumentDuplicateIcon" class="w-4 h-4" /> {{ copied ? 'Copied' : 'Copy' }}
                  </button>
                </div>
                <div class="px-5 py-4 grid grid-cols-2 gap-4">
                  <div>
                    <p class="detail-label">Member since</p>
                    <p class="text-white/90 text-sm mt-1">{{ membership.approved_at }}</p>
                  </div>
                  <div>
                    <p class="detail-label">Tier</p>
                    <p class="text-white/90 text-sm mt-1 capitalize">{{ tierName }}</p>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-[#d4b891]/20 bg-gradient-to-b from-[#d4b891]/[0.07] to-transparent p-5">
                <h2 class="text-[13px] font-semibold tracking-[0.18em] uppercase text-[#ebcda2] mb-4 flex items-center gap-2">
                  <ShieldCheckIcon class="w-4 h-4" /> {{ card.terms_heading }}
                </h2>
                <ol class="space-y-3">
                  <li v-for="(t, i) in card.terms" :key="i" class="flex gap-3 text-sm text-white/75 leading-relaxed">
                    <span class="w-5 h-5 rounded-full border border-[#d4b891]/40 text-[#d4b891] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ i + 1 }}</span>
                    <span>{{ t }}</span>
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </section>

        <div class="rounded-2xl bg-white border border-purple-100/50 p-5 flex items-start gap-3">
          <InformationCircleIcon class="w-5 h-5 text-[#b8955f] shrink-0 mt-0.5" />
          <p class="text-sm text-muted">Show this card or quote your card number when you shop in store or contact us. The name on the card comes from your
            <Link :href="route('account.profile')" class="text-charcoal font-medium underline underline-offset-2">profile</Link>.</p>
        </div>
      </template>

      <!-- ═══════════════ PENDING ═══════════════ -->
      <template v-else-if="status === 'pending'">
        <section class="lux-panel">
          <div class="lux-glow"></div>
          <div class="relative p-6 sm:p-10 grid lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)] gap-10 items-center">
            <div>
              <p class="eyebrow">Kizora Privilege</p>
              <h1 class="font-display text-2xl sm:text-3xl font-semibold text-white mt-2">Your application is <span class="gold-text">under review</span></h1>
              <p class="text-white/60 mt-3 leading-relaxed">Thank you, {{ firstName }}. Our team is reviewing your request. Once approved, your personal virtual Privilege Card will appear right here — we'll email you too.</p>

              <ol class="mt-8 space-y-0">
                <li v-for="(s, i) in steps" :key="s.label" class="flex gap-4">
                  <div class="flex flex-col items-center">
                    <span :class="s.state === 'done' ? 'gold-btn' : s.state === 'current' ? 'border-2 border-[#d4b891] text-[#d4b891] step-pulse' : 'border border-white/15 text-white/30'"
                      class="w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                      <CheckIcon v-if="s.state === 'done'" class="w-4 h-4" />
                      <ClockIcon v-else-if="s.state === 'current'" class="w-4 h-4" />
                      <CreditCardIcon v-else class="w-4 h-4" />
                    </span>
                    <span v-if="i < steps.length - 1" :class="s.state === 'done' ? 'bg-[#d4b891]/60' : 'bg-white/10'" class="w-px flex-1 min-h-8"></span>
                  </div>
                  <div class="pb-6">
                    <p :class="s.state === 'upcoming' ? 'text-white/40' : 'text-white'" class="font-medium text-sm">{{ s.label }}</p>
                    <p class="text-xs text-white/40 mt-0.5">{{ s.sub }}</p>
                  </div>
                </li>
              </ol>

              <button @click="withdraw" :disabled="busy" class="text-xs text-white/40 hover:text-rose-300 underline underline-offset-4 transition-colors">
                Withdraw application
              </button>
            </div>
            <div class="relative">
              <div class="max-w-[420px] mx-auto opacity-60 blur-[1.5px] grayscale-[35%] pointer-events-none">
                <PrivilegeCard :card-number="placeholderNumber" :holder-name="holder.name" :tier-line1="card.tier_line_1"
                  :tier-line2="card.tier_line_2" :click-to-flip="false" :tilt="false" shine />
              </div>
              <div class="absolute inset-0 flex items-center justify-center">
                <span class="px-4 py-2 rounded-full bg-black/70 border border-[#d4b891]/40 text-[#ebcda2] text-xs font-semibold tracking-[0.2em] uppercase backdrop-blur">Awaiting approval</span>
              </div>
            </div>
          </div>
        </section>
      </template>

      <!-- ═══════════════ NOT A MEMBER (new / rejected / revoked) ═══════════════ -->
      <template v-else>
        <section class="lux-panel">
          <div class="lux-glow"></div>
          <div class="relative p-6 sm:p-10 grid lg:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)] gap-10 items-center">
            <div>
              <p class="eyebrow">{{ program.title }}</p>
              <h1 class="font-display text-2xl sm:text-[32px] leading-tight font-semibold text-white mt-2">
                You're not a <span class="gold-text">Privilege Member</span> yet
              </h1>
              <p class="text-white/60 mt-3 leading-relaxed">{{ program.intro }}</p>

              <ul v-if="program.benefits.length" class="mt-6 grid sm:grid-cols-2 gap-3">
                <li v-for="b in program.benefits" :key="b" class="flex items-start gap-2.5 text-sm text-white/80">
                  <span class="w-5 h-5 rounded-full bg-[#d4b891]/15 flex items-center justify-center shrink-0 mt-0.5">
                    <CheckIcon class="w-3 h-3 text-[#ebcda2]" />
                  </span>
                  {{ b }}
                </li>
              </ul>

              <a v-if="program.accepting" href="#apply" class="gold-btn mt-8 inline-flex items-center gap-2 px-7 py-3 rounded-full text-sm font-semibold shadow-lg hover:brightness-105 transition">
                Apply now <ArrowRightIcon class="w-4 h-4" />
              </a>
            </div>
            <div>
              <div class="max-w-[440px] mx-auto card-float">
                <PrivilegeCard :card-number="placeholderNumber" :holder-name="holder.name" :tier-line1="card.tier_line_1"
                  :tier-line2="card.tier_line_2" :terms-heading="card.terms_heading" :terms="card.terms" shine auto-rotate />
              </div>
              <div class="card-shadow card-shadow-spin"></div>
              <p class="text-center text-[11px] text-white/35 tracking-wide">Your card, with your name on it</p>
            </div>
          </div>
        </section>

        <!-- Rejected / revoked notice -->
        <div v-if="status === 'rejected' || status === 'revoked'" class="rounded-2xl border border-rose-100 bg-rose-50/60 p-5 flex gap-3">
          <ExclamationTriangleIcon class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
          <div class="text-sm">
            <p class="font-semibold text-charcoal">
              {{ status === 'rejected' ? `Your previous application${membership.rejected_at ? ' (' + membership.rejected_at + ')' : ''} wasn't approved.` : 'Your Privilege Membership has been suspended.' }}
            </p>
            <p v-if="membership.admin_note" class="text-muted mt-1">Note from Kizora: {{ membership.admin_note }}</p>
            <p class="text-muted mt-1">You're welcome to apply again below.</p>
          </div>
        </div>

        <!-- Application form -->
        <section id="apply" class="bg-white rounded-2xl shadow-sm border border-purple-100/50 overflow-hidden scroll-mt-24">
          <div class="px-5 sm:px-7 py-5 border-b border-purple-50 flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-[#111] flex items-center justify-center"><SparklesIcon class="w-5 h-5 text-[#d4b891]" /></span>
            <div>
              <h2 class="font-display font-semibold text-charcoal text-lg">Apply for Privilege Membership</h2>
              <p class="text-muted text-xs">Takes less than a minute. We'll review and notify you by email.</p>
            </div>
          </div>

          <div v-if="!program.accepting" class="px-5 sm:px-7 py-10 text-center">
            <p class="text-charcoal font-medium">Applications are currently closed.</p>
            <p class="text-muted text-sm mt-1">Please check back soon — new memberships open regularly.</p>
          </div>

          <form v-else @submit.prevent="submit" class="px-5 sm:px-7 py-6 space-y-5">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="lbl">Name on card</label>
                <input :value="holder.name" disabled class="input-field bg-gray-50 text-charcoal/80 uppercase tracking-wide cursor-not-allowed">
                <p class="text-[11px] text-muted mt-1">From your account. <Link :href="route('account.profile')" class="underline">Edit in Profile</Link></p>
              </div>
              <div>
                <label class="lbl">Mobile number <span class="text-accent">*</span></label>
                <input v-model="form.phone" type="tel" maxlength="20" class="input-field" placeholder="01XXXXXXXXX" required>
                <p v-if="form.errors.phone" class="err">{{ form.errors.phone }}</p>
              </div>
            </div>
            <div>
              <label class="lbl">Anything you'd like us to know? <span class="text-muted font-normal">(optional)</span></label>
              <textarea v-model="form.note" rows="3" maxlength="500" class="input-field" placeholder="e.g. I'm a regular customer at your Dhaka store."></textarea>
            </div>

            <div class="rounded-xl bg-[#faf7f2] border border-[#ecdfca] p-4">
              <button type="button" @click="showTerms = !showTerms" class="w-full flex items-center justify-between text-sm font-semibold text-charcoal">
                {{ card.terms_heading }}
                <ChevronDownIcon :class="{ 'rotate-180': showTerms }" class="w-4 h-4 transition-transform" />
              </button>
              <ul v-show="showTerms" class="mt-3 space-y-1.5 list-disc pl-5 text-sm text-muted">
                <li v-for="(t, i) in card.terms" :key="i">{{ t }}</li>
              </ul>
              <label class="flex items-start gap-2.5 mt-3 text-sm text-charcoal cursor-pointer">
                <input v-model="form.agree" type="checkbox" class="w-4 h-4 mt-0.5 accent-ink">
                I have read and agree to the Privilege Card terms &amp; conditions.
              </label>
              <p v-if="form.errors.agree" class="err">{{ form.errors.agree }}</p>
            </div>

            <button type="submit" :disabled="form.processing || !form.agree"
              class="gold-btn w-full sm:w-auto px-8 py-3 rounded-xl text-sm font-semibold inline-flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover:brightness-105 transition">
              <SparklesIcon class="w-4 h-4" /> {{ form.processing ? 'Submitting…' : (status ? 'Apply again' : 'Submit application') }}
            </button>
          </form>
        </section>
      </template>
    </div>
  </AccountLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import PrivilegeCard from '@/Components/PrivilegeCard.vue'
import {
  SparklesIcon, CheckIcon, ClockIcon, CreditCardIcon, ArrowRightIcon, ChevronDownIcon, ShieldCheckIcon,
  DocumentDuplicateIcon, InformationCircleIcon, ExclamationTriangleIcon, ArrowPathRoundedSquareIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  membership: { type: Object, default: null },
  holder: { type: Object, required: true },
  card: { type: Object, required: true },
  program: { type: Object, required: true },
})

const status = computed(() => props.membership?.status || null)
const isMember = computed(() => status.value === 'approved')
const firstName = computed(() => (props.holder.name || '').split(' ')[0])
const tierName = computed(() => [props.card.tier_line_1, props.card.tier_line_2].filter(Boolean).join(' ').toLowerCase())
const placeholderNumber = 'XXXX XXXX XXXXXXX'

const flipped = ref(false)
const showTerms = ref(false)

// ── Apply ──
const form = useForm({ phone: props.membership?.phone || props.holder.phone || '', note: '', agree: false })
const submit = () => form.post(route('account.privilege.apply'), { preserveScroll: true, onSuccess: () => form.reset('note', 'agree') })

// ── Pending ──
const busy = ref(false)
const steps = computed(() => [
  { label: 'Application submitted', sub: props.membership?.applied_at, state: 'done' },
  { label: 'Under review', sub: 'Usually within 1–2 business days', state: 'current' },
  { label: 'Card issued', sub: 'Your virtual card appears here', state: 'upcoming' },
])
const withdraw = () => {
  if (!confirm('Withdraw your Privilege Membership application?')) return
  busy.value = true
  router.delete(route('account.privilege.cancel'), { preserveScroll: true, onFinish: () => { busy.value = false } })
}

// ── Member ──
const copied = ref(false)
const copyNumber = async () => {
  try {
    await navigator.clipboard.writeText(props.membership.card_number.replace(/\s/g, ''))
    copied.value = true
    setTimeout(() => { copied.value = false }, 1800)
  } catch { /* clipboard unavailable */ }
}
</script>

<style scoped>
@reference "../../../css/app.css";

.lux-panel {
  @apply relative rounded-3xl overflow-hidden;
  background: radial-gradient(120% 90% at 85% 0%, #2a2a2e 0%, #121213 45%, #070707 100%);
  box-shadow: 0 30px 60px -30px rgba(0, 0, 0, 0.6), inset 0 0 0 1px rgba(212, 184, 145, 0.14);
}
.lux-glow {
  position: absolute;
  width: 520px; height: 520px;
  right: -140px; top: -220px;
  background: radial-gradient(circle, rgba(212, 184, 145, 0.16), transparent 65%);
  pointer-events: none;
}
.eyebrow { @apply text-[11px] font-semibold uppercase tracking-[0.3em] text-[#d4b891]; }
.detail-label { @apply text-[10px] font-semibold uppercase tracking-[0.2em] text-white/40; }
.lbl { @apply block text-sm font-medium text-charcoal mb-1; }
.err { @apply text-error text-xs mt-1; }

.gold-text {
  background: linear-gradient(90deg, #b8955f, #ebcda2 45%, #d4b891);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.gold-btn {
  background: linear-gradient(135deg, #b8955f 0%, #ebcda2 50%, #c9a770 100%);
  color: #141414;
}

.card-float { animation: float 6s ease-in-out infinite; }
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-8px); }
}
.card-shadow {
  width: 70%; height: 18px; margin: 18px auto 14px;
  background: radial-gradient(ellipse, rgba(0, 0, 0, 0.7), transparent 70%);
  filter: blur(4px);
}
/* Floor shadow narrows as the card turns edge-on (twice per 14s roll) */
.card-shadow-spin { animation: shadow-roll 7s ease-in-out infinite; }
@keyframes shadow-roll {
  0%, 100% { transform: scaleX(1); opacity: 1; }      /* face-on (0° / 180°) */
  50%      { transform: scaleX(0.35); opacity: 0.5; } /* edge-on (90° / 270°) */
}
.step-pulse { animation: step-pulse 2s ease-in-out infinite; }
@keyframes step-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(212, 184, 145, 0.35); }
  50%      { box-shadow: 0 0 0 8px rgba(212, 184, 145, 0); }
}
@media (prefers-reduced-motion: reduce) {
  .card-float, .step-pulse, .card-shadow-spin { animation: none; }
}
</style>
