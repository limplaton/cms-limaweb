<template>
  <SimpleResourceCRUD
    :resource-name="resourceName"
    :with-cancel="false"
    @created="fetchLostReasons"
    @updated="fetchLostReasons"
    @deleted="fetchLostReasons"
  >
    <template #header>
      <ICardHeading :text="$t('deals::deal.lost_reasons.lost_reasons')" />
    </template>
  </SimpleResourceCRUD>
</template>

<script setup>
import SimpleResourceCRUD from '@/Core/components/SimpleResourceCRUD.vue'

import { useLostReasons } from '../composables/useLostReasons'

const resourceName = Innoclapps.resourceName('lost-reasons')

const { fetchLostReasons } = useLostReasons()
</script>
