<template>
  <BaseFormField
    v-slot="{ isReadonly, fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="model"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :label="field.label"
      :field-id="fieldId"
      :validation-errors="validationErrors"
    >
      <TimezoneInput
        v-model="model"
        :field-id="fieldId"
        :clearable="true"
        :name="field.attribute"
        :disabled="isReadonly"
        v-bind="field.attributes"
      />
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import isNil from 'lodash/isNil'

import TimezoneInput from '@/Core/components/TimezoneInput.vue'

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

function setInitialValue() {
  emit('setInitialValue', !isNil(props.field.value) ? props.field.value : '')
}

setInitialValue()

// Configure the TimezoneInput to utilize predefined timezone options specified in field settings,
// thereby eliminating the need to fetch timezones from the storage system.
// Using this method proves beneficial, particularly in the context of web forms.
Innoclapps.timezones(props.field.timezones)
</script>
