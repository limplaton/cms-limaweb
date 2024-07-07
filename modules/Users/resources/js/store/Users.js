
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'

function currentUserIndex(state) {
  return findIndex(state.collection, [
    'id',
    parseInt(Innoclapps.scriptConfig('user_id')),
  ])
}

const state = {
  collection: [],
  endpoint: '/users',
}

const mutations = {
  /**
   * Set the available users.
   *
   * @param {object} state
   * @param {Array} collection
   */
  SET(state, collection) {
    state.collection = collection
  },

  /**
   * Add user to the available users collection.
   *
   * @param {object} state
   * @param {object} item
   */
  ADD(state, item) {
    state.collection.push(item)
  },

  /**
   * Update user in store.
   *
   * @param {object} state
   * @param {object} data
   */
  UPDATE(state, data) {
    const index = findIndex(state.collection, ['id', parseInt(data.id)])

    if (index !== -1) {
      state.collection[index] = data.item
    }
  },

  /**
   * Remove user from store.
   *
   * @param {object} state
   * @param {number} id
   */
  REMOVE(state, id) {
    const index = findIndex(state.collection, ['id', parseInt(id)])

    if (index != -1) {
      state.collection.splice(index, 1)
    }
  },

  /**
   * Set the unread count notifications for the current user.
   */
  SET_TOTAL_UNREAD_NOTIFICATIONS(state, total) {
    const index = currentUserIndex(state)

    state.collection[index].notifications.unread_count = total
  },

  /**
   * Increment the unread count notifications for the current user.
   */
  INCREMENT_TOTAL_UNREAD_NOTIFICATIONS(state) {
    const index = currentUserIndex(state)

    state.collection[index].notifications.unread_count =
      state.collection[index].notifications.unread_count + 1
  },

  /**
   * Decrement the unread count notifications for the current user.
   */
  DECREMENT_TOTAL_UNREAD_NOTIFICATIONS(state) {
    const index = currentUserIndex(state)

    state.collection[index].notifications.unread_count =
      state.collection[index].notifications.unread_count - 1
  },

  /**
   * Add current user dashboard.
   */
  ADD_DASHBOARD(state, dashboard) {
    const index = currentUserIndex(state)

    state.collection[index].dashboards.push(dashboard)

    if (dashboard.is_default) {
      // Update previous is_default to false
      state.collection[index].dashboards.forEach((d, index) => {
        if (d.id != dashboard.id) {
          state.collection[index].dashboards[index].is_default = false
        }
      })
    }
  },

  /**
   * Update current user dashboard.
   */
  UPDATE_DASHBOARD(state, dashboard) {
    const index = currentUserIndex(state)

    const dashboardIndex = findIndex(state.collection[index].dashboards, [
      'id',
      parseInt(dashboard.id),
    ])

    state.collection[index].dashboards[dashboardIndex] = Object.assign(
      {},
      state.collection[index].dashboards[dashboardIndex],
      dashboard
    )

    if (dashboard.is_default) {
      // Update previous is_default to false
      state.collection[index].dashboards.forEach((d, didx) => {
        if (d.id != dashboard.id) {
          state.collection[index].dashboards[didx].is_default = false
        }
      })
    }
  },

  /**
   * Add current user dashboard.
   */
  REMOVE_DASHBOARD(state, id) {
    const index = currentUserIndex(state)

    const dashboardIndex = findIndex(state.collection[index].dashboards, [
      'id',
      parseInt(id),
    ])

    state.collection[index].dashboards.splice(dashboardIndex, 1)
  },
}

const getters = {
  /**
   * Get user by given ID.
   */
  getById: state => id => {
    return find(state.collection, ['id', parseInt(id)])
  },

  /**
   * Get the current user.
   */
  current(state) {
    return state.collection[currentUserIndex(state)]
  },
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
}
