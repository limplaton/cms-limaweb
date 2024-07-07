<template>
  <TabPanels class="py-2 sm:py-4">
    <slot />
  </TabPanels>
</template>

<script setup>
import { TabPanels } from '@headlessui/vue'
</script>
