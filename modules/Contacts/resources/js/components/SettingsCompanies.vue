<template>
  <ICardHeader>
    <ICardHeading :text="$t('contacts::company.companies')" />
  </ICardHeader>

  <ICard :overlay="!componentReady">
    <ICardBody>
      <IFormSwitchField>
        <IFormSwitchLabel
          :text="
            $t(
              'contacts::company.settings.automatically_associate_with_contacts'
            )
          "
        />

        <IFormSwitchDescription
          :text="
            $t(
              'contacts::company.settings.automatically_associate_with_contacts_info'
            )
          "
        />

        <IFormSwitch
          v-model="form.auto_associate_company_to_contact"
          @change="submit"
        />
      </IFormSwitchField>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { useSettings } from '@/Core/composables/useSettings'

const { form, submit, isReady: componentReady } = useSettings()
</script>
