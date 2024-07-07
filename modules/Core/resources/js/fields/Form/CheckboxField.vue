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
        <IFormCheckboxField
          v-for="option in field.options"
          :key="option[field.valueKey]"
        >
          <IFormCheckbox
            v-model:checked="model"
            :value="option[field.valueKey]"
            :name="field.attribute"
            :disabled="isReadonly"
            v-bind="field.attributes"
          />

          <IFormCheckboxLabel>
            <IBadge
              v-if="option.swatch_color"
              :text="option[field.labelKey]"
              :color="option.swatch_color"
            />

            <span v-else v-text="option[field.labelKey]" />
          </IFormCheckboxLabel>
        </IFormCheckboxField>
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

const model = defineModel({ type: Array, default: () => [] })

function setInitialValue() {
  emit(
    'setInitialValue',
    (!isNil(props.field.value) ? props.field.value : []).map(
      v => v[props.field.valueKey]
    )
  )
}

setInitialValue()
</script>
