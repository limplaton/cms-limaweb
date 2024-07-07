<template>
  <ContactsList
    :contacts="contacts"
    :empty-text="$t('contacts::company.no_contacts_associated')"
  >
    <template #actions="{ contact }">
      <IButton
        v-show="authorizeDissociate"
        v-i-tooltip.left="$t('contacts::contact.dissociate')"
        icon="XSolid"
        basic
        small
        @click="$confirm(() => dissociateContact(contact.id))"
      />
    </template>

    <template #top-actions>
      <IButton
        v-if="showCreateButton"
        v-i-tooltip="$t('contacts::contact.add')"
        class="-my-1.5 ml-4"
        icon="PlusSolid"
        basic
        small
        @click="$emit('createRequested')"
      />
    </template>
  </ContactsList>
</template>

<script setup>
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'

import ContactsList from './ContactsList.vue'

defineProps({
  contacts: { required: true, type: Array },
  companyId: { required: true, type: Number },
  authorizeDissociate: { required: true, type: Boolean },
  showCreateButton: { required: true, type: Boolean },
})

const emit = defineEmits(['dissociated', 'createRequested'])

const detachResourceAssociations = inject('detachResourceAssociations')

const { t } = useI18n()

async function dissociateContact(id) {
  await detachResourceAssociations({ contacts: [id] })

  emit('dissociated', id)

  Innoclapps.success(t('core::resource.dissociated'))
}
</script>
