<template>
  <CreateCompanyModal
    :title="modalTitle"
    :ok-text="
      hasSelectedExistingCompany
        ? $t('core::app.associate')
        : $t('core::app.create')
    "
    :[viaResource]="viaResource ? [parentResource] : undefined"
    :fields-visible="!hasSelectedExistingCompany"
    :with-extended-submit-buttons="!hasSelectedExistingCompany"
    :create-using="
      createFunc => (hasSelectedExistingCompany ? associate() : createFunc())
    "
  >
    <template #top="{ isReady }">
      <div
        v-if="viaResource"
        v-show="isReady"
        class="mb-4 rounded-lg border border-neutral-300 bg-neutral-50/80 px-4 py-3 dark:border-neutral-500/30 dark:bg-neutral-500/10"
      >
        <FormFields
          :fields="associateField"
          :form="associateForm"
          :resource-name="resourceName"
          is-floating
          @update-field-value="
            associateForm.fill($event.attribute, $event.value)
          "
          @set-initial-value="associateForm.set($event.attribute, $event.value)"
        />
      </div>
    </template>
  </CreateCompanyModal>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'
import { usePageTitle } from '@/Core/composables/usePageTitle'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  viaResource: String,
  parentResource: Object,
})

const emit = defineEmits(['associated'])

const resourceName = Innoclapps.resourceName('companies')

const { t } = useI18n()

const { fields: associateField } = useResourceFields([
  {
    asyncUrl: '/companies/search',
    attribute: 'companies',
    formComponent: 'FormSelectField',
    helpText: t('contacts::company.associate_field_info'),
    helpTextDisplay: 'text',
    label: t('contacts::company.company'),
    labelKey: 'name',
    valueKey: 'id',
    lazyLoad: { url: '/companies', params: { order: 'created_at|desc' } },
  },
])

const { form: associateForm } = useForm()

const hasSelectedExistingCompany = computed(() => !!associateForm.companies)

const modalTitle = computed(() => {
  if (!props.viaResource) {
    return t('contacts::company.create')
  }

  if (!hasSelectedExistingCompany.value) {
    return t('contacts::company.create_with', {
      name: props.parentResource.display_name,
    })
  }

  return t('contacts::company.associate_with', {
    name: props.parentResource.display_name,
  })
})

async function associate() {
  await associateForm
    .set({ companies: [associateForm.companies] }) // set the value as an array
    .put(`associations/${props.viaResource}/${props.parentResource.id}`)

  emit('associated', associateForm.companies[0])

  Innoclapps.success(t('core::resource.associated'))
}

if (!props.viaResource) {
  usePageTitle(t('contacts::company.create'))
}
</script>
