<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <span v-if="hasValue" class="text-base sm:text-sm">
      {{ value }}
      <span class="block text-xs text-neutral-500">
        {{
          DateTime.now()
            .setZone(value)
            .toLocaleString(DateTime.DATETIME_MED_WITH_WEEKDAY)
        }}
      </span>
    </span>

    <span v-else>&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
import { useDates } from '../../composables/useDates'

defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])

const { DateTime } = useDates()
</script>
