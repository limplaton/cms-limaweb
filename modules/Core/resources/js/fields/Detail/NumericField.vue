<template>
  <BaseDetailField
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData" />
    </template>

    <slot name="numeric-field" :formatted-value="formattedValue">
      {{ formattedValue }}
    </slot>
  </BaseDetailField>
</template>

<script setup>
import { computed } from 'vue'
import isNil from 'lodash/isNil'

import { useAccounting } from '../../composables/useAccounting'

const props = defineProps([
  'resource',
  'resourceName',
  'resourceId',
  'field',
  'isFloating',
])

const { formatMoney, formatNumber } = useAccounting()

const formattedValue = computed(() => {
  let value = props.field.value

  if (isNil(value)) {
    value = ''
  }

  if (props.field.currency) {
    return formatMoney(value, props.field.currency)
  }

  const formatted = formatNumber(value, {
    precision: props.field.attributes?.precision || 2,
  })

  return (
    (props.field.prependText || '') + formatted + (props.field.appendText || '')
  )
})
</script>
