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
      <div class="relative">
        <div
          v-if="field.https"
          class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
        >
          <span
            class="text-base text-neutral-500 dark:text-neutral-300 sm:text-sm"
            v-text="'https://'"
          />
        </div>

        <IFormInput
          :id="fieldId"
          v-model="model"
          type="url"
          :disabled="isReadonly"
          :name="field.attribute"
          v-bind="field.attributes"
          :class="[
            'pr-10 sm:pr-10',
            { 'pl-[calc(theme(spacing.20)-12px)] sm:pl-16': field.https },
          ]"
        />

        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
          <ILink
            variant="primary"
            :href="model || '#'"
            :basic="!model"
            :class="!model ? 'pointer-events-none' : ''"
          >
            <Icon icon="Link" class="size-5" />
          </ILink>
        </div>
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
  emit('setInitialValue', !isNil(props.field.value) ? props.field.value : '')
}

setInitialValue()
</script>
