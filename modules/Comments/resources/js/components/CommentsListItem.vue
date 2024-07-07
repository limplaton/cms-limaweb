<template>
  <div>
    <!-- The :id="'comment-'+commentId" is used to auto focus the comment in CommentsList.vue -->
    <div
      :id="'comment-' + commentId"
      :class="[
        'comment rounded-lg',
        highlighted
          ? 'bg-info-50 dark:bg-info-600/30'
          : 'bg-white dark:bg-neutral-800',
        {
          'border border-neutral-200 px-4 py-2.5 shadow-sm dark:border-neutral-500/30':
            !commentBeingEdited,
        },
      ]"
    >
      <div v-show="!commentBeingEdited" class="flex flex-wrap">
        <div v-once class="flex grow items-center">
          <IAvatar class="mr-1" :src="creator.avatar_url" />

          <ITextBlockDark>
            <I18nT
              scope="global"
              :keypath="'comments::comment.user_left_comment'"
            >
              <template #user>
                <b class="font-medium" v-text="creator.name"></b>
              </template>
            </I18nT>
          </ITextBlockDark>
        </div>

        <ITextBlock v-once class="mt-1">
          {{ localizedDateTime(createdAt) }}
        </ITextBlock>
      </div>

      <HtmlableLightbox
        v-show="!commentBeingEdited"
        v-memo="[commentBeingEdited]"
        class="wysiwyg-text mt-3"
        :html="body"
      />

      <CommentsEdit
        v-if="commentBeingEdited"
        class="mt-3"
        :comment-id="commentId"
        :body="body"
        :commentable-type="commentableType"
        :commentable-id="commentableId"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        @cancelled="commentBeingEdited = false"
        @updated="(commentBeingEdited = false), $emit('updated', $event)"
      />
    </div>

    <div class="flex justify-end space-x-2 py-2 text-base sm:text-sm">
      <ILink
        v-if="createdBy !== currentUser.id && !commentBeingEdited"
        :text="$t('comments::comment.reply')"
        @click="replyToComment"
      />

      <ILink
        v-show="authorizations.update && !commentBeingEdited"
        :text="$t('core::app.edit')"
        @click="commentBeingEdited = true"
      />

      <ILink
        v-show="authorizations.delete && !commentBeingEdited"
        variant="danger"
        :text="$t('core::app.delete')"
        @click="$confirm(() => destroy(commentId))"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useTimeoutFn } from '@vueuse/core'

import HtmlableLightbox from '@/Core/components/Lightbox/HtmlableLightbox.vue'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'

import { useComments } from '../composables/useComments'

import CommentsEdit from './CommentsEdit.vue'

const props = defineProps({
  commentId: { required: true, type: Number },
  body: { required: true, type: String },
  createdBy: { required: true, type: Number },
  createdAt: { required: true, type: String },
  authorizations: { required: true, type: Object },
  commentableType: { required: true, type: String },
  commentableId: { required: true, type: Number },
  highlighted: Boolean,
  viaResource: String,
  viaResourceId: [String, Number],
})

const emit = defineEmits(['deleted', 'updated'])

const { localizedDateTime } = useDates()
const { currentUser, findUserById } = useApp()

const creator = computed(() => findUserById(props.createdBy))

const { commengIsBeingCreated } = useComments(
  props.commentableId,
  props.commentableType
)

const commentBeingEdited = ref(false)

/**
 * Initialize a reply to the current comment
 */
function replyToComment() {
  commengIsBeingCreated.value = true

  nextTick(() => {
    const $addCommentWrapper = document.getElementById(
      'add-comment-' + props.commentableType + '-' + props.commentableId
    )

    $addCommentWrapper.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
      inline: 'nearest',
    })

    // Add timeout untill editor is initialized
    useTimeoutFn(() => {
      tinymce.activeEditor.setContent('')

      tinymce.activeEditor.Commands.insertMentionUser(
        creator.value.id,
        creator.value.name
      )
    }, 650)
  })
}

async function destroy(id) {
  await Innoclapps.request().delete(`/comments/${id}`)

  emit('deleted', props.commentId)
}
</script>
