<template>
  <div
    class="pc-scene"
    :class="{ 'pc-clickable': clickToFlip && !autoRotate, 'pc-auto': autoRotate }"
    @mousemove="onMouseMove"
    @mouseleave="onMouseLeave"
    @click="clickToFlip && !autoRotate && toggle()"
  >
    <div class="pc-spinner">
    <div class="pc-card" :class="{ 'is-flipped': isFlipped }" :style="cardStyle">

      <!-- FRONT (template image = logo + watermark only; text is live DOM) -->
      <div class="pc-face pc-front" :style="{ backgroundImage: `url(${frontImage})` }">
        <div class="pc-glare" :style="glareStyle"></div>
        <div v-if="shine" class="pc-shine"></div>
        <div class="pc-tier-1">{{ tierLine1 }}</div>
        <div class="pc-tier-2">{{ tierLine2 }}</div>
        <div class="pc-number">{{ formattedNumber }}</div>
        <div class="pc-name">{{ (holderName || '').toUpperCase() }}</div>
      </div>

      <!-- BACK -->
      <div class="pc-face pc-back" :style="{ backgroundImage: `url(${backImage})` }">
        <div class="pc-glare" :style="glareStyle"></div>
        <div class="pc-terms-heading">{{ termsHeading }}</div>
        <ul class="pc-terms-list" :style="termsStyle">
          <li v-for="(line, i) in cleanTerms" :key="i">{{ line }}</li>
        </ul>
      </div>

    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  cardNumber:   { type: String, default: '2606 1510 2100001' },
  holderName:   { type: String, default: 'Card Holder' },
  tierLine1:    { type: String, default: 'PRIVILEGE' },
  tierLine2:    { type: String, default: 'CARD' },
  termsHeading: { type: String, default: 'TERMS & CONDITIONS' },
  terms:        { type: Array, default: () => [] },
  // Controlled flip (v-model:flipped). Falls back to internal state when not bound.
  flipped:      { type: Boolean, default: undefined },
  clickToFlip:  { type: Boolean, default: true },
  tilt:         { type: Boolean, default: true },
  shine:        { type: Boolean, default: false },
  // Slow continuous 3D spin showing both faces (showcase use).
  autoRotate:   { type: Boolean, default: false },
  // Text-free templates (logo + watermark only)
  frontImage:   { type: String, default: '/images/privilege/card-front.png' },
  backImage:    { type: String, default: '/images/privilege/card-back.png' },
})
const emit = defineEmits(['update:flipped'])

const localFlipped = ref(false)
const isFlipped = computed(() => (props.flipped === undefined ? localFlipped.value : props.flipped))
const toggle = () => {
  const next = !isFlipped.value
  localFlipped.value = next
  emit('update:flipped', next)
}

const rotateX = ref(0)
const rotateY = ref(0)
const glareX = ref(50)
const glareY = ref(50)
const glareOpacity = ref(0)

// "2606151021 00001" → "2606 1510 2100001" (4 · 4 · rest, as on the artwork)
const formattedNumber = computed(() => {
  const raw = (props.cardNumber || '').replace(/\s+/g, '')
  if (raw.length <= 8) return raw
  return `${raw.slice(0, 4)} ${raw.slice(4, 8)} ${raw.slice(8)}`
})

const cleanTerms = computed(() => props.terms.map((t) => String(t || '').trim()).filter(Boolean))

// The artwork fits ~13 lines of ~46 chars; shrink the list when content grows past that.
const termsStyle = computed(() => {
  const lines = cleanTerms.value.reduce((n, t) => n + Math.max(1, Math.ceil(t.length / 46)), 0)
  const scale = lines > 13 ? Math.max(0.68, 13 / lines) : 1
  return { fontSize: `${2.222 * scale}cqw`, width: `${56 / Math.max(scale, 0.8)}%` }
})

const canHover = typeof window !== 'undefined' && window.matchMedia?.('(hover: hover)').matches

function onMouseMove(e) {
  if (!props.tilt || !canHover || props.autoRotate) return
  const rect = e.currentTarget.getBoundingClientRect()
  const px = (e.clientX - rect.left) / rect.width
  const py = (e.clientY - rect.top) / rect.height
  const maxTilt = 10
  rotateY.value = (px - 0.5) * maxTilt * 2
  rotateX.value = -(py - 0.5) * maxTilt * 2
  glareX.value = px * 100
  glareY.value = py * 100
  glareOpacity.value = 0.18
}

function onMouseLeave() {
  rotateX.value = 0
  rotateY.value = 0
  glareOpacity.value = 0
}

const cardStyle = computed(() => ({
  transform: `rotateX(${rotateX.value}deg) rotateY(${rotateY.value + (isFlipped.value ? 180 : 0)}deg)`,
}))

const glareStyle = computed(() => ({
  background: `radial-gradient(circle at ${glareX.value}% ${glareY.value}%, rgba(255,255,255,0.55), transparent 45%)`,
  opacity: glareOpacity.value,
}))
</script>

<style scoped>
/*
 * Positions/sizes/colours measured against the original artwork (see
 * Membership Resource/PrivilegeCard.vue). Values are % / cqw of the card so
 * they scale identically at any rendered size.
 */
.pc-scene {
  width: 100%;
  aspect-ratio: 1.586 / 1;
  perspective: 1400px;
  container-type: inline-size;
  user-select: none;
}
.pc-clickable { cursor: pointer; }

.pc-spinner {
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
}
/* Slow 360° roll with a slight forward tilt so it reads as a real 3D card */
.pc-auto .pc-spinner { animation: pc-roll 14s linear infinite; }
@keyframes pc-roll {
  from { transform: rotateX(8deg) rotateY(0deg); }
  to   { transform: rotateX(8deg) rotateY(360deg); }
}

.pc-card {
  position: relative;
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
  transition: transform 0.7s cubic-bezier(0.23, 1, 0.32, 1);
  will-change: transform;
}

.pc-face {
  position: absolute;
  inset: 0;
  border-radius: 3.6cqw;
  background-color: #000;
  background-size: cover;
  background-position: center;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35), inset 0 0 0 1px rgba(255, 255, 255, 0.06);
  overflow: hidden;
  font-family: 'Poppins', sans-serif;
}

.pc-back { transform: rotateY(180deg); }

.pc-glare {
  position: absolute;
  inset: 0;
  pointer-events: none;
  transition: opacity 0.2s ease-out;
  mix-blend-mode: overlay;
}

/* Slow golden sheen sweeping across the front */
.pc-shine {
  position: absolute;
  inset: -50% -100%;
  pointer-events: none;
  background: linear-gradient(105deg, transparent 42%, rgba(235, 205, 162, 0.16) 50%, transparent 58%);
  animation: pc-sweep 6s ease-in-out infinite;
}
@keyframes pc-sweep {
  0%, 55% { transform: translateX(-40%); }
  100%    { transform: translateX(40%); }
}
@media (prefers-reduced-motion: reduce) {
  .pc-shine { animation: none; opacity: 0; }
  .pc-auto .pc-spinner { animation: none; transform: none; }
  .pc-card { transition-duration: 0.01s; }
}

/* ---------- FRONT ---------- */
.pc-tier-1 {
  position: absolute; left: 9.665%; top: 37.026%;
  font-weight: 400; font-size: 5.664cqw; line-height: 1;
  color: #d4b891; white-space: nowrap;
}
.pc-tier-2 {
  position: absolute; left: 9.956%; top: 46.094%;
  font-weight: 400; font-size: 3.708cqw; line-height: 1; letter-spacing: 0.402cqw;
  color: #f0f0f0; white-space: nowrap;
}
.pc-number {
  position: absolute; left: 9.544%; top: 70.185%;
  font-weight: 700; font-size: 3.605cqw; line-height: 1;
  color: #d4b891; white-space: nowrap;
}
.pc-name {
  position: absolute; left: 9.544%; top: 77.172%;
  font-weight: 300; font-size: 3.296cqw; line-height: 1;
  color: #f0f0f0; white-space: nowrap;
}

/* ---------- BACK ---------- */
.pc-terms-heading {
  position: absolute; left: 10.802%; top: 22.554%;
  font-weight: 600; font-size: 3.086cqw; letter-spacing: 0.041cqw; line-height: 1;
  color: #ebcda2; white-space: nowrap;
}
.pc-terms-list {
  position: absolute; left: 10.802%; top: 30.253%;
  width: 56%; margin: 0; padding: 0; list-style: none;
  font-weight: 400; font-size: 2.222cqw; line-height: 1.343;
  color: #ffffff;
}
.pc-terms-list li {
  position: relative;
  padding-left: 0.864em;
}
.pc-terms-list li::before {
  content: '•';
  position: absolute;
  left: 0;
}
</style>
