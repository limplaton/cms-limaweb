<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <template v-if="hasValue">
      {{ value }}
      <span :class="['text-xs text-neutral-500', column.wrap ? 'block' : '']">
        {{
          DateTime.now()
            .setZone(value)
            .toLocaleString(DateTime.DATETIME_MED_WITH_WEEKDAY)
        }}
      </span>
    </template>

    <span v-else>&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
import { useDates } from '../../composables/useDates'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])

const { DateTime } = useDates()
</script>
