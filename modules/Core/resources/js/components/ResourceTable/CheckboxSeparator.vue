<template>
  <FloatingSeparator :class="condensed ? 'left-8' : 'left-12'" />
</template>

<script setup>
import FloatingSeparator from './FloatingSeparator.vue'

defineProps({ condensed: { type: Boolean, required: true } })
</script>
