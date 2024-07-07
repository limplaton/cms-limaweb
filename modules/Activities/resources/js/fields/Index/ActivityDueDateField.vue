<template>
  <BaseIndexField
    v-slot="{ hasValue }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    {{ formattedDate }}
    <span v-if="!hasValue">&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
import { computed } from 'vue'

import { useDates } from '@/Core/composables/useDates'

const props = defineProps([
  'column',
  'row',
  'field',
  'resourceName',
  'resourceId',
])

const { localizedDate, localizedDateTime, hasTime } = useDates()

const formattedDate = computed(() => {
  const value = props.row[props.column.attribute]

  if (!value) {
    return ''
  }

  return hasTime(value) ? localizedDateTime(value) : localizedDate(value)
})
</script>
