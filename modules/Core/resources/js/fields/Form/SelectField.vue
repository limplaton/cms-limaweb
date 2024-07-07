<template>
  <BaseSelectField
    v-slot="{
      isReadonly,
      fieldId,
      options,
      createOption,
      lazyLoadingOptions,
      filterable,
      onSearch,
      onDropdownOpen,
      noOptionsText,
      headerText,
      selectValue,
      handleSelectInputChange,
    }"
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
      <ICustomSelect
        :model-value="selectValue"
        :input-id="fieldId"
        :disabled="isReadonly"
        :filterable="filterable"
        :options="options"
        :create-option-provider="createOption"
        :loading="lazyLoadingOptions"
        :name="field.attribute"
        :label="field.labelKey"
        v-bind="field.attributes"
        @update:model-value="
          updateModelValue($event), handleSelectInputChange($event)
        "
        @search="onSearch"
        @open="onDropdownOpen"
      >
        <template #no-options>{{ noOptionsText }}</template>

        <template #header>
          <IText v-show="headerText" class="px-3 py-2" :text="headerText" />

          <IText v-if="lazyLoadingOptions" class="-mt-1 px-3 py-2">
            <span class="block size-4 motion-safe:animate-bounce">...</span>
          </IText>
        </template>
      </ICustomSelect>
    </FormFieldGroup>
  </BaseSelectField>
</template>

<script setup>
import isNil from 'lodash/isNil'

import { isBlank } from '@/Core/utils'

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
  value =
    value && !isBlank(value[props.field.valueKey])
      ? value[props.field.valueKey]
      : null

  emit('update:modelValue', value)
}

function setInitialValue() {
  emit(
    'setInitialValue',
    !isNil(props.field.value)
      ? (function () {
          if (typeof props.field.value === 'object') {
            return props.field.value[props.field.valueKey]
          } else {
            return props.field.value
          }
        })()
      : null
  )
}

setInitialValue()
</script>
