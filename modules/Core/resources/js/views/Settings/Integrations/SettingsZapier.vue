<template>
  <ICardHeader>
    <ICardHeading>
      Zapier
      <IBadge variant="info" class="ml-2">Beta</IBadge>
    </ICardHeading>
  </ICardHeader>

  <ICard>
    <ICardBody>
      <div class="sm:p-4">
        <div class="text-center">
          <img
            src="https://cdn.zapier.com/zapier/images/logos/zapier-logo.png"
            class="mx-auto h-16 w-auto"
          />

          <IText class="mx-auto mt-5 max-w-lg">
            Zapier integration is at <b>"Invite Only"</b>
            and
            <b>"Testing"</b> stage, we are inviting you to test the integration
            before it's available for everyone.
          </IText>
        </div>

        <IText class="mx-auto mb-2 mt-5 text-center">
          Before this, <b>we need to verify your purchase key</b> and after that
          we will share the Zapier invite link with you can try it!
        </IText>

        <div class="m-auto max-w-2xl">
          <div class="flex space-x-2">
            <IFormInput
              id="purchase-key"
              v-model="purchaseKey"
              class="grow"
              placeholder="Enter your purchase key here"
            />

            <IButton
              class="shrink-0"
              variant="secondary"
              :loading="isLoading"
              :disabled="isLoading"
              @click="getLink"
            >
              Get Integration Link
            </IButton>
          </div>
        </div>

        <IText v-if="link" class="mt-6 flex items-center justify-center">
          <span class="select-all font-medium" v-text="link" />

          <IButtonCopy
            v-i-tooltip="$t('core::app.copy')"
            class="ml-3"
            :text="link"
            :success-message="$t('core::app.copied')"
          />
        </IText>
      </div>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

import { useApp } from '@/Core/composables/useApp'
import { useLoader } from '@/Core/composables/useLoader'

const { scriptConfig } = useApp()

const purchaseKey = ref(scriptConfig('purchase_key'))
const link = ref(null)
const { isLoading, setLoading } = useLoader()

/**
 * Get the Zapier Link
 *
 * Uses separate axios instance to prevent collision
 * with application error codes alerts and redirects
 */
function getLink() {
  setLoading(true)

  axios
    .get(`https://www.crm.com/zapier-link/${purchaseKey.value}`, {
      withCredentials: true,
    })
    .then(({ data }) => {
      link.value = data.link
    })
    .catch(error => {
      Innoclapps.error(error.response.data.error)
    })
    .finally(() => setLoading(false))
}
</script>
