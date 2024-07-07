<template>
  <div :id="'add-comment-' + commentableType + '-' + commentableId">
    <Editor
      ref="editorRef"
      v-model="form.body"
      :placeholder="$t('comments::comment.add_placeholder')"
      with-mention
      minimal
      @init="() => $refs.editorRef.focus()"
    />

    <IFormError :error="form.getError('body')" />

    <div class="mt-2 flex justify-end space-x-2">
      <IButton
        variant="secondary"
        :text="$t('core::app.cancel')"
        @click="$emit('cancelled')"
      />

      <IButton
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
        @click="create"
      />
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'

const props = defineProps({
  commentableType: { required: true, type: String },
  commentableId: { required: true, type: Number },
  viaResource: String,
  viaResourceId: [String, Number],
})

const emit = defineEmits(['created', 'cancelled'])

const { t } = useI18n()

const { form } = useForm({ body: '' }, { resetOnSuccess: true })

function handleCommentCreated(comment) {
  emit('created', comment)

  Innoclapps.success(t('comments::comment.created'))
}

function create() {
  if (props.viaResource) {
    form.withQueryString({
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
    })
  }

  form
    .post(`${props.commentableType}/${props.commentableId}/comments`)
    .then(handleCommentCreated)
}
</script>
