<template>
  <form @submit.prevent="accept">
    <ITabGroup>
      <ITabList class="-mt-5">
        <ITab :title="$t('users::profile.profile')" />

        <ITab :title="$t('auth.password')" />

        <ITab :title="$t('users::user.localization')" />
      </ITabList>

      <ITabPanels>
        <ITabPanel>
          <IFormGroup label-for="name" :label="$t('users::user.name')" required>
            <IFormInput id="name" ref="name" v-model="form.name" type="text" />

            <IFormError :error="form.getError('name')" />
          </IFormGroup>

          <IFormGroup label-for="email" :label="$t('users::user.email')">
            <IFormInput
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              disabled
            />

            <IFormError :error="form.getError('email')" />
          </IFormGroup>
        </ITabPanel>

        <ITabPanel>
          <IFormGroup
            label-for="password"
            :label="$t('auth.password')"
            required
          >
            <IFormInput
              id="password"
              v-model="form.password"
              name="password"
              type="password"
            />

            <IFormError :error="form.getError('password')" />
          </IFormGroup>

          <IFormGroup
            label-for="password_confirmation"
            :label="$t('auth.confirm_password')"
            required
          >
            <IFormInput
              id="password_confirmation"
              v-model="form.password_confirmation"
              name="password_confirmation"
              type="password"
            />

            <IFormError :error="form.getError('password_confirmation')" />
          </IFormGroup>
        </ITabPanel>

        <ITabPanel>
          <LocalizationInputs
            :form="form"
            @update:time-format="form.time_format = $event"
            @update:date-format="form.date_format = $event"
            @update:locale="form.locale = $event"
            @update:timezone="form.timezone = $event"
          />
        </ITabPanel>

        <IButton
          type="submit"
          class="mt-2"
          :disabled="requestInProgress"
          :loading="requestInProgress"
          :text="$t('users::user.accept_invitation')"
        />
      </ITabPanels>
    </ITabGroup>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import LocalizationInputs from '@/Core/components/LocalizationInputs.vue'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'

const props = defineProps({
  invitation: { type: Object, required: true },
  timezones: { type: Array, required: true },
  dateFormat: String,
  timeFormat: String,
})

const { t } = useI18n()
const { appUrl } = useApp()
const { guessTimezone } = useDates()

const { form } = useForm({
  name: null,
  password: null,
  timezone: guessTimezone(),
  locale: 'en',
  date_format: props.dateFormat,
  time_format: props.timeFormat,
  password_confirmation: null,
  email: props.invitation.email,
})

const requestInProgress = ref(false)

function accept() {
  requestInProgress.value = true

  form
    .post(props.invitation.link)
    .then(() => (window.location.href = appUrl))
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(
          t('core::app.form_validation_failed_with_sections'),
          3000
        )
      }
    })
    .finally(() => (requestInProgress.value = false))
}

// Set the timezones from the props, so the TimezoneInput won't make request
Innoclapps.timezones(props.timezones)
</script>
