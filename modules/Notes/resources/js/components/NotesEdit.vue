<template>
  <ICard as="form" @submit.prevent="update">
    <ICardBody>
      <Editor v-model="form.body" minimal with-mention />

      <IFormError :error="form.getError('body')" />
    </ICardBody>

    <ICardFooter class="space-x-2 text-right">
      <IButton
        variant="secondary"
        :text="$t('core::app.cancel')"
        @click="$emit('cancelled')"
      />

      <IButton
        type="submit"
        variant="primary"
        :text="$t('core::app.save')"
        :disabled="form.busy"
        @click="update"
      />
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'

const props = defineProps({
  noteId: { required: true, type: Number },
  body: { required: true, type: String },
  viaResource: { required: true, type: String },
  viaResourceId: { required: true, type: [String, Number] },
})

const emit = defineEmits(['updated', 'cancelled'])

const synchronizeResource = inject('synchronizeResource')

const { t } = useI18n()
const { updateResource } = useResourceable(Innoclapps.resourceName('notes'))
const { form } = useForm({ body: props.body })

async function update() {
  let updatedNote = await updateResource(
    form.withQueryString({
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
    }),
    props.noteId
  )

  synchronizeResource({ notes: updatedNote })

  emit('updated', updatedNote)

  Innoclapps.success(t('notes::note.updated'))
}
</script>
