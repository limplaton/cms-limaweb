<template>
  <ICardHeader>
    <ICardHeading class="flex items-center gap-x-2">
      <Icon
        v-if="isConfigured && componentReady"
        icon="CheckCircleSolid"
        class="size-5 text-success-600"
      />

      Pusher
    </ICardHeading>

    <ICardActions>
      <ILink
        href="https://dashboard.pusher.com"
        text="Pusher.com"
        tabindex="-1"
      />
    </ICardActions>
  </ICardHeader>

  <ICard
    as="form"
    :overlay="!componentReady"
    @submit.prevent="submitPusherIntegrationSettings"
  >
    <ICardBody>
      <IAlert class="mb-6" :show="!isConfigured && componentReady">
        <IAlertBody>
          Receive notifications in real time without the need to manually
          refresh the page, after synchronization, automatically updates the
          calendar, total unread emails and new emails.
        </IAlertBody>
      </IAlert>

      <div class="sm:flex sm:space-x-4">
        <IFormGroup class="w-full" label="App ID" label-for="pusher_app_id">
          <IFormInput
            id="pusher_app_id"
            v-model="form.pusher_app_id"
          ></IFormInput>
        </IFormGroup>

        <IFormGroup class="w-full" label="App Key" label-for="pusher_app_key">
          <IFormInput
            id="pusher_app_key"
            v-model="form.pusher_app_key"
          ></IFormInput>
        </IFormGroup>
      </div>

      <div class="sm:flex sm:space-x-4">
        <IFormGroup
          class="w-full"
          label="App Secret"
          label-for="pusher_app_secret"
        >
          <IFormInput
            id="pusher_app_secret"
            v-model="form.pusher_app_secret"
            type="password"
          ></IFormInput>
        </IFormGroup>

        <IFormGroup class="w-full">
          <template #label>
            <div class="flex">
              <div class="mb-1 grow">
                <IFormLabel for="pusher_app_cluster">App Cluster</IFormLabel>
              </div>

              <ILink
                href="https://pusher.com/docs/clusters"
                text="https://pusher.com/docs/clusters"
              />
            </div>
          </template>

          <IFormInput
            id="pusher_app_cluster"
            v-model="form.pusher_app_cluster"
          ></IFormInput>
        </IFormGroup>
      </div>
    </ICardBody>

    <ICardFooter class="text-right">
      <IButton
        type="submit"
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
      />
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { computed } from 'vue'

import { useSettings } from '../../../composables/useSettings'

const {
  form,
  submit,
  isReady: componentReady,
  originalSettings,
} = useSettings()

const isConfigured = computed(
  () =>
    originalSettings.value.pusher_app_id &&
    originalSettings.value.pusher_app_key &&
    originalSettings.value.pusher_app_secret
)

function submitPusherIntegrationSettings() {
  submit(() => window.location.reload())
}
</script>
