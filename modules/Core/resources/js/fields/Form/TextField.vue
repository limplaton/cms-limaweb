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
      <IFormInput
        :id="fieldId"
        v-model="model"
        :disabled="isReadonly"
        :name="field.attribute"
        :type="field.inputType || 'text'"
        v-bind="field.attributes"
        :debounce="checksForDuplicates"
        @input="searchDuplicateResource"
      />

      <IAlert
        v-if="duplicateResource"
        v-slot="{ variant }"
        class="mt-2"
        dismissible
        @dismissed="duplicateResource = null"
      >
        <IAlertBody>
          <I18nT
            scope="global"
            :keypath="field.checkDuplicatesWith.lang_keypath"
          >
            <template #display_name>
              <span
                class="font-medium"
                v-text="duplicateResource.display_name"
              />
            </template>
          </I18nT>
        </IAlertBody>

        <IAlertActions>
          <IButton
            rel="noopener noreferrer"
            target="_blank"
            icon="ExternalLink"
            :href="duplicateResource.path"
            :variant="variant"
            :text="$t('core::app.view_record')"
            ghost
          />
        </IAlertActions>
      </IAlert>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed, nextTick, shallowRef } from 'vue'
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

const duplicateResource = shallowRef(null)

function setInitialValue() {
  emit('setInitialValue', !isNil(props.field.value) ? props.field.value : '')
}

async function searchDuplicateResource() {
  await nextTick()

  if (!checksForDuplicates.value || !model.value) {
    duplicateResource.value = null
  } else {
    duplicateResource.value = await makeDuplicateCheckRequest(model.value)
  }
}

const checksForDuplicates = computed(
  () =>
    !props.resourceId &&
    props.field.checkDuplicatesWith &&
    Object.keys(props.field.checkDuplicatesWith).length > 0
)

async function makeDuplicateCheckRequest(query) {
  const { data } = await Innoclapps.request(
    props.field.checkDuplicatesWith.url,
    {
      params: {
        q: query,
        ...props.field.checkDuplicatesWith.params,
      },
    }
  )

  return data.length > 0 ? data[0] : null
}

setInitialValue()
</script>
