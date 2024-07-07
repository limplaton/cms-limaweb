<template>
  <span
    :class="[
      index !== 0 ? 'lg:pl-9' : '',
      'flex items-start px-6 py-5 text-sm font-medium',
    ]"
  >
    <slot />
  </span>
</template>

<script setup>
defineProps({ index: { type: Number, required: true } })
</script>
