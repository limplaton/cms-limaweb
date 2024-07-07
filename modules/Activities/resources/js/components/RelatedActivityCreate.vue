<template>
  <ICard as="form" :overlay="!hasFields" @submit.prevent="create">
    <ICardBody>
      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        focus-first
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />

      <IAlert class="mt-1" variant="success" :show="form.recentlySuccessful">
        <IAlertBody :text="$t('activities::activity.created')" />
      </IAlert>
    </ICardBody>

    <ICardFooter>
      <div class="flex w-full flex-wrap items-center justify-between sm:w-auto">
        <div>
          <AssociationsPopover
            v-model="form.associations"
            :primary-record="relatedResource"
            :primary-resource-name="viaResource"
            :primary-record-disabled="true"
            :initial-associateables="relatedResource"
          />
        </div>

        <div
          class="mt-sm-0 mt-2 flex w-full flex-col sm:w-auto sm:flex-row sm:items-center sm:justify-end"
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
            class="mb-2 sm:mb-0 sm:mr-2"
            variant="secondary"
            :text="$t('core::app.cancel')"
            @click="$emit('cancel')"
          />

          <IButton
            variant="primary"
            type="submit"
            :disabled="form.busy"
            :text="$t('activities::activity.add')"
          />
        </div>
      </div>
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  viaResource: { type: String, required: true },
  viaResourceId: { type: [String, Number], required: true },
  relatedResource: { required: true, type: Object },
})

defineEmits(['cancel'])

const synchronizeResource = inject('synchronizeResource')
const incrementResourceCount = inject('incrementResourceCount')

const resourceName = Innoclapps.resourceName('activities')

const { t } = useI18n()

const { fields, hasFields, updateField, getCreateFields } = useResourceFields()

const { form } = useForm(
  {
    is_completed: false,
    associations: {
      [props.viaResource]: [props.viaResourceId],
    },
  },
  {
    resetOnSuccess: true,
  }
)

const { createResource } = useResourceable(resourceName)

const contactsForGuestsSelectField = computed(() =>
  props.viaResource === 'contacts'
    ? [props.relatedResource]
    : props.relatedResource.contacts || []
)

async function prepareComponent() {
  fields.value = await getCreateFields(resourceName, {
    viaResource: props.viaResource,
    viaResourceId: props.viaResourceId,
  })
  updateField('guests', { contacts: contactsForGuestsSelectField })
}

async function create() {
  let activity = await createResource(
    form.withQueryString({
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
    })
  )

  Innoclapps.success(t('activities::activity.created'))

  if (!activity.is_completed) {
    incrementResourceCount('incomplete_activities_for_user_count')
  }

  synchronizeResource({ activities: [activity] })
}

prepareComponent()
</script>
