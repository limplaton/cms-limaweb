<template>
  <ICard v-if="hasParents" v-memo="[resource.parents.map(p => p.updated_at)]">
    <div class="px-5 py-4">
      <CompaniesList
        :title="$t('contacts::company.child', { count: totalParents })"
        :companies="resource.parents"
      />
    </div>
  </ICard>
</template>

<script setup>
import { computed } from 'vue'

import CompaniesList from './CompaniesList.vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})

const totalParents = computed(() => props.resource.parents.length)

const hasParents = computed(() => totalParents.value > 0)
</script>
