
import { computed, ref, toValue } from 'vue'
import find from 'lodash/find'
import map from 'lodash/map'
import orderBy from 'lodash/orderBy'

import { randomString } from '@/Core/utils'

export function useFieldSection(
  resourceName,
  resourceFields,
  selectedSections
) {
  const field = ref(null)
  const fieldLabel = ref(null)
  const isRequired = ref(false)

  const fieldMustBeRequired = computed(
    () => field.value && field.value.isRequired && field.value.primary
  )

  const availableFields = computed(() =>
    orderBy(
      map(toValue(resourceFields), f => {
        f.disabled = isFieldAlreadySelected(f)

        return f
      }),
      ['disabled', 'order'],
      ['desc', 'asc']
    )
  )

  function generateRequestAttribute() {
    return randomString(25)
  }

  function handleFieldChanged(f) {
    if (f) {
      fieldLabel.value = f.label
      isRequired.value = f.isRequired
    } else {
      fieldLabel.value = ''
      isRequired.value = false
    }
  }

  function isFieldAlreadySelected(f) {
    return !find(toValue(selectedSections), {
      attribute: f.attribute,
      resourceName: toValue(resourceName),
    })
  }

  return {
    field,
    fieldLabel,
    isRequired,

    fieldMustBeRequired,
    availableFields,
    generateRequestAttribute,
    handleFieldChanged,
    isFieldAlreadySelected,
  }
}
