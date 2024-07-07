<template>
  <EditActivityModal
    :update-using="updateHandler"
    :is-ready="floatingReady"
    :fields="fields"
    :resource="resource"
  />
</template>

<script setup>
import { inject, provide } from 'vue'

import EditActivityModal from './EditActivityModal.vue'

defineProps({
  resource: { required: true, type: Object },
  fields: { required: true, type: Array },
  mode: { required: true, type: String },
  floatingReady: { required: true, type: Boolean },
  updateHandler: { required: true, type: Function },
})

const synchronizeResource = inject('synchronizeResource')
const synchronizeAndEmitUpdatedEvent = inject('synchronizeAndEmitUpdatedEvent')

provide('synchronizeResourceSilently', synchronizeResource)
provide('synchronizeResource', synchronizeAndEmitUpdatedEvent)
</script>
