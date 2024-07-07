
import findIndex from 'lodash/findIndex'

const state = {
  collection: [],
  collectionSet: false,
  endpoint: '/mail/accounts',
}

const mutations = {
  /**
   * Set email accounts in store.
   */
  SET(state, collection) {
    state.collection = collection

    state.collectionSet = true
  },

  /**
   * Reset the email accounts in store.
   */
  RESET(state) {
    state.collection = []

    state.collectionSet = false
  },

  /**
   * Add email account in store.
   */
  ADD(state, item) {
    state.collection.push(item)
  },

  /**
   * Update email account in store.
   */
  UPDATE(state, data) {
    const index = findIndex(state.collection, ['id', parseInt(data.id)])

    if (index !== -1) {
      state.collection[index] = data.item
    }
  },

  /**
   * Remove email account from store.
   */
  REMOVE(state, id) {
    const index = findIndex(state.collection, ['id', parseInt(id)])

    if (index != -1) {
      state.collection.splice(index, 1)
    }
  },

  /**
   * Set the given account id as primary
   * The function unsets any previous primary accounts from the store
   * and updates the given account id to be as primary.
   *
   * @param {number} id|null When passing null, all accounts are marked as not primary.
   */
  SET_ACCOUNT_AS_PRIMARY(state, id) {
    // Update previous is_primary to false and set passed id as primary
    // this helps the getter "accounts" to properly perform the sorting
    state.collection.forEach((account, index) => {
      state.collection[index].is_primary = account.id == id
    })
  },
}

const actions = {
  /**
   * Fetch accounts from storage.
   */
  async fetch({ state, commit }, options = {}) {
    if (state.collectionSet) {
      return state.collection
    }

    let { data: accounts } = await Innoclapps.request(state.endpoint, options)

    commit('SET', accounts)

    return accounts
  },

  /**
   * Remove primary account.
   */
  removePrimary({ state, commit }) {
    Innoclapps.request()
      .delete(`${state.endpoint}/primary`)
      .then(() => {
        commit('SET_ACCOUNT_AS_PRIMARY', null)
      })
  },

  /**
   * Set the account is primary state.
   */
  setPrimary({ state, commit }, id) {
    Innoclapps.request()
      .put(`${state.endpoint}/${id}/primary`)
      .then(() => {
        commit('SET_ACCOUNT_AS_PRIMARY', id)
      })
  },

  /**
   * Enable account synchronization.
   */
  enableSync({ state, commit }, id) {
    Innoclapps.request()
      .post(`${state.endpoint}/${id}/sync/enable`)
      .then(({ data: account }) => {
        commit('UPDATE', {
          id: account.id,
          item: account,
        })
      })
  },

  /**
   * Disable account synchronization.
   */
  disableSync({ state, commit }, id) {
    Innoclapps.request()
      .post(`${state.endpoint}/${id}/sync/disable`)
      .then(({ data: account }) => {
        commit('UPDATE', {
          id: account.id,
          item: account,
        })
      })
  },

  /**
   * Delete a record.
   */
  async destroy(context, id) {
    let { data } = await Innoclapps.request().delete(`${state.endpoint}/${id}`)

    context.commit('REMOVE', id)
    context.dispatch('updateUnreadCountUI', data.unread_count)

    return data
  },

  /**
   * Update the total unread count UI.
   */
  updateUnreadCountUI(context, unreadCount) {
    context.commit(
      'UPDATE_SIDEBAR_MENU_ITEM',
      {
        id: 'inbox',
        data: {
          badge: unreadCount,
        },
      },
      { root: true }
    )
  },

  /**
   * Decrement total unread count updateUnreadCountUI.
   */
  decrementUnreadCountUI(context) {
    let item = context.rootGetters.getSidebarMenuItem('inbox')

    if (item.badge < 1) {
      return
    }

    context.dispatch('updateUnreadCountUI', item.badge - 1)
  },
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
}
