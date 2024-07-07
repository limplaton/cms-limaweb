<template>
  <ICard as="form" :overlay="!componentReady" @submit.prevent="update">
    <ICardBody>
      <FormFields
        :fields="fields"
        :form="form"
        :resource-id="activityId"
        :resource-name="resourceName"
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />
    </ICardBody>

    <ICardFooter>
      <div
        class="flex w-full flex-col sm:w-auto sm:flex-row sm:items-center sm:justify-end"
      >
        <IFormSwitchField
          class="mb-4 sm:mb-0 sm:mr-4 sm:border-r sm:border-neutral-200 sm:pr-4 sm:dark:border-neutral-500/30"
        >
          <IFormSwitchLabel
            :text="$t('activities::activity.mark_as_completed')"
          />

          <IFormSwitch v-model="form.is_completed" />
        </IFormSwitchField>

        <IButton
          class="mb-2 ml-0 sm:mb-0 sm:mr-2"
          variant="secondary"
          :text="$t('core::app.cancel')"
          @click="$emit('cancelled', $event)"
        />

        <IButton
          type="submit"
          variant="primary"
          :disabled="form.busy"
          :text="$t('core::app.save')"
          @click="update"
        />
      </div>
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { computed, inject, onBeforeMount } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  activityId: { type: Number, required: true },
  relatedResource: { required: true, type: Object },
  viaResource: { type: String, required: true },
  viaResourceId: { type: [String, Number], required: true },
})

const emit = defineEmits(['cancelled', 'updated'])

const synchronizeResource = inject('synchronizeResource')
const incrementResourceCount = inject('incrementResourceCount')
const decrementResourceCount = inject('decrementResourceCount')

const resourceName = Innoclapps.resourceName('activities')

const { t } = useI18n()

const { fields, hydrateFields, updateField, getUpdateFields } =
  useResourceFields()

const { form } = useForm()
const { updateResource, retrieveResource } = useResourceable(resourceName)

let isCompleted = false

const componentReady = computed(() => fields.value.length > 0)

const contactsForGuestsSelectField = computed(() =>
  props.viaResource === 'contacts'
    ? [props.relatedResource]
    : props.relatedResource.contacts || []
)

function update() {
  updateResource(
    form.withQueryString({
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
    }),
    props.activityId
  ).then(handleActivityUpdated)
}

function handleActivityUpdated(updatedActivity) {
  // For the mark as completed toggle
  if (updatedActivity.is_completed !== isCompleted) {
    if (updatedActivity.is_completed) {
      decrementResourceCount('incomplete_activities_for_user_count')
    } else {
      incrementResourceCount('incomplete_activities_for_user_count')
    }

    isCompleted = updatedActivity.is_completed
  }

  synchronizeResource({ activities: updatedActivity })

  emit('updated', updatedActivity)

  Innoclapps.success(t('core::resource.updated'))
}

onBeforeMount(async () => {
  const activity = await retrieveResource(props.activityId)

  isCompleted = activity.is_completed

  fields.value = await getUpdateFields(resourceName, props.activityId, {
    viaResource: props.viaResource,
    viaResourceId: props.viaResourceId,
  })

  updateField('guests', { contacts: contactsForGuestsSelectField })
  hydrateFields(activity)

  // For checkbox mark as completed
  form.set('is_completed', activity.is_completed)
})
</script>
