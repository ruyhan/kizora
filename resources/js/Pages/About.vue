<template>
  <AppLayout>
    <Head title="About Us" />

    <!-- Hero -->
    <section class="relative py-24 gradient-kizora overflow-hidden">
      <template v-if="page.hero_image">
        <img :src="page.hero_image" class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
        <span class="absolute inset-0 bg-ink/50"></span>
      </template>
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-white blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full bg-white blur-3xl"></div>
      </div>
      <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <h1 class="font-display text-4xl sm:text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
          {{ page.hero_title }}<br>
          <span class="text-gradient-light">{{ page.hero_highlight }}</span>
        </h1>
        <p class="text-white/85 text-xl leading-relaxed max-w-2xl mx-auto">{{ page.hero_subtitle }}</p>
      </div>
    </section>

    <!-- Story -->
    <section class="py-20">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div>
            <span class="badge badge-purple mb-4">Our Story</span>
            <h2 class="section-title mb-6">{{ page.story_title }}</h2>
            <div class="space-y-4 text-muted text-lg leading-relaxed">
              <p v-for="(p, i) in page.story" :key="i">{{ p.text }}</p>
            </div>
          </div>
          <div class="rounded-3xl overflow-hidden bg-linear-to-br from-purple-100 to-purple-50 aspect-square flex items-center justify-center">
            <img v-if="page.story_image" :src="page.story_image" alt="" class="w-full h-full object-cover">
            <div v-else class="text-center">
              <div class="w-28 h-28 rounded-3xl gradient-kizora flex items-center justify-center mx-auto mb-6 shadow-xl shadow-purple-300">
                <ShoppingBagIcon class="w-14 h-14 text-white" />
              </div>
              <p class="font-display text-2xl font-semibold text-charcoal">{{ page.est }}</p>
              <p class="text-muted mt-1">Dhaka, Bangladesh</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Craftsmanship -->
    <section class="py-20 bg-white">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div class="rounded-3xl overflow-hidden bg-linear-to-br from-purple-800 to-primary aspect-square flex items-center justify-center text-center">
            <img v-if="page.craft_image" :src="page.craft_image" class="w-full h-full object-cover" alt="">
            <div v-else class="p-10">
              <div class="w-24 h-24 rounded-3xl bg-white/15 flex items-center justify-center mx-auto mb-6">
                <ShieldCheckIcon class="w-12 h-12 text-white" />
              </div>
              <p class="font-display text-2xl font-bold text-white">1-Year Warranty</p>
              <p class="text-white/70 mt-2">On every product</p>
            </div>
          </div>
          <div>
            <span class="badge badge-purple mb-4">Our Craft</span>
            <h2 class="section-title mb-6">{{ page.craft_title }}</h2>
            <p class="text-muted text-lg leading-relaxed whitespace-pre-line">{{ page.craft_body }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- What We Offer -->
    <section class="py-20">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
          <h2 class="section-title mb-4">What we make</h2>
          <p class="text-muted text-lg">Everyday-carry essentials for school, work, sport and travel.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
          <div v-for="(item, i) in page.offerings" :key="i"
            class="p-6 rounded-2xl bg-purple-50 text-center hover:gradient-kizora hover:text-white group transition-all duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-white/70 group-hover:bg-white/20 flex items-center justify-center mx-auto mb-4 transition-all duration-300 group-hover:scale-110">
              <component :is="offeringIcon(i)" class="w-7 h-7 text-primary group-hover:text-white transition-colors duration-300" />
            </div>
            <h3 class="font-semibold text-charcoal group-hover:text-white mb-2 transition-colors text-sm">{{ item.title }}</h3>
            <p class="text-muted text-xs group-hover:text-white/80 transition-colors leading-relaxed">{{ item.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-20 gradient-kizora">
      <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-8">
          <div class="card rounded-3xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center mb-5">
              <BoltIcon class="w-7 h-7 text-primary" />
            </div>
            <h3 class="font-display text-2xl font-bold text-charcoal mb-4">Our Mission</h3>
            <p class="text-muted leading-relaxed">{{ page.mission }}</p>
          </div>
          <div class="card rounded-3xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center mb-5">
              <EyeIcon class="w-7 h-7 text-amber-600" />
            </div>
            <h3 class="font-display text-2xl font-bold text-charcoal mb-4">Our Vision</h3>
            <p class="text-muted leading-relaxed">{{ page.vision }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 text-center">
      <div class="max-w-2xl mx-auto px-4">
        <h2 class="section-title mb-4">Find your everyday carry</h2>
        <p class="text-muted text-lg mb-8">Explore the collection and gear up for wherever you're headed.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link :href="route('shop.index')" class="btn-primary">Shop now</Link>
          <Link :href="route('contact')" class="btn-secondary">Get in touch</Link>
        </div>
      </div>
    </section>

  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  ShoppingBagIcon, ShieldCheckIcon, AcademicCapIcon, BriefcaseIcon,
  BeakerIcon, FireIcon, TagIcon, BoltIcon, EyeIcon,
} from '@heroicons/vue/24/outline'

defineProps({ page: { type: Object, default: () => ({}) } })

const offeringIcons = [ShoppingBagIcon, BriefcaseIcon, AcademicCapIcon, BeakerIcon, FireIcon, TagIcon]
const offeringIcon = (i) => offeringIcons[i % offeringIcons.length]
</script>

<style scoped>
.text-gradient-light {
  background: linear-gradient(135deg, #FFFFFF, #C5C5CA);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
</style>
