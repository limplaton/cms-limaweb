<template>
  <component
    :is="as"
    data-slot="label"
    class="block text-base font-medium text-neutral-900 dark:text-white sm:text-sm"
  >
    <span
      v-if="showRequiredMark"
      class="mr-1 text-base text-danger-600 sm:text-sm"
      v-text="'*'"
    />

    <slot>{{ label }}</slot>
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  as: { type: [String, Object], default: 'label' },
  label: String,
  required: Boolean,
})

const showRequiredMark = computed(() => props.required && props.label)
</script>
