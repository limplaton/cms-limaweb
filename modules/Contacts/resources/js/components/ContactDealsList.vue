<template>
  <DealsList
    :deals="deals"
    :empty-text="$t('contacts::contact.no_deals_associated')"
  >
    <template #actions="{ deal }">
      <IButton
        v-show="authorizeDissociate"
        v-i-tooltip.left="$t('deals::deal.dissociate')"
        icon="XSolid"
        basic
        small
        @click="$confirm(() => dissociateDeal(deal.id))"
      />
    </template>

    <template #top-actions>
      <IButton
        v-if="showCreateButton"
        v-i-tooltip="$t('deals::deal.add')"
        class="-my-1.5 ml-4"
        icon="PlusSolid"
        basic
        small
        @click="$emit('createRequested')"
      />
    </template>
  </DealsList>
</template>

<script setup>
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'

import DealsList from '@/Deals/components/DealsList.vue'

defineProps({
  deals: { required: true, type: Array },
  contactId: { required: true, type: Number },
  authorizeDissociate: { required: true, type: Boolean },
  showCreateButton: { required: true, type: Boolean },
  displayMode: { default: 'Boolean' },
})

const emit = defineEmits(['dissociated', 'createRequested'])

const { t } = useI18n()

const detachResourceAssociations = inject('detachResourceAssociations')

async function dissociateDeal(id) {
  await detachResourceAssociations({ deals: [id] })

  emit('dissociated', id)

  Innoclapps.success(t('core::resource.dissociated'))
}
</script>
