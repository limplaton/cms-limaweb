<template>
  <form @submit.prevent="">
    <IFormGroup
      label-for="name"
      :label="$t('mailclient::mail.templates.name')"
      required
    >
      <IFormInput
        :model-value="name"
        @update:model-value="$emit('update:name', $event)"
      />

      <IFormError :error="form.getError('name')" />
    </IFormGroup>

    <IFormGroup
      label-for="subject"
      :label="$t('mailclient::mail.templates.subject')"
      required
    >
      <IFormInput
        :model-value="subject"
        :class="{
          'border-dashed !border-neutral-400': subjectDragover,
        }"
        @update:model-value="$emit('update:subject', $event)"
        @dragover="subjectDragover = true"
        @dragleave="subjectDragover = false"
        @drop="subjectDragover = false"
      />

      <IFormError :error="form.getError('subject')" />
    </IFormGroup>

    <IFormGroup
      label-for="body"
      :label="$t('mailclient::mail.templates.body')"
      required
    >
      <MailEditor
        :model-value="body"
        :placeholders="placeholders"
        :placeholders-disabled="true"
        @update:model-value="$emit('update:body', $event)"
      />

      <IFormError :error="form.getError('body')" />
    </IFormGroup>

    <IFormGroup>
      <IFormCheckboxField>
        <IFormCheckbox
          :checked="isShared"
          @update:checked="$emit('update:isShared', $event)"
        />

        <IFormCheckboxLabel
          :text="$t('mailclient::mail.templates.is_shared')"
        />
      </IFormCheckboxField>

      <IFormError :error="form.getError('is_shared')" />
    </IFormGroup>

    <slot name="bottom" />
  </form>
</template>

<script setup>
import { ref } from 'vue'

import MailEditor from '../../components/MailEditor.vue'
import { useMessagePlaceholders } from '../../composables/useMessagePlaceholders'

defineProps({
  isShared: { required: true, type: Boolean },
  body: { required: true, type: String },
  subject: { required: true, type: String },
  name: { required: true, type: String },
  form: { required: true, type: Object },
})

defineEmits(['update:isShared', 'update:body', 'update:subject', 'update:name'])

const subjectDragover = ref(false)

const { placeholders } = useMessagePlaceholders()
</script>
