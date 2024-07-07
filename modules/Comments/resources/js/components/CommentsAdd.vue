<template>
  <ILink
    v-show="!commengIsBeingCreated"
    v-bind="$attrs"
    class="inline-flex items-center"
    @click="commengIsBeingCreated = true"
  >
    <Icon icon="PlusSolid" class="mr-1.5 size-4" />
    {{ $t('comments::comment.add') }}
  </ILink>

  <CommentsCreate
    v-if="commengIsBeingCreated"
    :commentable-type="commentableType"
    :commentable-id="commentableId"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    @created="handleCommentCreated"
    @cancelled="commengIsBeingCreated = false"
  />
</template>

<script setup>
import { onUnmounted } from 'vue'

import { useComments } from '../composables/useComments'

import CommentsCreate from './CommentsCreate.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  commentableType: { required: true, type: String },
  commentableId: { required: true, type: Number },
  viaResource: String,
  viaResourceId: [String, Number],
})

const emit = defineEmits(['created'])

const { commengIsBeingCreated } = useComments(
  props.commentableId,
  props.commentableType
)

function handleCommentCreated(comment) {
  emit('created', comment)
}

onUnmounted(() => {
  commengIsBeingCreated.value = false
})
</script>
