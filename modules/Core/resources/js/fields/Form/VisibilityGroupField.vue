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
      <VisibilityGroupSelector
        v-model:type="value.type"
        v-model:dependsOn="value.depends_on"
        v-bind="field.attributes"
        :disabled="isReadonly"
      />
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { ref, watch } from 'vue'

import VisibilityGroupSelector from '@/Core/components/VisibilityGroupSelector.vue'

import FormFieldGroup from '../FormFieldGroup.vue'

const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { type: Object, default: () => ({}) },
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['update:modelValue', 'setInitialValue'])

const value = ref(getInitialValue())

function updateModelValue(newValue) {
  emit('update:modelValue', newValue)
}

function getInitialValue() {
  if (!props.field.value || !props.field.value.type) {
    return {
      type: 'all',
      depends_on: [],
    }
  }

  return props.field.value
}

function setInitialValue() {
  emit('setInitialValue', getInitialValue())
}

setInitialValue()

watch(value, updateModelValue, { deep: true })
</script>
