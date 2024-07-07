<template>
  <span class="shrink-0">
    <span
      class="flex size-10 items-center justify-center rounded-full"
      v-bind="$attrs"
    >
      <slot />
    </span>
  </span>
</template>

<script setup>
defineOptions({ inheritAttrs: false })
</script>
