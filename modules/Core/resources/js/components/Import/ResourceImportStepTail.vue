<template>
  <span
    aria-hidden="true"
    :class="[
      'absolute left-0 top-0 h-full w-1 lg:bottom-0 lg:top-auto lg:h-1 lg:w-full',
      isCurrent ? 'bg-primary-600 dark:bg-primary-300' : 'bg-transparent',
    ]"
  />
</template>

<script setup>
defineProps({ isCurrent: { type: Boolean, default: false } })
</script>
