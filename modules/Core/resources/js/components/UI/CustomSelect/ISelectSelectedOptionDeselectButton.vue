<template>
  <button
    type="button"
    class="text-current hover:opacity-60 focus:outline-none"
    :disabled="disabled"
    :title="`Deselect ${label}`"
    :aria-label="`Deselect ${label}`"
    @click.stop="deselect(option)"
  >
    <Icon icon="XSolid" class="size-4" />
  </button>
</template>

<script setup>
defineProps({
  option: { required: true },
  deselect: { type: Function, required: true },
  disabled: Boolean,
  label: { type: [String, Number], required: true },
})
</script>
