<template>
  <div class="min-h-screen flex flex-col app-gradient">
    <!-- Soft colour gradient blobs (archive look) -->
    <div class="kz-blob kz-blob-1"></div>
    <div class="kz-blob kz-blob-2"></div>
    <div class="kz-blob kz-blob-3"></div>

    <Navbar />
    <main class="flex-1 flex flex-col pb-20 md:pb-0 relative z-[1]">
      <slot />
    </main>
    <Footer />
    <MobileBottomNav />
    <CartDrawer />
    <QuickView />
    <ChatWidget />
    <FlashMessage />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Navbar.vue'
import Footer from '@/Components/Footer.vue'
import MobileBottomNav from '@/Components/MobileBottomNav.vue'
import CartDrawer from '@/Components/CartDrawer.vue'
import QuickView from '@/Components/product/QuickView.vue'
import ChatWidget from '@/Components/ChatWidget.vue'
import FlashMessage from '@/Components/FlashMessage.vue'
</script>
