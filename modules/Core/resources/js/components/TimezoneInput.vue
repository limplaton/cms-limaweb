<template>
  <ICustomSelect v-model="model" :input-id="fieldId" :options="timezones">
    <template #option="option">
      {{ timezoneLabel(option) }}
    </template>
  </ICustomSelect>
</template>

<script setup>
import { computedAsync } from '@vueuse/core'

import { useDates } from '../composables/useDates'

defineProps({
  fieldId: { type: String, default: 'timezone' },
})

const model = defineModel()

const { DateTime } = useDates()

const timezones = computedAsync(async () => await Innoclapps.timezones(), [])

function timezoneLabel(option) {
  const dateTimeInZone = DateTime.now().setZone(option.label)
  const offset = dateTimeInZone.offset

  // Convert the offset to hours and minutes
  const offsetHours = Math.floor(Math.abs(offset) / 60)
  const offsetMinutes = Math.abs(offset) % 60

  // Format the offset string
  const offsetString =
    (offset >= 0 ? '+' : '-') +
    offsetHours.toString().padStart(2, '0') +
    ':' +
    offsetMinutes.toString().padStart(2, '0')

  return 'UTC/GMT ' + offsetString + ' ' + option.label
}
</script>
