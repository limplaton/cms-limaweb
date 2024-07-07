<template>
  <ICardHeader>
    <ICardHeading class="flex items-center gap-x-2">
      <Icon
        v-if="isConfigured && componentReady"
        :icon="maybeClientSecretIsExpired ? 'XCircleSolid' : 'CheckCircleSolid'"
        :class="[
          'size-5',
          {
            'text-success-600':
              !maybeClientSecretIsExpired && isConfigured && componentReady,
            'text-danger-500':
              maybeClientSecretIsExpired && isConfigured && componentReady,
          },
        ]"
      />

      Microsoft
    </ICardHeading>

    <ICardActions>
      <ILink
        href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade"
        text="App Registrations"
      />
    </ICardActions>
  </ICardHeader>

  <ICard
    as="form"
    :overlay="!componentReady"
    @submit.prevent="submitMicrosoftIntegrationSettings"
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
      <!-- <div
       class="mb-6 flex items-center justify-between rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-1 dark:border-neutral-500/30 dark:bg-neutral-800"
      >
         <div class="text-base/6 sm:text-sm/6">
          <span class="mr-2 font-medium text-neutral-700 dark:text-neutral-200">Logout Url:</span>
          <span class="select-all text-neutral-600 dark:text-white" v-text="logoutUrl"></span>
        </div>
        <IButtonCopy class="ml-3" :text="logoutUrl" :success-message="$t('core::app.copied')" v-i-tooltip="$t('core::app.copy')" />
      </div> -->
      <div class="sm:flex sm:space-x-4">
        <IFormGroup
          class="w-full"
          label="App (client) ID"
          label-for="msgraph_client_id"
        >
          <IFormInput
            id="msgraph_client_id"
            v-model="form.msgraph_client_id"
            autocomplete="off"
            name="msgraph_client_id"
          />
        </IFormGroup>

        <IFormGroup
          class="w-full"
          label="Client Secret"
          label-for="msgraph_client_secret"
        >
          <IFormInput
            id="msgraph_client_secret"
            v-model="form.msgraph_client_secret"
            autocomplete="off"
            type="password"
            name="msgraph_client_secret"
          />
        </IFormGroup>
      </div>

      <IAlert
        v-if="
          originalSettings.msgraph_client_secret &&
          originalSettings.msgraph_client_secret_configured_at &&
          !maybeClientSecretIsExpired
        "
        v-slot="{ variant }"
        class="mt-4"
      >
        <IAlertBody>
          The client secret was configured at
          {{
            localizedDate(originalSettings.msgraph_client_secret_configured_at)
          }}. If you followed the documentation and configured the client secret
          to expire in 24 months,
          <span class="font-bold">
            you must re-generate a new client secret at:
            {{ localizedDate(getClientSecretExpiresDateTime().toISO()) }}
          </span>
          in order the integration to continue working.
        </IAlertBody>

        <IAlertActions>
          <IButton
            :variant="variant"
            :href="
              'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationMenuBlade/Credentials/appId/' +
              form.msgraph_client_id +
              '/isMSAApp/true'
            "
            ghost
          >
            Re-Generate
          </IButton>
        </IAlertActions>
      </IAlert>

      <IAlert
        v-slot="{ variant }"
        class="mt-4"
        variant="danger"
        :show="maybeClientSecretIsExpired"
      >
        <IAlertBody>
          The client secret is probably expired, click the button below to
          re-generate new secret if it's needed, don't forget to update the
          secret here in the integration as well.
        </IAlertBody>

        <IAlertActions>
          <IButton
            :variant="variant"
            :href="
              'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationMenuBlade/Credentials/appId/' +
              form.msgraph_client_id +
              '/isMSAApp/true'
            "
            ghost
          >
            Re-Generate
          </IButton>
        </IAlertActions>
      </IAlert>
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
import { computed, nextTick } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'

import { useSettings } from '../../../composables/useSettings'

const { DateTime, UTCDateTimeInstance, localizedDate, isISODate } = useDates()
const { appUrl } = useApp()

const {
  form,
  submit,
  isReady: componentReady,
  originalSettings,
} = useSettings()

const redirectUri = appUrl + '/microsoft/callback'
// const logoutUrl = appUrl + '/microsoft/logout'

const isConfigured = computed(
  () =>
    originalSettings.value.msgraph_client_secret &&
    originalSettings.value.msgraph_client_id
)

const maybeClientSecretIsExpired = computed(() => {
  if (
    !originalSettings.value.msgraph_client_secret ||
    !originalSettings.value.msgraph_client_secret_configured_at
  ) {
    return false
  }

  return getClientSecretExpiresDateTime() < UTCDateTimeInstance
})

/**
 * We can only fetch the secret expires date using the servicePrincipal endpoint
 * however, this endpoint required work account and as we cannot force all users
 * to configure work account, we will assume that they follow the docs and add the
 * token to expire in 24 months, based on the configuration date, we will track the expiration of the token
 * @see https://docs.microsoft.com/en-us/graph/api/serviceprincipal-list?view=graph-rest-1.0&tabs=http#permissions
 */
function getClientSecretExpiresDateTime() {
  let configuredAt = originalSettings.value.msgraph_client_secret_configured_at

  configuredAt = isISODate(configuredAt)
    ? DateTime.fromISO(configuredAt)
    : DateTime.fromFormat(configuredAt, 'yyyy-MM-dd HH:mm:ss')

  return (
    configuredAt
      .toUTC()
      .plus({ months: 24 })
      // Subtract 1 day to avoid integration interruptions when the secret must be renewed at the same day
      .minus({ days: 1 })
  )
}

function submitMicrosoftIntegrationSettings() {
  if (
    form.msgraph_client_secret &&
    originalSettings.value.msgraph_client_secret != form.msgraph_client_secret
  ) {
    form.fill(
      'msgraph_client_secret_configured_at',
      UTCDateTimeInstance.toISODate()
    )
  } else if (!form.msgraph_client_secret) {
    form.fill('msgraph_client_secret_configured_at', null)
  }

  nextTick(() => submit(() => window.location.reload()))
}
</script>
