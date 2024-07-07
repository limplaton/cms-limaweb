<template>
  <EditActivityModal
    :update-using="performUpdate"
    :is-ready="componentReady"
    :fields="fields"
    :resource="resource"
    @action-executed="handleActionExecuted"
    @hidden="$router.back()"
  />
</template>

<script setup>
import { computed, provide } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import { useResource } from '@/Core/composables/useResource'
import { useResourceFields } from '@/Core/composables/useResourceFields'

import EditActivityModal from '../components/EditActivityModal.vue'

const resourceName = Innoclapps.resourceName('activities')

const route = useRoute()

const computedId = computed(() => parseInt(route.params.id))

const { t } = useI18n()
const router = useRouter()

const {
  fields,
  hasFields: componentReady,
  hydrateFields,
  getUpdateFields,
} = useResourceFields()

const {
  resource,
  fetchResource,
  synchronizeResource,
  incrementResourceCount,
  decrementResourceCount,
  updateResource,
} = useResource(resourceName, computedId)

provide('hydrateFields', hydrateFields)
provide('synchronizeResource', synchronizeResource)
provide('synchronizeResourceSilently', synchronizeResource)
provide('incrementResourceCount', incrementResourceCount)
provide('decrementResourceCount', decrementResourceCount)

function handleActionExecuted(action) {
  if (!action.destroyable) {
    prepareComponent()
  } else {
    router.push({ name: 'activity-index' })
  }
}

async function performUpdate(form) {
  await updateResource(form)

  Innoclapps.success(t('core::resource.updated'))
}

async function prepareComponent() {
  await fetchResource()

  fields.value = await getUpdateFields(resourceName, computedId.value)
  hydrateFields(resource.value)
}

prepareComponent()
</script>
