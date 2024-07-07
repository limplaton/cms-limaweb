<template>
  <ICard v-memo="[resource.contacts.map(d => d.updated_at)]">
    <div class="px-5 py-4">
      <DealContactCard
        :deal-id="resourceId"
        :contacts="resource.contacts"
        :authorize-dissociate="resource.authorizations.update"
        :show-create-button="resource.authorizations.update"
        @create-requested="
          $router.push({
            name: 'createContactViaDeal',
            params: { id: resourceId },
          })
        "
      />
    </div>
  </ICard>
</template>

<script setup>
import DealContactCard from './DealContactsList.vue'

defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})
</script>
