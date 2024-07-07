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
          :fields="callFields"
          :form="form"
          :resource-name="callsResourceName"
          @update-field-value="form.fill($event.attribute, $event.value)"
          @set-initial-value="form.set($event.attribute, $event.value)"
        />
      </IOverlay>

      <CreateFollowUpTask v-model="form.task_date" class="mt-2" />
    </div>
  </IModal>

  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :is-floating="isFloating"
    @updated="$emit('updated', $event)"
  >
    <template v-if="hasValue">
      <div v-for="(phone, index) in value" :key="index">
        <IDropdown>
          <IDropdownButton
            v-i-tooltip="$t('contacts::fields.phone.types.' + phone.type)"
            :text="phone.number"
            link
            no-caret
          />

          <IDropdownMenu>
            <span
              v-if="!isFloating"
              v-i-tooltip="isCallingDisabled ? callDropdownTooltip : null"
            >
              <IDropdownItem
                :disabled="isCallingDisabled"
                :text="$t('calls::call.make')"
                @click="initiateNewCall(phone.number)"
              />
            </span>

            <IButtonCopy
              as="IDropdownItem"
              :text="phone.number"
              :success-message="$t('contacts::fields.phone.copied')"
            >
              {{ $t('core::app.copy') }}
            </IButtonCopy>

            <IDropdownItem
              :href="'tel:' + phone.number"
              :text="$t('core::app.open_in_app')"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>
    </template>

    <span v-if="!hasValue">&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'
import { useGate } from '@/Core/composables/useGate'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import CreateFollowUpTask from '@/Activities/components/CreateFollowUpTask.vue'
import { useActivities } from '@/Activities/composables/useActivities'

import { useVoip } from '../../composables/useVoip'

const props = defineProps([
  'resource',
  'resourceName',
  'resourceId',
  'field',
  'isFloating',
])

defineEmits(['updated'])

const callsResourceName = Innoclapps.resourceName('calls')
const synchronizeResource = inject('synchronizeResource', null)
const incrementResourceCount = inject('incrementResourceCount', null)

const logCallModalIsVisible = ref(false)

const { t } = useI18n()
const { gate } = useGate()
const { voip, hasVoIPClient } = useVoip()
const { fields: callFields, hasFields, getCreateFields } = useResourceFields()

const { form } = useForm({
  task_date: null,
})

const { createResource } = useResourceable(callsResourceName)
const { createFollowUpActivity } = useActivities()

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

async function handleCallCreated(call) {
  if (form.task_date) {
    let activity = await createFollowUpActivity(
      form.task_date,
      props.resourceName,
      props.resourceId,
      props.resource.display_name,
      {
        note: t('calls::call.follow_up_task_body', {
          content: call.body,
        }),
      }
    )

    if (activity) {
      if (synchronizeResource) {
        synchronizeResource({ activities: [activity] })
      }

      if (incrementResourceCount) {
        incrementResourceCount('incomplete_activities_for_user_count')
      }
    }
  }

  if (synchronizeResource) {
    synchronizeResource({ calls: [call] })
  }

  if (incrementResourceCount) {
    incrementResourceCount('calls_count')
  }

  Innoclapps.success(t('calls::call.created'))
  form.reset()
  logCallModalIsVisible.value = false
}

async function initiateNewCall(phoneNumber) {
  form.set('task_date', null)

  let call = await voip.makeCall(phoneNumber)

  call.on('Disconnect', () => {
    logCallModalIsVisible.value = true
  })

  callFields.value = await getCreateFields(callsResourceName, {
    viaResource: props.resourceName,
    viaResourceId: props.resourceId,
  })
}

function logCall() {
  createResource(
    form.set(props.resourceName, [props.resourceId]).withQueryString({
      via_resource: props.resourceName,
      via_resource_id: props.resourceId,
    })
  ).then(handleCallCreated)
}
</script>
