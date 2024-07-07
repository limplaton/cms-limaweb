<template>
  <PredefinedMailTemplatesForm
    v-model:is-shared="form.is_shared"
    v-model:body="form.body"
    v-model:subject="form.subject"
    v-model:name="form.name"
    :form="form"
  >
    <template #bottom>
      <div class="space-x-2 pt-6 text-right sm:pt-8">
        <IButton
          variant="secondary"
          :text="$t('core::app.cancel')"
          @click="$emit('cancelRequested')"
        />

        <IButton
          type="submit"
          variant="primary"
          :text="$t('core::app.create')"
          @click="create"
        />
      </div>
    </template>
  </PredefinedMailTemplatesForm>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'

import { useMailTemplates } from '../../composables/useMailTemplates'

import PredefinedMailTemplatesForm from './PredefinedMailTemplatesForm.vue'

const emit = defineEmits(['created', 'cancelRequested'])

const { t } = useI18n()
const { addTemplate } = useMailTemplates()

const { form } = useForm({
  name: '',
  body: '',
  subject: '',
  is_shared: true,
})

function create() {
  form.post('/mails/templates').then(template => {
    addTemplate(template)

    emit('created', template)

    Innoclapps.success(t('mailclient::mail.templates.created'))
  })
}
</script>
