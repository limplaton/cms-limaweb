<template>
  <form @submit.prevent="saveGeneralSettings">
    <div class="space-y-8">
      <div>
        <ICardHeader>
          <div>
            <ICardHeading :text="$t('core::settings.general')" />

            <IText
              class="block"
              :text="$t('core::settings.general_settings')"
            />
          </div>
        </ICardHeader>

        <ICard :overlay="!componentReady">
          <ICardBody>
            <p
              v-t="'core::app.logo.dark'"
              class="mb-3 text-base font-medium text-neutral-700 dark:text-neutral-200 sm:text-sm"
            />

            <CropsAndUploadsImage
              name="logo_dark"
              :upload-url="`${$scriptConfig('apiURL')}/logo/dark`"
              :image="currentDarkLogo"
              :show-delete="Boolean(form.logo_dark)"
              :cropper-options="{ aspectRatio: null }"
              :choose-text="
                !currentDarkLogo
                  ? $t('core::settings.choose_logo')
                  : $t('core::app.change')
              "
              @cleared="deleteLogo('dark')"
              @success="refreshPage"
            >
              <template #image="{ src }">
                <img class="h-8 w-auto" :src="src" />
              </template>
            </CropsAndUploadsImage>

            <hr
              class="-mx-7 my-4 border-t border-neutral-200 dark:border-neutral-500/30"
            />

            <p
              v-t="'core::app.logo.light'"
              class="mb-3 text-base font-medium text-neutral-700 dark:text-neutral-200 sm:text-sm"
            />

            <CropsAndUploadsImage
              name="logo_light"
              :show-delete="Boolean(form.logo_light)"
              :upload-url="`${$scriptConfig('apiURL')}/logo/light`"
              :image="currentLightLogo"
              :cropper-options="{ aspectRatio: null }"
              :choose-text="
                !currentLightLogo
                  ? $t('core::settings.choose_logo')
                  : $t('core::app.change')
              "
              @cleared="deleteLogo('light')"
              @success="refreshPage"
            >
              <template #image="{ src }">
                <img class="h-8 w-auto" :src="src" />
              </template>
            </CropsAndUploadsImage>

            <hr
              class="-mx-7 my-4 border-t border-neutral-200 dark:border-neutral-500/30"
            />

            <IFormGroup
              label-for="currency"
              class="w-auto xl:w-1/3"
              :label="$t('core::app.currency')"
            >
              <ICustomSelect
                v-model="form.currency"
                input-id="currency"
                :clearable="false"
                :options="currencies"
              />

              <IFormError :error="form.getError('currency')" />
            </IFormGroup>

            <IFormGroup
              label-for="system_email_account_id"
              :label="$t('core::settings.system_email')"
            >
              <div class="w-auto xl:w-1/3">
                <ICustomSelect
                  input-id="system_email_account_id"
                  label="email"
                  :placeholder="
                    !systemEmailAccountIsVisibleToCurrentUser &&
                    systemEmailAccountIsConfiguredFromOtherUser
                      ? $t('core::settings.system_email_configured')
                      : ''
                  "
                  :model-value="systemEmailAccount"
                  :disabled="
                    !systemEmailAccountIsVisibleToCurrentUser &&
                    systemEmailAccountIsConfiguredFromOtherUser
                  "
                  :options="emailAccounts"
                  @option-selected="form.system_email_account_id = $event.id"
                  @cleared="form.system_email_account_id = null"
                />
              </div>

              <IFormText
                class="mt-2 max-w-3xl"
                :text="$t('core::settings.system_email_info')"
              />

              <IFormError :error="form.getError('system_email_account_id')" />
            </IFormGroup>

            <IFormGroup
              label-for="allowed_extensions"
              :label="$t('core::app.allowed_extensions')"
              :description="$t('core::app.allowed_extensions_info')"
            >
              <IFormTextarea
                id="allowed_extensions"
                v-model="form.allowed_extensions"
                rows="2"
              />

              <IFormError :error="form.getError('allowed_extensions')" />
            </IFormGroup>

            <hr
              class="-mx-7 my-4 border-t border-neutral-200 dark:border-neutral-500/30"
            />

            <IFormSwitchField>
              <IFormSwitchLabel
                :text="$t('core::settings.phones.require_calling_prefix')"
              />

              <IFormSwitchDescription
                :text="$t('core::settings.phones.require_calling_prefix_info')"
              />

              <IFormSwitch
                v-model="form.require_calling_prefix_on_phones"
                @change="submit"
              />
            </IFormSwitchField>

            <hr
              class="-mx-7 my-4 border-t border-neutral-200 dark:border-neutral-500/30"
            />

            <div class="my-4 block">
              <IAlert class="mb-5">
                <IAlertBody>
                  {{ $t('core::settings.update_user_account_info') }}
                </IAlertBody>
              </IAlert>

              <LocalizationInputs
                class="w-auto xl:w-1/3"
                :exclude="['timezone', 'locale']"
                :form="form"
                @update:time-format="form.time_format = $event"
                @update:date-format="form.date_format = $event"
              />
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
      </div>
      <!-- Company information -->
      <div>
        <ICardHeader>
          <ICardHeading :text="$t('core::settings.company_information')" />
        </ICardHeader>

        <ICard :overlay="!componentReady">
          <ICardBody>
            <IFormGroup
              class="w-auto xl:w-1/3"
              label-for="company_name"
              :label="$t('core::app.company.name')"
            >
              <IFormInput id="company_name" v-model="form.company_name" />
            </IFormGroup>

            <IFormGroup
              class="w-auto xl:w-1/3"
              label-for="company_country_id"
              :label="$t('core::app.company.country')"
            >
              <ICustomSelect
                v-model="country"
                label="name"
                input-id="company_country_id"
                :options="countries"
                @option-selected="form.company_country_id = $event.id"
                @cleared="form.company_country_id = null"
              />
            </IFormGroup>
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
      </div>

      <div>
        <ICardHeader>
          <ICardHeading :text="$t('core::app.privacy_policy')" />
        </ICardHeader>

        <ICard :overlay="!componentReady">
          <ICardBody>
            <Editor v-model="form.privacy_policy" :with-image="false" />

            <IFormText
              class="mt-2"
              :text="
                $t('core::settings.privacy_policy_info', {
                  url: $scriptConfig('privacyPolicyUrl'),
                })
              "
            />
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
      </div>
    </div>
  </form>
</template>

<script setup>
import { computed, shallowRef } from 'vue'
import find from 'lodash/find'
import map from 'lodash/map'

import LocalizationInputs from '@/Core/components/LocalizationInputs.vue'
import { useApp } from '@/Core/composables/useApp'

import { useEmailAccounts } from '@/MailClient/composables/useEmailAccounts'

import { useSettings } from '../../composables/useSettings'

const { setting, resetStoreState } = useApp()

const {
  form,
  submit,
  isReady: componentReady,
  originalSettings,
} = useSettings()

const { emailAccounts, fetchEmailAccounts } = useEmailAccounts()

const currencies = shallowRef([])
const countries = shallowRef([])
const country = shallowRef(null)

const currentLightLogo = computed(() => setting('logo_light'))

const currentDarkLogo = computed(() => setting('logo_dark'))

const systemEmailAccount = computed(() =>
  find(emailAccounts.value, ['id', parseInt(form.system_email_account_id)])
)

const originalSystemEmailAccount = computed(() =>
  find(emailAccounts.value, [
    'id',
    parseInt(originalSettings.value.system_email_account_id),
  ])
)

const systemEmailAccountIsVisibleToCurrentUser = computed(
  () =>
    originalSettings.value.system_email_account_id &&
    originalSystemEmailAccount.value
)

const systemEmailAccountIsConfiguredFromOtherUser = computed(() => {
  // If the account cannot be found in the accounts list, this means the account is not visible
  // to the current logged-in user
  return (
    originalSettings.value.system_email_account_id &&
    !originalSystemEmailAccount.value
  )
})

function saveGeneralSettings() {
  submit(() => {
    if (
      form.require_calling_prefix_on_phones !==
      originalSettings.value.require_calling_prefix_on_phones
    ) {
      resetStoreState()
    }

    if (form.currency !== originalSettings.value.currency) {
      // Reload the page as the original currency is stored is in Innoclapps.config object
      refreshPage()
    }
  })
}

function refreshPage() {
  window.location.reload()
}

function deleteLogo(type) {
  const optionName = 'logo_' + type

  if (form[optionName]) {
    Innoclapps.request().delete(`/logo/${type}`).then(refreshPage)
  }
}

function fetchAndSetCurrencies() {
  Innoclapps.request('currencies').then(({ data }) => {
    currencies.value = map(data, (val, code) => code)
  })
}

function fetchAndSetCountries() {
  Innoclapps.request('countries').then(({ data }) => {
    countries.value = data

    if (form.company_country_id) {
      country.value = find(countries.value, [
        'id',
        parseInt(form.company_country_id),
      ])
    }
  })
}

fetchEmailAccounts()
fetchAndSetCurrencies()
fetchAndSetCountries()
</script>
