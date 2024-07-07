
import { computed, ref, watchEffect } from 'vue'
import orderBy from 'lodash/orderBy'

import { useApp } from '@/Core/composables/useApp'
import { useLoader } from '@/Core/composables/useLoader'

const lostReasons = ref([])

export const useLostReasons = () => {
  const { setLoading, isLoading: lostReasonsAreBeingFetched } = useLoader()
  const { scriptConfig } = useApp()

  lostReasons.value = [...(scriptConfig('deals.lost_reasons') || [])]

  watchEffect(() => {
    scriptConfig('deals.lost_reasons', [...lostReasons.value])
  })

  const lostReasonsByName = computed(() => orderBy(lostReasons.value, 'name'))

  function setLostReasons(list) {
    lostReasons.value = list
  }

  function fetchLostReasons(config = {}) {
    setLoading(true)

    Innoclapps.request(
      '/lost-reasons',
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
      .then(({ data }) => (lostReasons.value = data.data))
      .finally(() => setLoading(false))
  }

  return {
    lostReasons,
    lostReasonsByName,
    lostReasonsAreBeingFetched,

    setLostReasons,
    fetchLostReasons,
  }
}
