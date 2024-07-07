<template>
  <IModal
    id="newCustomFieldModal"
    size="sm"
    :ok-text="$t('core::app.save')"
    :ok-disabled="form.busy"
    :title="$t('core::fields.custom.create')"
    static-backdrop
    form
    @hidden="resetForm"
    @submit="performCreate"
    @show="initiateCreate"
  >
    <CustomFieldsForm
      v-model:field-type="form.field_type"
      v-model:label="form.label"
      v-model:field-id="form.field_id"
      v-model:is-unique="form.is_unique"
      v-model:options="form.options"
      :resource-name="form.resource_name"
      :form="form"
    />
  </IModal>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'

import CustomFieldsForm from './CustomFieldsForm.vue'

const props = defineProps({
  resourceName: { type: String, required: true },
})

const emit = defineEmits(['created'])

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

function initiateCreate() {
  form.fill('resource_name', props.resourceName)
}

async function performCreate() {
  let field = await form.post('/custom-fields')

  emit('created', field)

  Innoclapps.dialog().hide('newCustomFieldModal')

  Innoclapps.success(t('core::fields.custom.created'))
}

function resetForm() {
  form.reset()
  form.errors.clear()
}
</script>
