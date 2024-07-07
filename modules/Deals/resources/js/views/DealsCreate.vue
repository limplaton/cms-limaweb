<template>
  <CreateDealModal
    :title="modalTitle"
    :ok-text="
      hasSelectedExistingDeal
        ? $t('core::app.associate')
        : $t('core::app.create')
    "
    :[viaResource]="viaResource ? [parentResource] : undefined"
    :fields-visible="!hasSelectedExistingDeal"
    :with-extended-submit-buttons="!hasSelectedExistingDeal"
    :create-using="
      createFunc => (hasSelectedExistingDeal ? associate() : createFunc())
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
  </CreateDealModal>
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

const resourceName = Innoclapps.resourceName('deals')

const { t } = useI18n()

const { fields: associateField } = useResourceFields([
  {
    asyncUrl: '/deals/search',
    attribute: 'deals',
    formComponent: 'FormSelectField',
    helpText: t('deals::deal.associate_field_info'),
    helpTextDisplay: 'text',
    label: t('deals::deal.deal'),
    labelKey: 'name',
    valueKey: 'id',
    lazyLoad: { url: '/deals', params: { order: 'created_at|desc' } },
  },
])

const { form: associateForm } = useForm()

const hasSelectedExistingDeal = computed(() => !!associateForm.deals)

const modalTitle = computed(() => {
  if (!props.viaResource) {
    return t('deals::deal.create')
  }

  if (!hasSelectedExistingDeal.value) {
    return t('deals::deal.create_with', {
      name: props.parentResource.display_name,
    })
  }

  return t('deals::deal.associate_with', {
    name: props.parentResource.display_name,
  })
})

async function associate() {
  await associateForm
    .set({ deals: [associateForm.deals] }) // set the value as an array
    .put(`associations/${props.viaResource}/${props.parentResource.id}`)

  emit('associated', associateForm.deals[0])

  Innoclapps.success(t('core::resource.associated'))
}

if (!props.viaResource) {
  usePageTitle(t('deals::deal.create'))
}
</script>
