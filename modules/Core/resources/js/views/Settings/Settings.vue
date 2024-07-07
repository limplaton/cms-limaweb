<template>
  <MainLayout>
    <div class="mx-auto max-w-7xl">
      <div class="lg:grid lg:grid-cols-12 lg:gap-x-5">
        <aside class="lg:col-span-3">
          <SettingsMenu></SettingsMenu>
        </aside>

        <div ref="settingsViewRef" class="sm:hidden"></div>

        <div class="lg:col-span-9">
          <RouterView />
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { onBeforeMount, ref } from 'vue'
import { onBeforeRouteUpdate, useRouter } from 'vue-router'

import { useGate } from '@/Core/composables/useGate'

import SettingsMenu from './SettingsMenu.vue'

const router = useRouter()
const { gate } = useGate()
const settingsViewRef = ref(null)

onBeforeRouteUpdate((to, from, next) => {
  // Scroll on top when some child route from the settings is loaded
  // Helps the user to see the top if navigated too down as well on mobile as
  // the menu is on the top of the page
  if (to.matched && to.matched[0] && to.matched[0].name === 'settings') {
    document.getElementById('main').scrollTo({
      top: settingsViewRef.value.getBoundingClientRect().top,
      behavior: 'smooth',
    })
  }
  next()
})

onBeforeMount(() => {
  if (gate.isRegularUser()) {
    router.push({ path: '/403' })
  }
})
</script>
