<template>
  <slot
    :comments-are-visible="commentsAreVisible"
    :total-comments="countComputed"
    :has-comments="hasComments"
    :comments-are-being-loaded="requestInProgress"
    :toggle-comments-visibility="toggleCommentsVisibility"
  />

  <div v-show="commentsAreVisible && commentsAreLoaded" v-bind="$attrs">
    <CommentsList
      v-if="commentsAreLoaded"
      :comments="comments"
      :commentable-type="commentableType"
      :commentable-id="commentableId"
      :via-resource="viaResource"
      :via-resource-id="viaResourceId"
      :auto-focus-if-required="true"
      @updated="handleCommentUpdatedEvent"
      @deleted="handleCommentDeletedEvent"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, watch } from 'vue'
import { whenever } from '@vueuse/core'

import { useComments } from '../composables/useComments'

import CommentsList from './CommentsList.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  count: Number,
  listWrapperClass: [Array, Object, String],
  commentableType: { required: true, type: String },
  commentableId: { required: true, type: Number },
  comments: { required: true, type: Array },
  viaResource: String,
  viaResourceId: [String, Number], // must be provided when "viaResource" is provided
})

const emit = defineEmits([
  'update:count',
  'update:comments',
  'deleted',
  'updated',
])

const {
  requestInProgress,
  commentsAreVisible,
  commentsAreLoaded,
  toggleCommentsVisibility,
  getAllComments,
} = useComments(props.commentableId, props.commentableType)

const countComputed = computed(() => {
  if (!commentsAreLoaded.value) {
    return props.count || 0
  }

  return props.comments.length
})

const hasComments = computed(() => countComputed.value > 0)

/**
 * Emit the update count event when new comments are added/deleted
 * For in case any parent component interested to update it's data
 */
watch(countComputed, newVal => {
  emit('update:count', newVal)
})

watch(
  countComputed,
  newVal => {
    if (newVal === 0) {
      commentsAreVisible.value = false
    }
  },
  { flush: 'post' }
)

whenever(commentsAreVisible, () => {
  // When the comments visibility is toggled on after a comment is added
  // We don't need to make a request to load them all as we already know
  // that there were zero comments and new one was created
  if (
    props.count === 0 &&
    props.comments.length === 1 &&
    props.comments[0].was_recently_created === true
  ) {
    commentsAreLoaded.value = true

    return
  }

  if (!commentsAreLoaded.value) {
    // TODO, fires twice because of timeline and direct embed
    loadComments()
  }
})

async function loadComments() {
  let comments = await getAllComments(
    props.viaResource,
    props.viaResource ? props.viaResourceId : null
  )

  commentsAreLoaded.value = true

  emit('update:comments', comments)
}

async function handleCommentDeletedEvent(id) {
  await nextTick()

  if (!hasComments.value) {
    commentsAreVisible.value = false
  }

  emit('deleted', id)
}

function handleCommentUpdatedEvent(comment) {
  emit('updated', comment)
}

onBeforeUnmount(() => {
  commentsAreVisible.value = false
  commentsAreLoaded.value = false
})
</script>
