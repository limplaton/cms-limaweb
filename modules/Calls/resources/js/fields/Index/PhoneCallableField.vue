<template>
  <IModal
    v-model:visible="logCallModalIsVisible"
    size="md"
    :title="$t('calls::call.add')"
    :ok-text="$t('calls::call.add')"
    :ok-disabled="form.busy"
    @shown="logCallModalIsVisible = true"
    @hidden="logCallModalIsVisible = false"
    @ok="logCall"
  >
    <!-- re-render the fields as it's causing issue with the tinymce editor
                 on second time the editor has no proper height -->
    <div v-if="logCallModalIsVisible">
      <IOverlay :show="!hasFields">
        <FormFields
          :fields="fields"
          :form="form"
          :resource-name="callsResourceName"
          @update-field-value="form.fill($event.attribute, $event.value)"
          @set-initial-value="form.set($event.attribute, $event.value)"
        />
      </IOverlay>

      <CreateFollowUpTask v-model="form.task_date" class="mt-2" />
    </div>
  </IModal>

  <BasePhoneField v-bind="{ ...$props, ...$attrs }">
    <template #start="{ phone }">
      <span v-i-tooltip="isCallingDisabled ? callDropdownTooltip : null">
        <IDropdownItem
          :disabled="isCallingDisabled"
          :text="$t('calls::call.make')"
          @click="initiateNewCall(phone.number)"
        />
      </span>
    </template>
  </BasePhoneField>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'
import { useGate } from '@/Core/composables/useGate'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import CreateFollowUpTask from '@/Activities/components/CreateFollowUpTask.vue'
import { useActivities } from '@/Activities/composables/useActivities'
import BasePhoneField from '@/Contacts/fields/Index/PhoneField.vue'

import { useVoip } from '../../composables/useVoip'

defineOptions({ inheritAttrs: false })

const props = defineProps([
  'column',
  'row',
  'field',
  'resourceName',
  'resourceId',
])

const callsResourceName = Innoclapps.resourceName('calls')

const { t } = useI18n()
const { gate } = useGate()
const { voip, hasVoIPClient } = useVoip()
const { createFollowUpActivity } = useActivities()
const { fields, hasFields, getCreateFields } = useResourceFields()

const { form } = useForm({
  task_date: null,
})

const { createResource } = useResourceable(callsResourceName)

const logCallModalIsVisible = ref(false)

const isCallingDisabled = computed(
  () => !hasVoIPClient || !gate.userCan('use voip')
)

const callDropdownTooltip = computed(() => {
  if (!hasVoIPClient) {
    return t('core::app.integration_not_configured')
  } else if (gate.userCant('use voip')) {
    return t('calls::call.no_voip_permissions')
  }

  return ''
})

async function initiateNewCall(phoneNumber) {
  form.set('task_date', null)

  let call = await voip.makeCall(phoneNumber)

  call.on('Disconnect', () => {
    logCallModalIsVisible.value = true
  })

  fields.value = await getCreateFields(callsResourceName, {
    viaResource: props.resourceName,
    viaResourceId: props.row.id,
  })
}

async function handleCallCreated(call) {
  if (form.task_date) {
    createFollowUpActivity(
      form.task_date,
      props.resourceName,
      props.resourceId,
      props.row.display_name || props.row.name,
      {
        note: t('calls::call.follow_up_task_body', {
          content: call.body,
        }),
      }
    )
  }

  form.reset()
  logCallModalIsVisible.value = false
}

function logCall() {
  createResource(
    form.set(props.resourceName, [props.row.id]).withQueryString({
      via_resource: props.resourceName,
      via_resource_id: props.row.id,
    })
  ).then(handleCallCreated)
}
</script>
