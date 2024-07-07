<template>
  <div class="sm:flex sm:items-start">
    <div
      v-if="!hasFields"
      class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-danger-100 sm:mx-0 sm:size-10"
    >
      <Icon icon="ExclamationTriangle" class="size-6 text-danger-600" />
    </div>

    <div
      :class="[
        'mt-3 w-full sm:mt-0 sm:text-left',
        !hasFields ? 'text-center sm:ml-4' : '',
      ]"
    >
      <IDialogTitle class="mt-2" :title="dialog.title" />

      <IText v-if="showMessage" class="mt-1" :text="dialog.message" />

      <FormFields
        v-if="hasFields"
        class="mt-4"
        :fields="dialog.fields"
        :form="form"
        is-floating
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />
    </div>
  </div>

  <div class="mt-6 sm:flex sm:flex-row-reverse">
    <IButton
      class="w-full sm:ml-2 sm:w-auto"
      :variant="dialog.action.destroyable ? 'danger' : 'primary'"
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
</template>

<script setup>
import { computed } from 'vue'

import { useForm } from '@/Core/composables/useForm'

import IDialogTitle from '../UI/Dialog/IDialogTitle.vue'

const props = defineProps({
  close: Function,
  cancel: Function,
  dialog: { type: Object, required: true },
})

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

const hasFields = computed(() => props.dialog.fields.length > 0)
const showMessage = computed(() => !hasFields.value && props.dialog.message)

async function runAction() {
  await form.fill('ids', props.dialog.ids).post(`${props.dialog.endpoint}`, {
    params: props.dialog.queryString,
    responseType: props.dialog.action.responseType,
  })

  props.close()
}
</script>
