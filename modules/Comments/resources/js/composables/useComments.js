
import { computed, toValue } from 'vue'
import { useStore } from 'vuex'

// resourceId - resourceName
export function useComments(commentableId, commentableType) {
  const store = useStore()

  const requestInProgress = computed(
    () => store.state.comments.requestInProgress
  )

  const commentsAreLoaded = computed({
    set(newValue) {
      store.commit('comments/SET_LOADED', {
        commentableId: toValue(commentableId),
        commentableType: commentableType,
        value: newValue,
      })
    },
    get() {
      return store.getters['comments/areLoadedFor'](
        toValue(commentableId),
        commentableType
      )
    },
  })

  const commentsAreVisible = computed({
    set(newValue) {
      store.commit('comments/SET_VISIBILITY', {
        commentableId: toValue(commentableId),
        commentableType: commentableType,
        visible: newValue,
      })
    },
    get() {
      return store.getters['comments/areVisibleFor'](
        toValue(commentableId),
        commentableType
      )
    },
  })

  const commengIsBeingCreated = computed({
    set(newValue) {
      store.commit('comments/SET_ADD_COMMENT_VISIBILITY', {
        commentableId: toValue(commentableId),
        commentableType: commentableType,
        value: newValue,
      })
    },
    get() {
      return store.getters['comments/isCommentBeingCreatedFor'](
        toValue(commentableId),
        commentableType
      )
    },
  })

  function toggleCommentsVisibility() {
    commentsAreVisible.value = !commentsAreVisible.value
  }

  function getAllComments(viaResource, viaResourceId) {
    return store.dispatch('comments/getAll', {
      resourceName: commentableType,
      resourceId: toValue(commentableId),
      viaResource: viaResource,
      viaResourceId: viaResourceId,
    })
  }

  return {
    requestInProgress,
    commengIsBeingCreated,
    commentsAreLoaded,
    commentsAreVisible,
    getAllComments,
    toggleCommentsVisibility,
  }
}
