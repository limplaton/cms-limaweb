<template>
  <SimpleResourceCRUD
    :resource-name="resourceName"
    :with-cancel="false"
    @created="fetchActivityTypes"
    @updated="fetchActivityTypes"
    @deleted="fetchActivityTypes"
  >
    <template #header>
      <ICardHeading :text="$t('activities::activity.type.types')" />
    </template>
  </SimpleResourceCRUD>
</template>

<script setup>
import SimpleResourceCRUD from '@/Core/components/SimpleResourceCRUD.vue'

import { useActivityTypes } from '../composables/useActivityTypes'

const resourceName = Innoclapps.resourceName('activity-types')

const { fetchActivityTypes } = useActivityTypes()
</script>
