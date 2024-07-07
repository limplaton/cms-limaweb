
const state = {
  // Fields by groups for forms
  fields: {},
}

const mutations = {
  /**
   * Set the given fields and group in store
   *
   * @param {Object} state
   * @param {Object} data
   */
  SET(state, data) {
    state.fields[data.group] = data.fields
  },

  /**
   * Set the given fields and group in store
   *
   * @param {Object} state
   */
  RESET(state) {
    state.fields = {}
    state.placeholders = null
  },
}

const actions = {
  /**
   * Get fields for given group/resource and view
   *
   * @param  {Function} options.commit
   * @param  {Object} options.state
   * @param  {String} options.resourceName
   * @param  {String} options.view
   * @param  {Number} options.resourceId
   *
   * @return {Array}
   */
  async getForResource(
    { commit, state },
    {
      resourceName,
      view,
      resourceId,
      viaResource,
      viaResourceId,
      intent,
      params = {},
    }
  ) {
    let cacheKey =
      resourceName + '-' + view + (viaResource ? '-' + viaResource : '')

    // We don't cache the fields when resourceId/update fields are requested
    // Because the resource may implement different strategies based on the model
    // e.q. readonly if specific model condition is met
    if (
      state.fields[cacheKey] !== undefined &&
      !resourceId &&
      !params.resourceId
    ) {
      return state.fields[cacheKey]
    }

    let { data: fields } = await Innoclapps.request(
      `/${resourceName}${resourceId ? '/' + resourceId : ''}/${view}-fields`,
      {
        params: {
          intent: intent || view,
          via_resource: viaResource,
          via_resource_id: viaResourceId,
          ...params,
        },
      }
    )

    commit('SET', { group: cacheKey, fields: Object.freeze(fields) })

    return fields
  },
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
}
