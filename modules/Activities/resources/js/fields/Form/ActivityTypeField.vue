<template>
  <BaseFormField
    v-slot="{ fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="model"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :field-id="fieldId"
      :validation-errors="validationErrors"
      as-paragraph-label
    >
      <IFormLabel
        class="mb-1 block sm:hidden"
        :for="fieldId"
        :label="$t('activities::activity.type.type')"
      />

      <IIconPicker
        v-model="model"
        class="flex-nowrap overflow-auto p-px sm:flex-wrap sm:overflow-visible"
        value-field="id"
        size="md"
        :icons="typesForIconPicker"
        v-bind="field.attributes"
      />
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import isNil from 'lodash/isNil'

import FormFieldGroup from '@/Core/fields/FormFieldGroup.vue'

import { useActivityTypes } from '../../composables/useActivityTypes'

const props = defineProps({
  field: { type: Object, required: true },
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['setInitialValue'])

const model = defineModel()

const { typesForIconPicker } = useActivityTypes()

function setInitialValue() {
  emit(
    'setInitialValue',
    !isNil(props.field.value)
      ? typeof props.field.value == 'object'
        ? props.field.value.id
        : props.field.value
      : null
  )
}

setInitialValue()
</script>
