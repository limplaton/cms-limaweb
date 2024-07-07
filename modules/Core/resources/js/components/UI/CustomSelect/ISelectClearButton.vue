<template>
  <button
    type="button"
    class="rounded-md border-0 p-0.5 text-neutral-400 outline-none hover:bg-neutral-50 hover:text-neutral-700 focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 active:bg-neutral-50 active:text-neutral-700 dark:hover:bg-neutral-600/20 dark:hover:text-neutral-300 dark:active:bg-neutral-600/20"
    title="Clear Selected"
    aria-label="Clear Selected"
    @click.prevent.stop="$emit('clear')"
  >
    <Icon icon="XSolid" class="size-5 shrink-0 sm:size-4" />
  </button>
</template>

<script setup>
defineEmits(['clear'])
</script>
