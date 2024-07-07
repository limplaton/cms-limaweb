<template>
  <form class="space-y-6" @submit.prevent="submit">
    <IAlert variant="success" :show="successMessage !== null">
      <IAlertBody>
        {{ successMessage }}

        <!-- We will redirect to login as the user is already logged in and will be redirected to the HOME route -->
        <ILinkBase class="mt-2 font-medium" variant="primary" href="/login">
          {{ $t('core::dashboard.dashboard') }}
        </ILinkBase>
      </IAlertBody>
    </IAlert>

    <IFormGroup label-for="email" :label="$t('auth.email_address')">
      <IFormInput
        id="email"
        v-model="form.email"
        type="email"
        name="email"
        autocomplete="email"
        autofocus
        required
      />

      <IFormError :error="form.getError('email')" />
    </IFormGroup>

    <IFormGroup label-for="password" :label="$t('auth.password')">
      <IFormInput
        id="password"
        v-model="form.password"
        type="password"
        name="password"
        autocomplete="new-password"
        required
      />

      <IFormError :error="form.getError('password')" />
    </IFormGroup>

    <IFormGroup
      label-for="password-confirm"
      :label="$t('auth.confirm_password')"
    >
      <IFormInput
        id="password-confirm"
        v-model="form.password_confirmation"
        type="password"
        name="password_confirmation"
        autocomplete="new-password"
        required
      />
    </IFormGroup>

    <IButton
      type="submit"
      variant="primary"
      :disabled="requestInProgress"
      :loading="requestInProgress"
      :text="$t('passwords.reset_password')"
      block
      @click="resetPassword"
    />
  </form>
</template>

<script setup>
import { ref } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

const props = defineProps({
  email: String,
  token: { required: true, type: String },
})

const { appUrl } = useApp()

const requestInProgress = ref(false)
const successMessage = ref(null)

const { form } = useForm({
  token: props.token,
  email: props.email,
  password: null,
  password_confirmation: null,
})

async function resetPassword() {
  requestInProgress.value = true

  await Innoclapps.request(appUrl + '/sanctum/csrf-cookie')

  form
    .post(appUrl + '/password/reset')
    .then(data => (successMessage.value = data.message))
    .finally(() => (requestInProgress.value = false))
}
</script>
