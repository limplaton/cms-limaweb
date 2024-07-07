
import { ref, toValue, watchEffect } from 'vue'

export function useMessageAssociations(resourceName, record) {
  const newMessageAdditionalAssociations = ref([])

  watchEffect(() => {
    newMessageAdditionalAssociations.value = []

    const unwrappedRecord = toValue(record)
    const unwrappedResource = toValue(resourceName)

    // When navigating from same resource the record is reset and may be empty object.
    if (Object.keys(unwrappedRecord).length === 0) {
      return
    }

    if (unwrappedResource === 'deals' || unwrappedResource === 'contacts') {
      if (unwrappedRecord.companies.length === 1) {
        newMessageAdditionalAssociations.value.push({
          id: unwrappedRecord.companies[0].id,
          display_name: unwrappedRecord.companies[0].display_name,
          path: unwrappedRecord.companies[0].path,
          resourceName: Innoclapps.resourceName('companies'),
        })
      }
    } else if (
      unwrappedResource === Innoclapps.resourceName('companies') &&
      unwrappedRecord.contacts.length === 1
    ) {
      newMessageAdditionalAssociations.value.push({
        id: unwrappedRecord.contacts[0].id,
        display_name: unwrappedRecord.contacts[0].display_name,
        path: unwrappedRecord.contacts[0].path,
        resourceName: Innoclapps.resourceName('contacts'),
      })
    }
  })

  return {
    newMessageAdditionalAssociations,
  }
}
