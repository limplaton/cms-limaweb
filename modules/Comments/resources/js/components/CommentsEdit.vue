<template>
  <div>
    <Editor v-model="form.body" with-mention minimal />

    <IFormError :error="form.getError('body')" />

    <div class="mt-2 space-x-2 text-right">
      <IButton
        variant="secondary"
        :text="$t('core::app.cancel')"
        @click="$emit('cancelled')"
      />

      <IButton
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
        @click="update"
      />
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'

const props = defineProps({
  commentId: { required: true, type: Number },
  body: { required: true, type: String },
  commentableType: { required: true, type: String },
  commentableId: { required: true, type: Number },
  viaResource: String,
  viaResourceId: [String, Number],
})

const emit = defineEmits(['updated', 'cancelled'])

const { t } = useI18n()

const { form } = useForm({
  body: props.body,
})

async function update() {
  if (props.viaResource) {
    form.withQueryString({
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
    })
  }

  let comment = await form.put(`/comments/${props.commentId}`)

  emit('updated', comment)

  Innoclapps.success(t('comments::comment.updated'))
}
</script>
