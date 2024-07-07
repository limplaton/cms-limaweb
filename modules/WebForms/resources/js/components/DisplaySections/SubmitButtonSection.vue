<template>
  <div>
    <IFormGroup v-if="validateWithReCaptcha">
      <VueRecaptcha
        :sitekey="reCaptcha.siteKey"
        @verify="handleReCaptchaVerified"
      />

      <IFormError :error="form.getError('g-recaptcha-response')" />
    </IFormGroup>

    <div v-if="section.privacyPolicyAcceptIsRequired" class="flex">
      <IFormCheckboxField>
        <IFormCheckbox
          v-model:checked="privacyPolicyAccepted"
          class="self-start"
          @change="form.fill('_privacy-policy', $event)"
        />

        <IFormCheckboxLabel class="-mt-1 text-neutral-500">
          <I18nT scope="global" :keypath="'core::app.agree_to_privacy_policy'">
            <template #privacyPolicyLink>
              <ILink variant="primary" :href="section.privacyPolicyUrl">
                {{ $t('core::app.privacy_policy') }}
              </ILink>
            </template>
          </I18nT>
        </IFormCheckboxLabel>
      </IFormCheckboxField>
    </div>

    <IFormError class="ml-6" :error="form.getError('_privacy-policy')" />

    <IButton
      id="submitButton"
      variant="primary"
      class="mt-3"
      type="submit"
      :disabled="form.busy"
      :loading="form.busy"
      :text="section.text"
      block
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { VueRecaptcha } from 'vue-recaptcha'

import { useApp } from '@/Core/composables/useApp'

import propsDefinition from './props'

const props = defineProps(propsDefinition)

const emit = defineEmits({
  fillFormAttribute: ({ attribute, value }) => {
    if (attribute && typeof value != 'undefined') {
      return true
    } else {
      console.warn('Invalid "fillFormAttribute" event payload!')

      return false
    }
  },
})

const { scriptConfig } = useApp()

const reCaptcha = scriptConfig('reCaptcha') || {}
const privacyPolicyAccepted = ref(false)

const validateWithReCaptcha = computed(() => {
  if (!props.section.spamProtected) {
    return false
  }

  return reCaptcha.validate && reCaptcha.configured
})

function handleReCaptchaVerified(response) {
  emit('fillFormAttribute', {
    attribute: 'g-recaptcha-response',
    value: response,
  })
}
</script>

<style>
#submitButton {
  color: var(--primary-contrast);
}
</style>
