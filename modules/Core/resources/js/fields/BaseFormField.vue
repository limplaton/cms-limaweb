<template>
  <slot :is-readonly="isReadonly" :field-id="fieldId" />
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { watchArray } from '@vueuse/core'
import get from 'lodash/get'
import isObject from 'lodash/isObject'

import { emitGlobal } from '../composables/useGlobalEventListener'

const props = defineProps(['field', 'resourceName', 'value', 'isFloating'])

const isReadonly = computed(
  () => props.field.readonly || get(props.field, 'attributes.readonly')
)

const fieldId = computed(
  () =>
    (props.resourceName ? props.resourceName + '-' : '') +
    (props.field.id || props.field.attribute) +
    (props.isFloating ? '-floating' : '')
)

function handleValueChanged(newVal) {
  if (props.field.emitChangeEvent) {
    emitGlobal(props.field.emitChangeEvent, newVal)
  }
}

onMounted(() => {
  if (Array.isArray(props.value)) {
    watchArray(
      () => props.value,
      newList => {
        handleValueChanged(newList)
      },
      { deep: true }
    )

    return
  }

  watch(
    () => props.value,
    (newVal, oldVal) => {
      if (
        isObject(newVal) &&
        isObject(oldVal) &&
        JSON.stringify(newVal) === JSON.stringify(oldVal)
      ) {
        return
      }

      handleValueChanged(newVal)
    },
    { deep: true }
  )
})
</script>
