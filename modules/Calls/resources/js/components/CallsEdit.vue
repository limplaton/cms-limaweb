<template>
  <ICard as="form" :overlay="!hasFields" @submit.prevent="update">
    <ICardBody>
      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        :resource-id="callId"
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />
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
        :disabled="form.busy"
        :text="$t('core::app.save')"
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
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  callId: { required: true, type: Number },
  viaResource: { required: true, type: String },
  viaResourceId: { required: true, type: [String, Number] },
})

const emit = defineEmits(['updated', 'cancelled'])

const synchronizeResource = inject('synchronizeResource')

const resourceName = Innoclapps.resourceName('calls')

const { t } = useI18n()

const { fields, hasFields, hydrateFields, getUpdateFields } =
  useResourceFields()

const { form } = useForm()
const { updateResource, retrieveResource } = useResourceable(resourceName)

function update() {
  updateResource(
    form.withQueryString({
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
    }),
    props.callId
  ).then(handleCallUpdated)
}

function handleCallUpdated(updatedCall) {
  synchronizeResource({ calls: updatedCall })

  emit('updated', updatedCall)

  Innoclapps.success(t('calls::call.updated'))
}

async function prepareComponent() {
  const call = await retrieveResource(props.callId)

  fields.value = await getUpdateFields(resourceName, props.callId, {
    viaResource: props.viaResource,
    viaResourceId: props.viaResourceId,
  })

  hydrateFields(call)
}

prepareComponent()
</script>
