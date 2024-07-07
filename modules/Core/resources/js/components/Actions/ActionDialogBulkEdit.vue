<template>
  <div class="sm:p-2">
    <div class="sm:flex sm:items-start">
      <div class="mt-3 w-full sm:mt-0 sm:text-left">
        <IDialogTitle :title="dialog.title" />

        <div class="mt-4 flex flex-col">
          <FieldsButtonCollapse
            v-if="totalCollapsable > 0"
            v-model:collapsed="fieldsCollapsed"
            class="mb-3 ml-auto"
            :total="totalCollapsable"
          />

          <FormFields
            :fields="fields"
            :collapsed="fieldsCollapsed"
            :form="form"
            is-floating
            @update-field-value="form.fill($event.attribute, $event.value)"
            @set-initial-value="form.set($event.attribute, $event.value)"
          >
            <template
              v-for="field in fields"
              #[field.beforeFieldSlotName]
              :key="field.attribute"
            >
              <div
                v-if="field.attribute"
                :class="[
                  field.displayNone || (field.collapsed && fieldsCollapsed)
                    ? 'hidden'
                    : '',
                  fieldsBeingUpdated[field.attribute] === replaceKey
                    ? '-mb-2'
                    : '',
                ]"
              >
                <div class="mb-3">
                  <IFormLabel
                    class="mb-1"
                    :for="field.attribute"
                    :required="
                      fieldsBeingUpdated[field.attribute] === replaceKey &&
                      field.isRequired
                    "
                    :label="field.label"
                  />

                  <ICustomSelect
                    v-model="fieldsBeingUpdated[field.attribute]"
                    :reduce="option => option.value"
                    :input-id="field.attribute"
                    :clearable="false"
                    :options="[
                      {
                        value: keepKey,
                        label: $t('core::fields.keep_existing_value'),
                      },
                      {
                        value: replaceKey,
                        label: $t('core::fields.replace_existing_value'),
                      },
                    ]"
                  />
                </div>
              </div>
            </template>
          </FormFields>
        </div>
      </div>
    </div>

    <div class="mt-6 sm:flex sm:flex-row-reverse">
      <IButton
        class="w-full sm:ml-2 sm:w-auto"
        variant="primary"
        :disabled="form.busy"
        :loading="form.busy"
        :text="$t('core::app.confirm')"
        @click="runAction"
      />

      <IButton
        class="mt-2 w-full sm:mt-0 sm:w-auto"
        :text="$t('core::app.cancel')"
        basic
        @click="cancel"
      />
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'

import { useForm } from '@/Core/composables/useForm'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import IDialogTitle from '../UI/Dialog/IDialogTitle.vue'

const props = defineProps({
  close: Function,
  cancel: Function,
  dialog: { type: Object, required: true },
})

const keepKey = 'keep'
const replaceKey = 'replace'
const fieldsCollapsed = ref(true)
const fieldsBeingUpdated = reactive({})

const { fields, updateField, totalCollapsable } = useResourceFields(
  props.dialog.fields
)

const { form } = useForm(
  { ids: [] },
  {
    onSuccess: response =>
      props.dialog.resolve({
        form: form,
        response: response,
      }),
  }
)

prepareFieldsForBulkEdit()

watch(
  fieldsBeingUpdated,
  newVal => {
    Object.entries(newVal).forEach(([attribute, value]) => {
      updateField(attribute, { hidden: value === keepKey })
    })
  },
  {
    deep: true,
  }
)

async function runAction() {
  try {
    Object.entries(fieldsBeingUpdated).forEach(([attribute, value]) => {
      if (value === keepKey) {
        delete form[attribute]
      }
    })

    await form.fill('ids', props.dialog.ids).post(`${props.dialog.endpoint}`, {
      params: props.dialog.queryString,
      responseType: props.dialog.action.responseType,
    })

    props.close()
  } catch (e) {
    // Show any fields that the user choosed to keep
    // but there are validation errors related to them.
    showHiddenFieldsWithErrors()

    throw e
  }
}

function prepareFieldsForBulkEdit() {
  fields.value.forEach(field => {
    // field.collapsed = false
    field.hidden = true
    field.hideLabel = true
    field.toggleable = false
    field.value = null // no default value for bulk edit fields
    field.beforeFieldSlotName = 'before-' + field.attribute + '-field'

    if (field.attribute) {
      fieldsBeingUpdated[field.attribute] = keepKey
    }
  })
}

function showHiddenFieldsWithErrors() {
  fields.value.forEach(field => {
    if (
      form.errors.has(field.attribute) &&
      fieldsBeingUpdated[field.attribute] === keepKey
    ) {
      fieldsBeingUpdated[field.attribute] = replaceKey
    }
  })
}
</script>
