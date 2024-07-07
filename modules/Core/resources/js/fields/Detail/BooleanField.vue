<template>
  <BaseDetailField
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <span
      :class="[
        'mt-0.5 inline-block size-4 rounded-full',
        isTruthy ? 'bg-success-400' : 'bg-danger-400',
      ]"
    />
  </BaseDetailField>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps([
  'resource',
  'resourceName',
  'resourceId',
  'field',
  'isFloating',
])

const isTruthy = computed(() => props.field.value === props.field.trueValue)
</script>
