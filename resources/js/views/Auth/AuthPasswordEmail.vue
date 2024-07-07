<template>
  <form class="space-y-6" @submit.prevent="submit">
    <IAlert variant="success" :show="successMessage !== null">
      <IAlertBody>
        {{ successMessage || '' }}
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

    <IFormGroup v-if="reCaptcha.validate">
      <VueRecaptcha
        ref="reCaptchaRef"
        :sitekey="reCaptcha.siteKey"
        @verify="handleReCaptchaVerified"
      />

      <IFormError :error="form.getError('g-recaptcha-response')" />
    </IFormGroup>

    <IButton
      type="submit"
      variant="primary"
      :disabled="requestInProgress || !Boolean(form.email)"
      :loading="requestInProgress"
      :text="$t('passwords.send_password_reset_link')"
      block
      @click="sendPasswordResetEmail"
    />
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { VueRecaptcha } from 'vue-recaptcha'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

const { appUrl, scriptConfig } = useApp()

const { form } = useForm(
  {
    email: null,
    'g-recaptcha-response': null,
  },
  {
    resetOnSuccess: true,
  }
)

const reCaptcha = scriptConfig('reCaptcha') || {}
const reCaptchaRef = ref(null)
const requestInProgress = ref(false)
const successMessage = ref(null)

async function sendPasswordResetEmail() {
  requestInProgress.value = true
  successMessage.value = null

  await Innoclapps.request(appUrl + '/sanctum/csrf-cookie')

  form
    .post(appUrl + '/password/email')
    .then(data => {
      successMessage.value = data.message
    })
    .finally(() => {
      requestInProgress.value = false
      reCaptchaRef.value && reCaptchaRef.value.reset()
    })
}

function handleReCaptchaVerified(response) {
  form.fill('g-recaptcha-response', response)
}
</script>
