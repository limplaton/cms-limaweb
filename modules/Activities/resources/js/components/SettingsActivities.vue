<template>
  <div class="space-y-8">
    <div>
      <ICardHeader>
        <ICardHeading :text="$t('activities::activity.activities')" />
      </ICardHeader>

      <ICard :overlay="!componentReady">
        <ICardBody
          class="divide-y divide-neutral-200 dark:divide-neutral-500/30"
        >
          <IFormSwitchField>
            <IFormSwitchLabel
              :text="$t('activities::activity.settings.send_contact_email')"
            />

            <IFormSwitchDescription
              :text="
                $t('activities::activity.settings.send_contact_email_info')
              "
            />

            <IFormSwitch
              v-model="form.send_contact_attends_to_activity_mail"
              @change="submit"
            />
          </IFormSwitchField>

          <IFormGroup
            class="mt-3 pt-3"
            label-for="default_activity_type"
            :label="$t('activities::activity.type.default_type')"
          >
            <ICustomSelect
              v-model="defaultType"
              input-id="default_activity_type"
              class="xl:w-1/3"
              label="name"
              :clearable="false"
              :options="types"
              @option-selected="handleActivityTypeInputEvent"
            >
            </ICustomSelect>
          </IFormGroup>
        </ICardBody>
      </ICard>
    </div>

    <div>
      <ActivitiesTypesIndex />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { watchOnce } from '@vueuse/core'

import { useApp } from '@/Core/composables/useApp'
import { useSettings } from '@/Core/composables/useSettings'

import { useActivityTypes } from '../composables/useActivityTypes'
import ActivitiesTypesIndex from '../views/ActivitiesTypesIndex.vue'

const { resetStoreState } = useApp()
const { form, submit, isReady: componentReady } = useSettings()

const defaultType = ref(null)

const { typesByName: types } = useActivityTypes()

function handleActivityTypeInputEvent(e) {
  form.default_activity_type = e.id
  submit(resetStoreState)
}

watchOnce(componentReady, () => {
  defaultType.value = types.value.find(
    type => type.id == form.default_activity_type
  )
})
</script>
