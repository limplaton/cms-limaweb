<template>
  <SimpleResourceCRUD
    :resource-name="resourceName"
    :with-cancel="false"
    @created="fetchCallOutcomes"
    @updated="fetchCallOutcomes"
    @deleted="fetchCallOutcomes"
  >
    <template #header>
      <ICardHeading :text="$t('calls::call.outcome.outcomes')" />
    </template>
  </SimpleResourceCRUD>
</template>

<script setup>
import SimpleResourceCRUD from '@/Core/components/SimpleResourceCRUD.vue'

import { useCallOutcomes } from '../composables/useCallOutcomes'

const resourceName = Innoclapps.resourceName('call-outcomes')

const { fetchCallOutcomes } = useCallOutcomes()
</script>
