<template>
  <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
    <span
      :class="[
        'text-sm font-semibold uppercase tracking-wide sm:text-xs',
        step.status === 'complete'
          ? 'dark:text-primary-300'
          : step.status === 'current'
            ? 'text-primary-600 dark:text-primary-300'
            : 'text-neutral-500 dark:text-neutral-100',
        ,
      ]"
      v-text="step.name"
    />

    <span
      class="truncate text-base font-medium text-neutral-500 dark:text-neutral-200 sm:text-sm"
      v-text="step.description"
    />
  </span>
</template>

<script setup>
defineProps({ step: { type: Object, required: true } })
</script>
