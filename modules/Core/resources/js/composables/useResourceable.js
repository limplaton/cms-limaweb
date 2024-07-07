
import { computed, isRef, ref, shallowRef, toValue, watch } from 'vue'

import { useForm } from './useForm'
import { emitGlobal } from './useGlobalEventListener'

/**
 * Composable function for resource-related operations.
 *
 * @param {import('vue').Ref<string>|string} resourceName - The name of the resource.
 * @returns {{
 *   retrieveResource: (resourceId: number|string) => Promise<Object>,
 *   resourceBeingRetrieved: import('vue').Ref<boolean>,
 *   createResource: (attributes: Object) => Promise<Object>,
 *   resourceBeingCreated: import('vue').Ref<boolean>,
 *   cloneResource: (id: number|string) => Promise<Object>,
 *   resourceBeingCloned: import('vue').Ref<boolean>,
 *   updateResource: (attributes: Object, id: number|string, config?: Object) => Promise<Object>,
 *   resourceBeingUpdated: import('vue').Ref<boolean>,
 *   deleteResource: (id: number|string) => Promise<any>,
 *   resourceBeingDeleted: import('vue').Ref<boolean>,
 *   detachAssociations: (resourceId: number|string, attrs: Object) => Promise<Object>,
 *   syncAssociations: (resourceId: number|string, attrs: Object) => Promise<Object>,
 *   associationsBeingSaved: import('vue').Ref<boolean>,
 *   resourceName: import('vue').Ref<string>|string,
 *   rawResourceName: string | null,
 *   getRawResourceName: () => string | null,
 *   resourceSingularName: import('vue').ComputedRef<string>
 * }}
 */
export function useResourceable(resourceName) {
  let rawResourceName = null,
    _resource = shallowRef({})

  if (isRef(resourceName)) {
    watch(
      resourceName,
      newVal => {
        rawResourceName = newVal

        if (newVal) {
          _resource.value = Innoclapps.resource(newVal)
        }
      },
      { immediate: true, flush: 'post' }
    )
  } else {
    rawResourceName = toValue(resourceName)
    _resource.value = Innoclapps.resource(rawResourceName) || {}
  }

  const resourceBeingCreated = ref(false)
  const resourceBeingDeleted = ref(false)
  const resourceBeingUpdated = ref(false)
  const resourceBeingRetrieved = ref(false)
  const resourceBeingCloned = ref(false)
  const associationsBeingSaved = ref(false)
  const resourceSingularName = computed(() => _resource.value.singularName)

  function getRawResourceName() {
    return rawResourceName
  }

  async function retrieveResource(resourceId) {
    resourceBeingRetrieved.value = true

    try {
      let { data } = await Innoclapps.request(
        `/${getRawResourceName()}/${resourceId}`
      )

      return data
    } finally {
      resourceBeingRetrieved.value = false
    }
  }

  async function updateResource(attributes, id, config = {}) {
    let form

    // is not form?
    if (typeof attributes.put !== 'function') {
      form = useForm(attributes).form
    } else {
      form = attributes
    }

    resourceBeingUpdated.value = true

    try {
      const updatedResource = await form.put(
        `/${rawResourceName}/${id}`,
        config
      )

      emitGlobal('resource-updated', {
        resourceName: rawResourceName,
        resourceId: id,
        resource: updatedResource,
      })

      emitGlobal(`${rawResourceName}-updated`, updatedResource)

      return updatedResource
    } finally {
      resourceBeingUpdated.value = false
    }
  }

  async function createResource(attributes) {
    let form

    if (typeof attributes.post !== 'function') {
      form = useForm(attributes).form
    } else {
      form = attributes
    }

    resourceBeingCreated.value = true

    try {
      const data = await form.post(`/${rawResourceName}`)

      emitGlobal('resource-updated', {
        resourceName: rawResourceName,
        resourceId: data.id,
        resource: data,
      })

      emitGlobal(`${rawResourceName}-updated`, data)

      return data
    } finally {
      resourceBeingCreated.value = false
    }
  }

  async function deleteResource(id) {
    resourceBeingDeleted.value = true

    try {
      const data = await Innoclapps.request().delete(
        `/${rawResourceName}/${id}`
      )

      emitGlobal('resource-deleted', {
        resourceName: rawResourceName,
        resourceId: id,
      })

      emitGlobal(`${rawResourceName}-deleted`, data)

      return data
    } finally {
      resourceBeingDeleted.value = false
    }
  }

  async function cloneResource(id) {
    resourceBeingCloned.value = true

    try {
      const { data } = await Innoclapps.request().post(
        `/${rawResourceName}/${id}/clone`
      )

      emitGlobal('resource-cloned', {
        resourceName: rawResourceName,
        resourceId: id,
        clonedResource: data,
      })

      emitGlobal(`${rawResourceName}-cloned`, data)

      return data
    } finally {
      resourceBeingCloned.value = false
    }
  }

  async function syncAssociations(resourceId, attrs) {
    associationsBeingSaved.value = true

    try {
      let { data } = await Innoclapps.request().post(
        `associations/${rawResourceName}/${resourceId}`,
        attrs
      )

      return data
    } finally {
      associationsBeingSaved.value = false
    }
  }

  async function detachAssociations(resourceId, attrs) {
    associationsBeingSaved.value = true

    try {
      const { data } = await Innoclapps.request().delete(
        `associations/${rawResourceName}/${resourceId}`,
        {
          data: attrs,
        }
      )

      return data
    } finally {
      associationsBeingSaved.value = false
    }
  }

  return {
    retrieveResource,
    resourceBeingRetrieved,

    createResource,
    resourceBeingCreated,

    cloneResource,
    resourceBeingCloned,

    updateResource,
    resourceBeingUpdated,

    deleteResource,
    resourceBeingDeleted,

    detachAssociations,
    syncAssociations,
    associationsBeingSaved,

    resourceName,
    rawResourceName,

    getRawResourceName,
    resourceSingularName,
  }
}
