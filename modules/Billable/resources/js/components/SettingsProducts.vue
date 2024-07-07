<template>
  <ICardHeader>
    <ICardHeading :text="$t('billable::product.products')" />
  </ICardHeader>

  <ICard as="form" :overlay="!componentReady" @submit.prevent="submit">
    <ICardBody>
      <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
        <div class="sm:col-span-2">
          <IFormGroup
            label-for="tax_label"
            :label="$t('billable::product.tax_label')"
            required
          >
            <IFormInput id="tax_label" v-model="form.tax_label" />
          </IFormGroup>
        </div>

        <div class="sm:col-span-2">
          <IFormGroup
            label-for="tax_rate"
            :label="$t('billable::product.tax_rate')"
          >
            <IFormNumericInput
              id="tax_rate"
              v-model="form.tax_rate"
              :placeholder="$t('billable::product.tax_percent')"
              :precision="3"
              :minus="true"
            >
            </IFormNumericInput>
          </IFormGroup>
        </div>
      </div>

      <IFormGroup
        class="mt-3 space-y-1"
        label-for="tax_type"
        :label="$t('billable::product.settings.default_tax_type')"
      >
        <IFormRadioField v-for="taxType in taxTypes" :key="taxType">
          <IFormRadio
            v-model="form.tax_type"
            name="tax_type"
            :value="taxType"
          />

          <IFormRadioLabel
            :text="$t('billable::billable.tax_types.' + taxType)"
          />
        </IFormRadioField>
      </IFormGroup>

      <IFormGroup
        label-for="tax_type"
        class="mt-3 space-y-1"
        :label="$t('billable::product.settings.default_discount_type')"
      >
        <IFormRadioField
          v-for="discountType in discountTypes"
          :key="discountType.value"
        >
          <IFormRadio
            v-model="form.discount_type"
            name="discount_type"
            :value="discountType.value"
          />

          <IFormRadioLabel :text="discountType.label" />
        </IFormRadioField>
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
</template>

<script setup>
import { useApp } from '@/Core/composables/useApp'
import { useSettings } from '@/Core/composables/useSettings'

const { form, isReady: componentReady, submit } = useSettings()
const { scriptConfig } = useApp()

const taxTypes = scriptConfig('taxes.types')

const discountTypes = [
  { label: scriptConfig('currency.iso_code'), value: 'fixed' },
  { label: '%', value: 'percent' },
]
</script>
