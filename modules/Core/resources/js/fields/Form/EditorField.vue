<template>
  <BaseFormField
    v-slot="{ isReadonly }"
    :resource-name="resourceName"
    :field="field"
    :value="model"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :label="field.label"
      :field-id="localFieldId"
      :validation-errors="validationErrors"
    >
      <Editor
        :id="localFieldId"
        v-model="model"
        :disabled="isReadonly"
        v-bind="field.attributes"
      />
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed } from 'vue'
import isNil from 'lodash/isNil'

import { randomString } from '@/Core/utils'

import FormFieldGroup from '../FormFieldGroup.vue'

const props = defineProps({
  field: { type: Object, required: true },
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['setInitialValue'])

const model = defineModel()

const localFieldId = computed(() => {
  return (
    (props.resourceName ? props.resourceName + '-' : '') +
    (props.field.id || props.field.attribute) +
    (props.isFloating ? '-floating' : '') +
    '-' +
    randomString()
  )
})

function setInitialValue() {
  emit('setInitialValue', !isNil(props.field.value) ? props.field.value : '')
}

setInitialValue()
</script>
