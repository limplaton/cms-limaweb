<template>
  <ICardHeader>
    <ICardHeading class="flex items-center gap-x-2">
      <Icon
        v-if="isConfigured && componentReady"
        icon="CheckCircleSolid"
        class="size-5 text-success-600"
      />

      Google
    </ICardHeading>

    <ICardActions>
      <ILink href="https://console.developers.google.com/" text="Console" />
    </ICardActions>
  </ICardHeader>

  <ICard
    as="form"
    :overlay="!componentReady"
    @submit.prevent="submitGoogleIntegrationSettings"
  >
    <ICardBody>
      <div
        class="mb-6 flex items-center justify-between rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-1 dark:border-neutral-500/30 dark:bg-neutral-800"
      >
        <ITextBlock>
          <ITextDark class="mr-2 inline font-medium">Redirect Url:</ITextDark>

          <span class="select-all" v-text="redirectUri" />
        </ITextBlock>

        <IButtonCopy
          v-i-tooltip="$t('core::app.copy')"
          class="ml-3"
          :text="redirectUri"
          :success-message="$t('core::app.copied')"
        />
      </div>

      <div class="sm:flex sm:space-x-4">
        <IFormGroup
          label="Client ID"
          label-for="google_client_id"
          class="w-full"
        >
          <IFormInput
            id="google_client_id"
            v-model="form.google_client_id"
            autocomplete="off"
            name="google_client_id"
          />
        </IFormGroup>

        <IFormGroup
          label="Client Secret"
          label-for="google_client_secret"
          class="w-full"
        >
          <IFormInput
            id="google_client_secret"
            v-model="form.google_client_secret"
            type="password"
            autocomplete="off"
            name="google_client_secret"
          />
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

import { useApp } from '@/Core/composables/useApp'

import { useSettings } from '../../../composables/useSettings'

const { appUrl } = useApp()

const {
  form,
  submit,
  isReady: componentReady,
  originalSettings,
} = useSettings()

const redirectUri = appUrl + '/google/callback'

const isConfigured = computed(
  () =>
    originalSettings.value.google_client_secret &&
    originalSettings.value.google_client_id
)

function submitGoogleIntegrationSettings() {
  submit(() => window.location.reload())
}
</script>
