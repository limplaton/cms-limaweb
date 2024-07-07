<template>
  <ICard v-memo="[resource.companies.map(d => d.updated_at)]">
    <div class="px-5 py-4">
      <DealCompaniesList
        :deal-id="resourceId"
        :companies="resource.companies"
        :authorize-dissociate="resource.authorizations.update"
        :show-create-button="resource.authorizations.update"
        @create-requested="
          $router.push({
            name: 'createCompanyViaDeal',
            params: { id: resourceId },
          })
        "
      />
    </div>
  </ICard>
</template>

<script setup>
import DealCompaniesList from './DealCompaniesList.vue'

defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})
</script>
