<template>
  <ILink
    class="flex items-center px-4 py-2 font-medium"
    aria-current="step"
    tabindex="-1"
    plain
    @click="$emit('click')"
  >
    <span
      class="flex size-7 shrink-0 items-center justify-center rounded-full border-2 border-success-500 dark:border-success-400"
    >
      <span class="text-success-500 dark:text-success-400">
        <ISpinner
          v-if="requestInProgress"
          class="size-6 text-success-500 dark:text-success-400"
        />

        <Icon
          v-else
          icon="Check"
          class="size-6 text-success-500 dark:text-success-400"
        />
      </span>
    </span>

    <span
      class="ml-4 text-base font-medium text-success-500 dark:text-success-400 sm:text-sm"
    >
      <slot />
    </span>
  </ILink>
</template>

<script setup>
defineProps({ requestInProgress: Boolean })

defineEmits(['click'])
</script>
