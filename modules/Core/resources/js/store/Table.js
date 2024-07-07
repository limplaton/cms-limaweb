
import { shallowReactive, shallowRef, toValue } from 'vue'

const state = {
  settings: shallowReactive({}),
  customize: {},
}

const mutations = {
  /**
   * Set the table customize visibility
   *
   * @param {Object} state
   * @param {Object} data
   */
  SET_CUSTOMIZE_VISIBILTY(state, data) {
    state.customize[data.id] = data.value
  },

  /**
   * Set the table settings in store.
   *
   * @param {Object} state
   * @param {Object} data
   */
  SET_SETTINGS(state, data) {
    state.settings[data.id] = shallowRef(data.settings)
  },

  /**
   * Reset all tables settings.
   */
  RESET_SETTINGS(state) {
    for (let i in state.settings) {
      state.settings[i] = {}
    }
  },
}

const getters = {
  /**
   * Get table settings function.
   *
   * @returns {Function}
   */
  settings: () => id => {
    return toValue(state.settings[id]) || {}
  },
}

export default {
  namespaced: true,
  state,
  mutations,
  getters,
}
