<template>
  <span
    v-if="address.name"
    v-i-tooltip="address.address"
    :class="[
      'text-neutral-700 dark:text-neutral-300',
      { 'border-b border-dashed border-neutral-400': address.address },
    ]"
  >
    {{ address.name }}
  </span>

  <span
    v-else
    class="text-neutral-700 dark:text-neutral-300"
    v-text="address.address"
  />
</template>

<script setup>
defineProps({
  address: { type: Object, required: true },
})
</script>
