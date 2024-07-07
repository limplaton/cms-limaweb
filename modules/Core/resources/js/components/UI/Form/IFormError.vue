<template>
  <p
    v-show="error"
    class="mt-2 text-base/6 text-danger-600 dark:text-danger-500 sm:text-sm/6"
  >
    <slot>{{ error }}</slot>
  </p>
</template>

<script setup>
defineProps({ error: String })
</script>
