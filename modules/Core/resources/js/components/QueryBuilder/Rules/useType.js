
import { unref, watch } from 'vue'
import { useStore } from 'vuex'

import { needsArray } from '../utils'

export function useType(query, operator, isNullable) {
  const store = useStore()

  watch(
    () => unref(query).operand,
    (newVal, oldVal) => {
      // reset the value when the operand changes as the operands
      // may be changed multiple times after the rule is added and the value may not match the newly selected operand
      // e.q. prevously was operand of type date and after change is text
      if (oldVal) {
        updateValue(needsArray(unref(operator)) ? [] : '')
      }
    }
  )

  watch(operator, (newVal, oldVal) => {
    const nowNeedsArray = needsArray(newVal)

    // If now needs array and the current value is not array
    // set the current value to empty array
    if (nowNeedsArray && !Array.isArray(unref(query).value)) {
      updateValue([])
    }

    // 1. If previous operator needed array and now don't need array just reset the value,
    // 2. If oldVal is "is" or "was" then set the value to empty as if it's date, will throw error for invalid date
    // 3. When selecting nullable operator, set the value only to empty
    if (
      (needsArray(oldVal) || oldVal == 'is' || oldVal == 'was' || isNullable) &&
      !nowNeedsArray
    ) {
      updateValue('')
    }
  })

  function updateValue(value) {
    store.commit('queryBuilder/UPDATE_QUERY_VALUE', {
      query: unref(query),
      value: value,
    })
  }

  return { updateValue }
}
