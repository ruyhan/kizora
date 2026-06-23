<template>
  <section :class="tinted ? 'bg-[#F4F4F5]' : 'bg-surface'" class="py-12 sm:py-16">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h2 class="section-title">{{ title }}</h2>
        <p v-if="subtitle" class="text-muted mt-1 text-sm sm:text-base">{{ subtitle }}</p>
      </div>
      <div class="flex gap-4 sm:gap-5 overflow-hidden">
        <div v-for="i in 5" :key="i" class="shrink-0 w-[44vw] sm:w-56 lg:w-60">
          <Skeleton height="14rem" rounded="rounded-2xl" />
          <Skeleton width="80%" height="0.9rem" class="mt-3" />
          <Skeleton width="45%" height="0.9rem" class="mt-2" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import Skeleton from '@/Components/ui/Skeleton.vue'
defineProps({ title: String, subtitle: String, tinted: { type: Boolean, default: false } })
</script>
