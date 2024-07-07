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
        v-bind="field.attributes"
        mode="dateTime"
        :model-value="modelValue"
        :required="field.isRequired"
        :name="field.attribute"
        :disabled="isReadonly"
        @update:model-value="updateModelValue"
      />
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { nextTick } from 'vue'
import isNil from 'lodash/isNil'

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

function updateModelValue(value) {
  emit('update:modelValue', value || '')
}

function setInitialValue() {
  emit('setInitialValue', !isNil(props.field.value) ? props.field.value : '')
}

nextTick(setInitialValue)
</script>
