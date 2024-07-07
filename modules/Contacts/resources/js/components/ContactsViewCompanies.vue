<template>
  <ICard v-memo="[resource.companies.map(d => d.updated_at)]">
    <div class="px-5 py-4">
      <ContactCompaniesList
        :contact-id="resourceId"
        :companies="resource.companies"
        :authorize-dissociate="resource.authorizations.update"
        :show-create-button="resource.authorizations.update"
        @create-requested="
          $router.push({
            name: 'createCompanyViaContact',
            params: { id: resourceId },
          })
        "
      />
    </div>
  </ICard>
</template>

<script setup>
import ContactCompaniesList from './ContactCompaniesList.vue'

defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})
</script>
