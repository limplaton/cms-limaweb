<template>
  <div :class="{ relative: show }">
    <slot />

    <div v-show="show" class="absolute inset-0 z-50">
      <div
        class="absolute inset-0 rounded-lg bg-neutral-100 opacity-60 dark:bg-neutral-700/30"
      />

      <div
        v-if="show"
        class="absolute bottom-1/2 left-1/2 z-20 translate-y-1/2"
      >
        <slot name="overlay">
          <ISpinner class="size-5 text-primary-500 dark:text-neutral-300" />
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import ISpinner from './ISpinner.vue'

defineProps({
  show: Boolean,
})
</script>
