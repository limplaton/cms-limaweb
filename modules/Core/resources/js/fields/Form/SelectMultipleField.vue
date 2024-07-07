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
        multiple
        v-bind="field.attributes"
        @update:model-value="
          updateModelValue($event), handleSelectInputChange($event)
        "
        @search="onSearch"
        @open="onDropdownOpen"
      >
        <template #no-options>{{ noOptionsText }}</template>

        <template
          v-if="field.displayOptionViewActionFromPath"
          #after-selected-option-inner="{ option }"
        >
          <ILink
            v-if="option.path"
            target="_blank"
            :to="option.path"
            plain
            @click.stop=""
          >
            <Icon icon="Eye" class="size-4" />
          </ILink>
        </template>

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
import each from 'lodash/each'
import isNil from 'lodash/isNil'

import FormFieldGroup from '../FormFieldGroup.vue'

const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { type: Array, default: () => [] },
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['update:modelValue', 'setInitialValue'])

function updateModelValue(value) {
  let values = []

  each(value, data => {
    values.push(data[props.field.valueKey])
  })

  emit('update:modelValue', values)
}

function setInitialValue() {
  emit(
    'setInitialValue',
    !isNil(props.field.value)
      ? (function () {
          if (props.field.value.length === 0) {
            return []
          }

          if (typeof props.field.value[0] === 'object') {
            return props.field.value.map(option => option[props.field.valueKey])
          } else {
            return props.field.value
          }
        })()
      : []
  )
}

setInitialValue()
</script>
