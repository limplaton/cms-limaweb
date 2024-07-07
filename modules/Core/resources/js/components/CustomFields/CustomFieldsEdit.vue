<template>
  <IModal
    id="editCustomFieldModal"
    size="sm"
    :ok-text="$t('core::app.save')"
    :ok-disabled="form.busy"
    :title="$t('core::fields.custom.update')"
    static-backdrop
    form
    @submit="performUpdate"
    @hidden="resetForm"
    @show="initiateUpdate"
  >
    <CustomFieldsForm
      v-model:field-type="form.field_type"
      v-model:label="form.label"
      v-model:field-id="form.field_id"
      v-model:is-unique="form.is_unique"
      v-model:options="form.options"
      :resource-name="form.resource_name"
      :form="form"
      in-edit-mode
    />
  </IModal>
</template>

<script setup>
import { toRaw } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'

import CustomFieldsForm from './CustomFieldsForm.vue'

const props = defineProps({
  customFieldId: { type: Number, required: true },
  label: { type: String, required: true },
  fieldType: { type: String, required: true },
  fieldId: { type: String, required: true },
  resourceName: { type: String, required: true },
  options: Array,
  isUnique: Boolean,
})

const emit = defineEmits(['updated'])

const { t } = useI18n()

const { form } = useForm(
  {
    label: '',
    field_type: 'Text',
    field_id: '',
    resource_name: '',
    options: [],
    is_unique: null,
  },
  { resetOnSuccess: true }
)

function initiateUpdate() {
  form.fill('id', props.customFieldId)
  form.fill('label', props.label)
  form.fill('field_type', props.fieldType)
  form.fill('field_id', props.fieldId)
  form.fill('is_unique', props.isUnique)
  form.fill('resource_name', props.resourceName)
  // Clone options deep, when removing an option and not saving
  // the custom field, will remove the option from the field.customField.options array
  // too, in this case, we need the option to the original field in case
  // user access edit again to be shown on the form
  form.fill('options', structuredClone(toRaw(props.options || [])))
}

async function performUpdate() {
  let field = await form.put(`/custom-fields/${form.id}`)

  emit('updated', field)

  Innoclapps.dialog().hide('editCustomFieldModal')

  Innoclapps.success(t('core::fields.custom.updated'))
}

function resetForm() {
  form.reset()
  form.errors.clear()
}
</script>
