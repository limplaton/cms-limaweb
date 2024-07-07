<template>
  <div ref="commentsRef">
    <IText
      v-if="!hasComments"
      class="text-center"
      :text="$t('comments::comment.no_comments')"
    />

    <CommentsListItem
      v-for="comment in comments"
      :key="comment.id"
      class="mb-3"
      :commentable-type="commentableType"
      :commentable-id="commentableId"
      :via-resource="viaResource"
      :via-resource-id="viaResourceId"
      :comment-id="comment.id"
      :highlighted="comment.id === highlightedCommentId"
      :body="comment.body"
      :created-by="comment.created_by"
      :created-at="comment.created_at"
      :authorizations="comment.authorizations"
      @deleted="$emit('deleted', $event)"
      @updated="$emit('updated', $event)"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useTimeoutFn } from '@vueuse/core'

import CommentsListItem from './CommentsListItem.vue'

const props = defineProps({
  autoFocusIfRequired: Boolean,
  comments: Array,
  commentableType: { required: true, type: String },
  commentableId: { required: true, type: Number },
  viaResource: String,
  viaResourceId: [String, Number],
})

defineEmits(['deleted', 'updated'])

const route = useRoute()

const commentsRef = ref(null)
const highlightedCommentId = ref(null)

const hasComments = computed(() => props.comments.length > 0)

function focusIfRequired() {
  if (!route.query.comment_id || !props.autoFocusIfRequired) {
    return
  }

  nextTick(() => {
    const $comment = commentsRef.value.querySelector(
      '#comment-' + route.query.comment_id
    )

    if ($comment) {
      $comment.scrollIntoView({
        behavior: 'auto',
        block: 'center',
        inline: 'nearest',
      })

      highlightedCommentId.value = parseInt(route.query.comment_id)

      useTimeoutFn(() => (highlightedCommentId.value = null), 10000)
    }
  })
}

onMounted(focusIfRequired)
</script>
