<template>
  <img :class="['inline-block rounded-full', sizes[size]]" :src="src" />
</template>

<script setup>
defineProps({
  src: { type: String, required: true },
  size: {
    default: 'sm',
    type: String,
    validator: value => ['xs', 'sm', 'md', 'lg'].includes(value),
  },
})

const sizes = {
  xs: 'size-5',
  sm: 'size-6',
  md: 'size-7',
  lg: 'size-8',
}
</script>
