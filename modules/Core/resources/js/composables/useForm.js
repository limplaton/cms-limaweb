
import { reactive } from 'vue'
import { watchDebounced } from '@vueuse/core'
import cloneDeep from 'lodash/cloneDeep'
import isArray from 'lodash/isArray'
import isEqual from 'lodash/isEqual'
import isObject from 'lodash/isObject'
import lodashMerge from 'lodash/merge'

import Form from '@/Core/services/Form/Form'

export function useForm(data = {}, options = {}) {
  const autoClearErrors = options.autoClearErrors !== false
  delete options.autoClearErrors

  const form = reactive(new Form(data, options))

  if (autoClearErrors) {
    const debounceWait = 250
    let previousFormData = {}

    watchDebounced(
      () => form.data(),
      () => updateFormErrors(),
      { debounce: debounceWait, deep: true, immediate: true }
    )

    // eslint-disable-next-line no-inner-declarations
    function updateFormErrors() {
      const currentFormData = form.data()

      form.keys().forEach(field => {
        const oldVal = previousFormData[field]
        const newVal = currentFormData[field]

        // Handle primitive and objects
        if (!Array.isArray(newVal)) {
          if (newVal != oldVal) form.errors.clear(field)

          return
        }

        // Handle arrays
        if (!isEqual(newVal, oldVal)) {
          form.errors.clear(field)

          if (Array.isArray(oldVal)) {
            newVal.forEach(item => {
              if (isObject(item)) defineFormDataObjectUniqueId(item)
            })

            const useUniqueId = oldVal.some(item => item && item._formUniqueId)

            if (useUniqueId) {
              form.errors.set(
                updateErrorKeysForArrayChanges(
                  field,
                  oldVal,
                  newVal,
                  form.errors.all()
                )
              )
            } else {
              oldVal.forEach((val, idx) => {
                if (val !== newVal[idx]) form.errors.clear(`${field}.${idx}`)
              })
            }
          }
        }
      })

      previousFormData = deepCloneWithUniqueFormId(currentFormData)
    }
  }

  return { form }
}

export { defineFormDataObjectUniqueId, deepCloneWithUniqueFormId }

function deepCloneWithUniqueFormId(obj, merge = {}) {
  // Clone the main object and merge the additional values
  let clonedObj = lodashMerge({}, cloneDeep(obj), merge)

  if ('_formUniqueId' in obj) {
    defineFormDataObjectUniqueId(clonedObj, obj._formUniqueId)
  }

  // Handle arrays within the object
  Object.keys(clonedObj).forEach(key => {
    if (isArray(clonedObj[key])) {
      clonedObj[key] = obj[key].map(item => {
        if (isObject(item)) {
          let clonedItem = cloneDeep(item)

          if ('_formUniqueId' in item) {
            defineFormDataObjectUniqueId(clonedItem, item._formUniqueId)
          }

          return clonedItem
        }

        return item // Return non-object items as is
      })
    }
  })

  return clonedObj
}

/**
 * Form objects must have a unique identifier so we can determine
 * whether to clear the errors when the object is changed, removed or reordered.
 */
function defineFormDataObjectUniqueId(obj, id) {
  if ('_formUniqueId' in obj === false) {
    Object.defineProperty(obj, '_formUniqueId', {
      enumerable: false,
      configurable: false,
      writable: false,
      value: id || Symbol(),
    })
  }

  return obj
}

/**
 * Handle the update of the form errors based on changes.
 */
function updateErrorKeysForArrayChanges(dataKey, oldArray, newArray, errors) {
  let updatedErrors = { ...errors }

  // Accumulate changes here
  const changes = {}

  // Map each unique ID to its corresponding object in the new array
  const idToNewObjectMap = new Map(
    newArray.map(item => [item._formUniqueId, item])
  )

  oldArray.forEach(item => {
    const oldIndex = oldArray.findIndex(
      oldItem => oldItem._formUniqueId === item._formUniqueId
    )

    const newItem = idToNewObjectMap.get(item._formUniqueId)

    const newIndex = newArray.findIndex(
      newItem => newItem && newItem._formUniqueId === item._formUniqueId
    )

    const itemRemoved = !newItem
    const itemChanged = !itemRemoved && !isEqual(item, newItem)
    const itemReordered = newIndex !== oldIndex

    Object.keys(updatedErrors).forEach(errorKey => {
      const pattern = new RegExp(`^${dataKey}\\.(${oldIndex})(\\.|$)`)

      if (pattern.test(errorKey)) {
        if (itemRemoved || (itemChanged && !itemReordered)) {
          // Remove errors for removed or changed only items
          delete updatedErrors[errorKey]
        } else if (itemReordered) {
          // Adjust error keys for reordered items
          const newKey = errorKey.replace(pattern, `${dataKey}.${newIndex}$2`)

          changes[errorKey] = newKey
        }
      }
    })
  })

  // Apply the accumulated changes
  Object.entries(changes).forEach(([oldKey, newKey]) => {
    updatedErrors[newKey] = updatedErrors[oldKey]
    delete updatedErrors[oldKey]
  })

  return updatedErrors
}
