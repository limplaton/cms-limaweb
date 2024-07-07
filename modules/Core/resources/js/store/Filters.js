
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'
import sortBy from 'lodash/sortBy'

const state = {
  // The available saved filters for resource
  filters: {},
  // The current available rules for resource
  availableRules: {},
  // Visible filters builders by resource
  visibleBuilders: {},
  // Active filters by resource
  activeFilters: {},
}

const mutations = {
  /**
   * Set the saved filters in store
   */
  SET(state, data) {
    state.filters[data.identifier] = data.filters
  },

  /**
   * Set the available rules for identigier
   */
  SET_AVAILABLE_RULES(state, data) {
    state.availableRules[data.identifier] = data.rules
  },

  /**
   * Update the filter in store
   */
  UPDATE(state, data) {
    let index = findIndex(state.filters[data.identifier], [
      'id',
      parseInt(data.filter.id),
    ])

    if (index !== -1) {
      state.filters[data.identifier][index] = data.filter
    }
  },

  /**
   * Add new saved filter in store
   */
  PUSH(state, data) {
    state.filters[data.identifier].push(data.filter)
  },

  /**
   * Remove filter from store
   */
  REMOVE(state, data) {
    let index = findIndex(state.filters[data.identifier], [
      'id',
      parseInt(data.id),
    ])

    if (index !== -1) {
      state.filters[data.identifier].splice(index, 1)
    }
  },

  /**
   * Set filter as active
   */
  SET_ACTIVE(state, data) {
    if (!state.activeFilters[data.identifier]) {
      state.activeFilters[data.identifier] = {}
    }

    state.activeFilters[data.identifier][data.view] = data.id
  },

  /**
   * Clear active filter
   */
  CLEAR_ACTIVE(state, data) {
    delete state.activeFilters[data.identifier][data.view]
  },

  /**
   * Unmark the given filter as default
   */
  UNMARK_AS_DEFAULT(state, data) {
    let index = findIndex(state.filters[data.identifier], [
      'id',
      parseInt(data.id),
    ])

    if (index !== -1) {
      let defaultViewIndex = findIndex(
        state.filters[data.identifier][index].defaults,
        { view: data.view, user_id: data.userId }
      )

      if (defaultViewIndex !== -1) {
        state.filters[data.identifier][index].defaults.splice(
          defaultViewIndex,
          1
        )
      }
    }
  },

  /**
   * Set filters builder visible indicator for resource
   */
  SET_BUILDER_VISIBLE(state, data) {
    if (!state.visibleBuilders[data.identifier]) {
      state.visibleBuilders[data.identifier] = {}
    }
    state.visibleBuilders[data.identifier][data.view] = data.visible
  },
}

const getters = {
  /**
   * Get all resource saved filters
   */
  getAll: state => identifier => {
    return sortBy(
      state.filters[identifier],
      ['is_system_default', 'name'],
      'desc',
      'asc'
    )
  },

  /**
   * Get resource saved filter by id
   */
  getById: state => (identifier, id) => {
    return find(state.filters[identifier], ['id', parseInt(id)])
  },

  /**
   * Get resource default filter
   */
  getDefault: state => (identifier, view, userId) => {
    return find(state.filters[identifier], filter => {
      return find(filter.defaults, { view: view, user_id: userId })
    })
  },

  /**
   * Get resource active filter
   */
  getActive: state => (identifier, view) => {
    if (
      !state.activeFilters[identifier] ||
      !state.activeFilters[identifier][view]
    ) {
      return null
    }

    return find(state.filters[identifier], [
      'id',
      parseInt(state.activeFilters[identifier][view]),
    ])
  },

  /**
   * Check whether the filters builder is visible for the given identifier and view
   */
  filtersBuilderVisible: state => (identifier, view) => {
    if (
      !state.visibleBuilders[identifier] ||
      !state.visibleBuilders[identifier][view]
    ) {
      return false
    }

    return state.visibleBuilders[identifier][view] || false
  },
}

const actions = {
  /**
   * Set the available saved filters and the available rules
   */
  setFiltersAndRules({ commit }, data) {
    commit('SET', {
      identifier: data.identifier,
      filters: data.filters,
    })

    commit('SET_AVAILABLE_RULES', {
      identifier: data.identifier,
      rules: data.rules,
    })
  },

  /**
   * Clear active filter
   */
  clearActive({ commit, getters }, data) {
    let filter = getters.getActive(data.identifier, data.view)

    if (filter) {
      commit('CLEAR_ACTIVE', {
        identifier: data.identifier,
        view: data.view,
      })

      commit(
        'queryBuilder/RESET_BUILDER_RULES',
        {
          identifier: data.identifier,
          view: data.view,
        },
        { root: true }
      )

      return true
    }

    return false
  },

  /**
   * Delete saved filter
   */
  async destroy({ commit, getters }, payload) {
    await Innoclapps.request().delete(`filters/${payload.id}`)

    let active = getters.getActive(payload.identifier, payload.view)

    if (active && parseInt(payload.id) === parseInt(active.id)) {
      commit('CLEAR_ACTIVE', {
        identifier: payload.identifier,
        view: payload.view,
      })
    }

    commit('REMOVE', {
      identifier: payload.identifier,
      id: payload.id,
    })
  },
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
}
