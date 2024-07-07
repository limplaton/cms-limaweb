
import { computed, toValue } from 'vue'
import { useStore } from 'vuex'

import { useApp } from '@/Core/composables/useApp'

export function useFilterable(identifier, view) {
  const store = useStore()
  const { currentUser } = useApp()

  const viewName = toValue(view || identifier)

  /**
   * Get the currently query builder active filter
   */
  const activeFilter = computed({
    set(newValue) {
      if (newValue === null) {
        store.dispatch('filters/clearActive', {
          identifier: toValue(identifier),
          view: viewName,
        })
      } else {
        store.commit('filters/SET_ACTIVE', {
          identifier: toValue(identifier),
          view: viewName,
          id: newValue,
        })
      }
    },
    get() {
      return store.getters['filters/getActive'](toValue(identifier), viewName)
    },
  })

  /**
   * Get the identifier available saved filters ordered by name
   */
  const filters = computed({
    set(newValue) {
      store.commit('filters/SET', {
        identifier: toValue(identifier),
        filters: newValue,
      })
    },
    get() {
      return store.getters['filters/getAll'](toValue(identifier))
    },
  })

  /**
   * Get the available rules for the identifier
   */
  const availableRules = computed({
    set(newValue) {
      store.commit('filters/SET_AVAILABLE_RULES', {
        identifier: toValue(identifier),
        rules: newValue,
      })
    },
    get() {
      return store.state.filters.availableRules[toValue(identifier)]
    },
  })

  /**
   * Indicates whether the resource has available rules/filters
   */
  const hasRules = computed(() =>
    !availableRules.value ? false : availableRules.value.length > 0
  )

  /**
   * Indicates whether the filters rules are visible
   */
  const filtersBuilderVisible = computed({
    set(newValue) {
      store.commit('filters/SET_BUILDER_VISIBLE', {
        identifier: toValue(identifier),
        view: viewName,
        visible: newValue,
      })
    },
    get() {
      return store.getters['filters/filtersBuilderVisible'](
        toValue(identifier),
        viewName
      )
    },
  })

  /**
   * Toggle the filters builder visibility
   */
  function toggleFiltersBuilderVisibility() {
    filtersBuilderVisible.value = !filtersBuilderVisible.value
  }

  /**
   * Current user default filter
   */
  const currentUserDefaultFilter = computed(() =>
    getDefault(currentUser.value.id)
  )

  /**
   * Get the default filter for the given user
   */
  function getDefault(userId) {
    return store.getters['filters/getDefault'](
      toValue(identifier),
      viewName,
      userId
    )
  }

  /**
   * Find filter by given ID
   */
  function findFilter(id) {
    return store.getters['filters/getById'](toValue(identifier), id)
  }

  /**
   * Delete filter by given ID
   */
  async function deleteFilter(id) {
    store.dispatch('filters/destroy', {
      identifier: toValue(identifier),
      view: viewName,
      id: id,
    })
  }

  return {
    filters,
    availableRules,
    filtersBuilderVisible,
    toggleFiltersBuilderVisibility,
    activeFilter,
    hasRules,
    currentUserDefaultFilter,
    findFilter,
    deleteFilter,
  }
}
