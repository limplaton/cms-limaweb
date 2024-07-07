<template>
  <TabGroup :selected-index="model" @change="model = $event">
    <slot />
  </TabGroup>
</template>

<script setup>
import { TabGroup } from '@headlessui/vue'

const model = defineModel({ type: Number })
</script>
