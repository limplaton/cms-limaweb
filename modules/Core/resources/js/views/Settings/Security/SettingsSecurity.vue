<template>
  <ICardHeader>
    <ICardHeading :text="$t('core::settings.security.security')" />
  </ICardHeader>

  <ICard :overlay="!componentReady">
    <ICardBody>
      <IFormSwitchGroup>
        <IFormSwitchField>
          <IFormSwitchLabel
            :text="$t('core::settings.security.disable_password_forgot')"
          />

          <IFormSwitchDescription
            :text="$t('core::settings.security.disable_password_forgot_info')"
          />

          <IFormSwitch
            v-model="form.disable_password_forgot"
            @change="submit"
          />
        </IFormSwitchField>

        <IFormSwitchField>
          <IFormSwitchLabel
            :text="$t('core::settings.security.block_bad_visitors')"
          />

          <IFormSwitchDescription
            :text="$t('core::settings.security.block_bad_visitors_info')"
          />

          <IFormSwitch v-model="form.block_bad_visitors" @change="submit" />
        </IFormSwitchField>
      </IFormSwitchGroup>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { useSettings } from '../../../composables/useSettings'

const { form, submit, isReady: componentReady } = useSettings()
</script>
