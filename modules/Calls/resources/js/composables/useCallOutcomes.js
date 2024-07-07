
import { computed, ref, watchEffect } from 'vue'
import orderBy from 'lodash/orderBy'

import { useApp } from '@/Core/composables/useApp'
import { useLoader } from '@/Core/composables/useLoader'

const callOutcomes = ref([])

export const useCallOutcomes = () => {
  const { setLoading, isLoading: outcomesAreBeingFetched } = useLoader()
  const { scriptConfig } = useApp()

  callOutcomes.value = [...(scriptConfig('calls.outcomes') || [])]

  watchEffect(() => {
    scriptConfig('calls.outcomes', [...callOutcomes.value])
  })

  const outcomesByName = computed(() => orderBy(callOutcomes.value, 'name'))

  function setCallOutcomes(outcomes) {
    callOutcomes.value = outcomes
  }

  function fetchCallOutcomes(config = {}) {
    setLoading(true)

    Innoclapps.request(
      '/call-outcomes',
      Object.assign(
        {},
        {
          params: {
            per_page: 100,
          },
        },
        config
      )
    )
      .then(({ data }) => (callOutcomes.value = data.data))
      .finally(() => setLoading(false))
  }

  return {
    callOutcomes,
    outcomesByName,
    outcomesAreBeingFetched,

    setCallOutcomes,
    fetchCallOutcomes,
  }
}
