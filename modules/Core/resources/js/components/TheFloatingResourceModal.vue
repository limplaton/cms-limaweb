<template>
  <component
    :is="`${resourceSingularName}FloatingModal`"
    v-if="hasFloatingResource"
    v-model:visible="isVisible"
    :floating-ready="floatingReady"
    :resource="resource"
    :fields="fields"
    :mode="mode"
    :update-handler="performUpdate"
    @hidden="handleModalHidden"
    @action-executed="handleActionExecuted"
    @view-requested="view"
  />
</template>

<script setup>
import { computed, nextTick, provide, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import omit from 'lodash/omit'

import { emitGlobal } from '../composables/useGlobalEventListener'
import { useResource } from '../composables/useResource'
import { useResourceFields } from '../composables/useResourceFields'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const resourceId = ref(null)
const resourceName = ref(null)
const mode = computed(() => route.query.mode || '')
const hasFloatingResource = ref(false)

const {
  fields,
  setResource,
  hydrateFields,
  totalCollapsable: totalCollapsableFields,
  getUpdateFields,
} = useResourceFields()

const isVisible = ref(false)
const floatingReady = ref(false)

const floatingKey = computed(() => {
  return String(
    String(route.query.floating_resource) +
      String(route.query.floating_resource_id)
  )
})

const {
  resource,
  resourceSingularName,
  updateResource,
  fetchResource,
  synchronizeResource,
  detachResourceAssociations,
  incrementResourceCount,
  decrementResourceCount,
} = useResource(resourceName, resourceId, {
  watchId: false,
})

function emitUpdatedGlobalEvent() {
  emitGlobal('floating-resource-updated', {
    resourceName: resourceName.value,
    resourceId: resourceId.value,
    resource: resource.value,
  })
}

async function performUpdate(form) {
  try {
    await updateResource(form)

    emitUpdatedGlobalEvent()

    Innoclapps.success(t('core::resource.updated'))
  } catch (e) {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  }
}

async function fetchFields() {
  // We will use update fields as if we use detail fields, some fields
  // may not be available for update when floating a resource.
  fields.value = await getUpdateFields(resourceName.value, resourceId.value)
}

async function fetchFieldsAndHydrateFromResource() {
  await fetchResource()
  await fetchFields()

  setResource(resource)
}

async function refreshFloatingResource() {
  await fetchFieldsAndHydrateFromResource()
}

function hideFloatingResource() {
  isVisible.value = false
}

async function boot() {
  fields.value = []
  resource.value = {}
  isVisible.value = true

  await fetchFieldsAndHydrateFromResource()

  floatingReady.value = true
}

/**
 * Helper method to navigate to the actual record full view/update
 * The method uses the current already fetched record from database and passes as meta
 * This helps not making the same request again
 */
function view() {
  router[resourceSingularName.value] = resource.value
  router.push(resource.value.path)
}

function handleActionExecuted(action) {
  if (action.destroyable) {
    hideFloatingResource()
  } else {
    refreshFloatingResource()
  }

  emitGlobal('floating-resource-action-executed', {
    resourceName: resourceName.value,
    resourceId: resourceId.value,
    resource: resource.value,
    action,
  })
}

function handleModalHidden() {
  floatingReady.value = false

  emitGlobal('floating-resource-hidden', {
    resourceName: resourceName.value,
    resourceId: resourceId.value,
  })

  router.replace({
    query: omit(route.query, [
      'floating_resource',
      'floating_resource_id',
      'mode',
    ]),
  })
}

watch(
  floatingKey,
  () => {
    resourceId.value = route.query.floating_resource_id
    resourceName.value = route.query.floating_resource

    if (resourceName.value && resourceId.value) {
      floatingReady.value = false
      hasFloatingResource.value = true
      nextTick(boot)
    } else {
      isVisible.value = false
      hasFloatingResource.value = false
    }
  },
  { immediate: true }
)

provide('refreshFloatingResource', refreshFloatingResource)
provide('hideFloatingResource', hideFloatingResource)
provide('synchronizeResource', synchronizeResource)
provide('detachResourceAssociations', detachResourceAssociations)
provide('incrementResourceCount', incrementResourceCount)
provide('decrementResourceCount', decrementResourceCount)
provide('emitUpdatedGlobalEvent', emitUpdatedGlobalEvent)
provide('hydrateFields', hydrateFields)
provide('totalCollapsableFields', totalCollapsableFields)

provide(
  'synchronizeAndEmitUpdatedEvent',
  (updatedResource, isFreshObject = false) => {
    synchronizeResource(updatedResource, isFreshObject)
    emitUpdatedGlobalEvent()
  }
)
</script>
