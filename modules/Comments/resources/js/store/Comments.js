
const state = {
  visible: {},
  loaded: {},
  commentBeingCreated: {},
  requestInProgress: false,
}

const mutations = {
  /**
   * Set whether the comments for the commentable are visible when using collapse
   *
   * @param {Object} state
   * @param {Object} data
   */
  SET_VISIBILITY(state, data) {
    state.visible[String(data.commentableId + data.commentableType)] =
      data.visible
  },

  /**
   * Set whether to show the add comment form
   *
   * @param {Object} state
   * @param {Object} data
   */
  SET_ADD_COMMENT_VISIBILITY(state, data) {
    state.commentBeingCreated = Object.assign({}, state.commentBeingCreated, {
      [String(data.commentableId + data.commentableType)]: data.value,
    })
  },

  /**
   * Set whether comments are loaded
   *
   * @param {Object} state
   * @param {Object} data
   */
  SET_LOADED(state, data) {
    state.loaded = Object.assign({}, state.loaded, {
      [String(data.commentableId + data.commentableType)]: data.value,
    })
  },

  /**
   * Update the request in progress state
   *
   * @param {Object} state
   * @param {Boolean} value
   */
  SET_REQUEST_IN_PROGRESS(state, value) {
    state.requestInProgress = value
  },
}

const getters = {
  /**
   * Check whether the comments are visible for the given commentable
   *
   * @param  {Object} state)
   *
   * @return {Boolean}
   */
  areVisibleFor: state => (commentableId, commentableType) => {
    return Boolean(state.visible[String(commentableId + commentableType)])
  },

  /**
   * Check whether a comment is being created for the given commentable
   *
   * @param  {Object} state)
   *
   * @return {Boolean}
   */
  isCommentBeingCreatedFor: state => (commentableId, commentableType) => {
    return Boolean(
      state.commentBeingCreated[String(commentableId + commentableType)]
    )
  },

  /**
   * Check whether the comments are loaded for the given commentable
   *
   * @param  {Object} state)
   *
   * @return {Boolean}
   */
  areLoadedFor: state => (commentableId, commentableType) => {
    return Boolean(state.loaded[String(commentableId + commentableType)])
  },
}

const actions = {
  async getAll(
    { commit },
    { resourceName, resourceId, viaResource, viaResourceId }
  ) {
    commit('SET_REQUEST_IN_PROGRESS', true)

    let { data } = await Innoclapps.request(
      `${resourceName}/${resourceId}/comments`,
      {
        params:
          viaResourceId && viaResourceId
            ? {
              via_resource: viaResource,
              via_resource_id: viaResourceId,
            }
            : {},
      }
    )

    commit('SET_REQUEST_IN_PROGRESS', false)

    return data
  },
}

export default {
  state,
  mutations,
  getters,
  actions,
  namespaced: true,
}
