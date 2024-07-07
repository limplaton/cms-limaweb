
import { createStore } from 'vuex'
import findIndex from 'lodash/findIndex'

export default createStore({
  state: {
    sidebarMenuItems: [],
    sidebarOpen: false,
    settings: {},
  },
  mutations: {
    /**
     * Set the application settings in store
     *
     * @param {Object} state
     * @param {Object} settings
     */
    SET_SETTINGS(state, settings) {
      Object.keys(settings).forEach(settingKey => {
        state.settings[settingKey] = settings[settingKey]
      })
    },

    /**
     * Toggle the sidebar visibility
     */
    SET_SIDEBAR_OPEN(state, value) {
      state.sidebarOpen = value
    },

    /**
     * Set available sidebar menu items.
     */
    SET_SIDEBAR_MENU_ITEMS(state, items) {
      state.sidebarMenuItems = items
    },

    /**
     * Update sidebar menu item.
     */
    UPDATE_SIDEBAR_MENU_ITEM(state, data) {
      const index = findIndex(state.sidebarMenuItems, ['id', data.id])

      state.sidebarMenuItems[index] = Object.assign(
        {},
        state.sidebarMenuItems[index],
        data.data
      )
    },
  },
  getters: {
    /**
     * Get a sidebar menu item by given id.
     */
    getSidebarMenuItem: state => id => {
      return state.sidebarMenuItems[
        findIndex(state.sidebarMenuItems, ['id', id])
      ]
    },
  },
  modules: {},
  strict: process.env.NODE_ENV !== 'production',
})
