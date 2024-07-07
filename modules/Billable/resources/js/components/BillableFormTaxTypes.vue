<template>
  <div class="flex flex-col space-y-1 sm:flex-row sm:space-x-2 sm:space-y-0">
    <IFormRadioField v-for="taxType in formattedTaxTypes" :key="taxType.value">
      <IFormRadio v-model="model" name="tax_type" :value="taxType.value" />

      <IFormRadioLabel :text="taxType.label" />
    </IFormRadioField>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useApp } from '@/Core/composables/useApp'

const model = defineModel()

const { t } = useI18n()
const { scriptConfig } = useApp()

const taxTypes = scriptConfig('taxes.types')

const formattedTaxTypes = computed(() =>
  taxTypes.map(type => ({
    value: type,
    label: t('billable::billable.tax_types.' + type),
  }))
)
</script>
