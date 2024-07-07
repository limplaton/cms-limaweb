<template>
  <div class="mt-2 flex items-center font-normal">
    <span class="mr-1 font-medium text-neutral-800 dark:text-neutral-100">
      {{ field.label }}:
    </span>

    <slot v-bind="slotProps" />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  field: { type: Object, required: true },
  item: { type: Object, required: true },
  formatter: { type: Function, required: true },
})

const slotProps = computed(() => ({
  row: props.item,
  column: props.field,
  formatted: formatted.value,
}))

const formatted = computed(() => props.formatter(props.item, props.field))
</script>
