<template>
  <form class="space-y-6" @submit.prevent="submit">
    <IFormGroup label-for="email" :label="$t('auth.login')">
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
        ref="passwordRef"
        v-model="form.password"
        type="password"
        name="password"
        autocomplete="current-password"
        required
      />

      <IFormError :error="form.getError('password')" />
    </IFormGroup>

    <IFormGroup v-if="reCaptcha.validate">
      <VueRecaptcha
        ref="reCaptchaRef"
        :sitekey="reCaptcha.siteKey"
        @verify="handleReCaptchaVerified"
      />

      <IFormError :error="form.getError('g-recaptcha-response')" />
    </IFormGroup>

    <div class="flex items-center justify-between">
      <IFormCheckboxField>
        <IFormCheckbox v-model="form.remember" />

        <IFormCheckboxLabel :text="$t('auth.remember_me')" />
      </IFormCheckboxField>

      <ILinkBase
        v-if="!setting('disable_password_forgot')"
        variant="primary"
        href="/password/reset"
      >
        {{ $t('auth.forgot_password') }}
      </ILinkBase>
    </div>

    <IButton
      type="submit"
      variant="primary"
      :disabled="submitButtonIsDisabled"
      :loading="requestInProgress"
      :text="$t('auth.login')"
      block
      @click="login"
    />
  </form>
</template>

<script setup>
import { computed, ref } from 'vue'
import { VueRecaptcha } from 'vue-recaptcha'

import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

const { appUrl, scriptConfig, setting } = useApp()

const reCaptcha = scriptConfig('reCaptcha') || {}
const passwordRef = ref(null)
const reCaptchaRef = ref(null)
const requestInProgress = ref(false)

const { form } = useForm({
  email: null,
  password: null,
  remember: null,
  'g-recaptcha-response': null,
})

const submitButtonIsDisabled = computed(() => requestInProgress.value)

async function login() {
  requestInProgress.value = true
  passwordRef.value.blur()

  await Innoclapps.request(appUrl + '/sanctum/csrf-cookie')

  form
    .post(appUrl + '/login')
    .then(data => (window.location.href = data.redirect_path))
    .finally(() => reCaptchaRef.value && reCaptchaRef.value.reset())
    .catch(() => (requestInProgress.value = false))
}

function handleReCaptchaVerified(response) {
  form.fill('g-recaptcha-response', response)
}
</script>
