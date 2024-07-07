
import { onBeforeUnmount, ref } from 'vue'
import find from 'lodash/find'
import map from 'lodash/map'

import { CancelToken } from '@/Core/services/HTTP'

export function useCards() {
  const cards = ref([])

  let cancelTokenHandler = null

  async function fetchCards() {
    const { data } = await Innoclapps.request('/cards', {
      cancelToken: new CancelToken(token => (cancelTokenHandler = token)),
    })

    cards.value = data
  }

  function applyUserConfig(cards, dashboard) {
    return map(cards, (card, index) => {
      let config = find(dashboard.cards, ['key', card.uriKey])

      card.order = config
        ? Object.hasOwn(config, 'order')
          ? config.order
          : index + 1
        : index + 1

      card.enabled =
        !config || config.enabled || typeof config.enabled == 'undefined'
          ? true
          : false

      return card
    })
  }

  onBeforeUnmount(() => {
    cancelTokenHandler && cancelTokenHandler()
  })

  return { cards, fetchCards, applyUserConfig }
}
