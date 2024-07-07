<template>
  <BaseFormField
    v-slot="{ isReadonly, fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="modelValue"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :label="field.label"
      :field-id="fieldId"
      :validation-errors="validationErrors"
    >
      <DatePicker
        :id="fieldId"
        :model-value="modelValue"
        :required="field.isRequired"
        :name="field.attribute"
        :disabled="isReadonly"
        v-bind="field.attributes"
        @update:model-value="updateModelValue"
      />
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { nextTick } from 'vue'
import isNil from 'lodash/isNil'

import { useDates } from '@/Core/composables/useDates'

import FormFieldGroup from '../FormFieldGroup.vue'

const props = defineProps({
  field: { type: Object, required: true },
  modelValue: {},
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['update:modelValue', 'setInitialValue'])

const { DateTime } = useDates()

function updateModelValue(value) {
  emit('update:modelValue', value || '')
}

function setInitialValue() {
  emit(
    'setInitialValue',
    !isNil(props.field.value)
      ? DateTime.fromISO(props.field.value).toISODate()
      : ''
  )
}

nextTick(setInitialValue)
</script>
