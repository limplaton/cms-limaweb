<template>
  <CompaniesList
    :companies="companies"
    :empty-text="$t('deals::deal.no_companies_associated')"
  >
    <template #actions="{ company }">
      <IButton
        v-show="authorizeDissociate"
        v-i-tooltip.left="$t('contacts::company.dissociate')"
        icon="XSolid"
        basic
        small
        @click="$confirm(() => dissociateCompany(company.id))"
      />
    </template>

    <template #top-actions>
      <IButton
        v-if="showCreateButton"
        v-i-tooltip="$t('contacts::company.add')"
        class="-my-1.5 ml-4"
        icon="PlusSolid"
        basic
        small
        @click="$emit('createRequested')"
      />
    </template>
  </CompaniesList>
</template>

<script setup>
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'

import CompaniesList from '@/Contacts/components/CompaniesList.vue'

defineProps({
  companies: { required: true, type: Array },
  dealId: { required: true, type: Number },
  authorizeDissociate: { required: true, type: Boolean },
  showCreateButton: { required: true, type: Boolean },
})

const emit = defineEmits(['dissociated', 'createRequested'])

const { t } = useI18n()

const detachResourceAssociations = inject('detachResourceAssociations')

async function dissociateCompany(id) {
  await detachResourceAssociations({ companies: [id] })

  emit('dissociated', id)

  Innoclapps.success(t('core::resource.dissociated'))
}
</script>
