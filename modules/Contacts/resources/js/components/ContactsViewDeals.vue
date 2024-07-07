<template>
  <ICard v-memo="[resource.deals.map(d => d.updated_at)]">
    <div class="px-5 py-4">
      <ContactDealsList
        :contact-id="resourceId"
        :deals="resource.deals"
        :authorize-dissociate="resource.authorizations.update"
        :show-create-button="resource.authorizations.update"
        @create-requested="
          $router.push({
            name: 'createDealViaContact',
            params: { id: resourceId },
          })
        "
      />
    </div>
  </ICard>
</template>

<script setup>
import ContactDealsList from './ContactDealsList.vue'

defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})
</script>
