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
      as-paragraph-label
    >
      <div
        :class="{
          'flex items-center space-x-2': field.inline,
          'space-y-1': !field.inline,
        }"
      >
        <IFormRadioField
          v-for="option in field.options"
          :key="option[field.valueKey]"
        >
          <IFormRadio
            v-bind="field.attributes"
            v-model="model"
            :name="field.attribute"
            :value="option[field.valueKey]"
            :disabled="isReadonly"
          />

          <IFormRadioLabel>
            <IBadge
              v-if="option.swatch_color"
              :text="option[field.labelKey]"
              :color="option.swatch_color"
            />

            <span v-else v-text="option[field.labelKey]" />
          </IFormRadioLabel>
        </IFormRadioField>
      </div>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import isNil from 'lodash/isNil'

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
  emit(
    'setInitialValue',
    (function () {
      if (!isNil(props.field.value)) {
        if (typeof props.field.value === 'object') {
          return props.field.value[props.field.valueKey]
        } else {
          return props.field.value
        }
      }

      return ''
    })()
  )
}

setInitialValue()
</script>
