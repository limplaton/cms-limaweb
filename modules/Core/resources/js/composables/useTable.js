
import { computed, toValue } from 'vue'
import { useStore } from 'vuex'
import omit from 'lodash/omit'

import { emitGlobal } from './useGlobalEventListener'

/**
 * Composable for table operations.
 *
 * @param {string | number} tableId - The ID of the table.
 */
export function useTable(tableId) {
  /** @type {import('vuex').Store} */
  const store = useStore()

  /**
   * Indicates if the table being customized.
   *
   * @type {import('vue').ComputedRef<boolean>}
   */
  const tableBeingCustomized = computed(() =>
    Boolean(store.state.table.customize[toValue(tableId)])
  )

  /**
   * Get the table settings.
   *
   * @type {import('vue').ComputedRef<Object>}
   */
  const settings = computed({
    set(newValue) {
      store.commit('table/SET_SETTINGS', {
        id: toValue(tableId),
        settings: newValue,
      })
    },
    get() {
      return store.getters['table/settings'](toValue(tableId))
    },
  })

  /**
   * Reloads the table.
   */
  function reloadTable() {
    emitGlobal('reload-resource-table', toValue(tableId))
  }

  /**
   * Customizes the table visibility.
   *
   * @param {boolean} [value=true] - The visibility value.
   */
  function customizeTable(value = true) {
    store.commit('table/SET_CUSTOMIZE_VISIBILTY', {
      id: toValue(tableId),
      value: value !== false,
    })
  }

  /**
   * Fetch the table actions and set them in the store.
   *
   * @param {string} resourceName
   * @param {Object} config
   * @returns Promise
   */
  async function fetchSettings(resourceName, config) {
    let settingsRetrieved = Object.keys(settings.value).length > 0

    if (settingsRetrieved) {
      return
    }

    let { data } = await Innoclapps.request(
      `/${resourceName}/table/settings`,
      config
    )

    settings.value = omit(data, ['filters', 'rules'])

    store.dispatch('filters/setFiltersAndRules', {
      identifier: data.identifier,
      filters: data.filters,
      rules: data.rules,
    })
  }

  /**
   * Fetch the table actions and set them in the store.
   *
   * @param {string} resourceName
   * @param {Object} config
   * @returns Promise
   */
  async function fetchActions(resourceName, config) {
    let { data } = await Innoclapps.request(
      `/${resourceName}/table/settings`,
      config
    )

    settings.value = { ...settings.value, ...{ actions: data.actions } }
  }

  return {
    reloadTable,
    customizeTable,
    tableBeingCustomized,
    settings,
    fetchSettings,
    fetchActions,
  }
}
