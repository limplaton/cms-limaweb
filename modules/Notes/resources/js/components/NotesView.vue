<template>
  <ICard v-show="!noteBeingEdited" v-bind="$attrs" :class="'note-' + noteId">
    <ICardHeader>
      <div class="flex flex-1">
        <div class="mr-1 shrink-0 self-start">
          <IAvatar :src="user.avatar_url" />
        </div>

        <ITextBlockDark class="ml-1 mt-0.5">
          <I18nT scope="global" keypath="notes::note.info_created">
            <template #user>
              <span class="font-medium" v-text="user.name" />
            </template>

            <template #date>
              <span class="font-medium" v-text="localizedDateTime(createdAt)" />
            </template>
          </I18nT>
        </ITextBlockDark>
      </div>

      <ICardActions>
        <IDropdownMinimal
          v-if="authorizations.update && authorizations.delete"
          small
        >
          <IDropdownItem
            v-show="authorizations.update"
            :text="$t('core::app.edit')"
            @click="toggleEdit"
          />

          <IDropdownItem
            v-show="authorizations.delete"
            :text="$t('core::app.delete')"
            @click="$confirm(() => destroy(noteId))"
          />
        </IDropdownMinimal>
      </ICardActions>
    </ICardHeader>

    <ICardBody>
      <TextCollapse
        v-if="collapsable"
        class="wysiwyg-text"
        :text="body"
        :length="250"
        lightbox
        @dblclick="toggleEdit"
      />

      <HtmlableLightbox
        v-else
        class="wysiwyg-text"
        :html="body"
        @dblclick="toggleEdit"
      />

      <CollapsableCommentsList
        v-slot="{
          hasComments,
          totalComments,
          commentsAreBeingLoaded,
          toggleCommentsVisibility,
        }"
        class="mt-3"
        commentable-type="notes"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        :commentable-id="noteId"
        :count="commentsCount"
        :comments="comments"
        @updated="
          synchronizeResource({
            notes: { id: noteId, comments: $event },
          })
        "
        @deleted="
          synchronizeResource({
            notes: { id: noteId, comments: { id: $event, _delete: true } },
          })
        "
        @update:comments="
          synchronizeResource({
            notes: { id: noteId, comments: $event },
          })
        "
        @update:count="
          synchronizeResource({
            notes: { id: noteId, comments_count: $event },
          })
        "
      >
        <CollapseableCommentsLink
          v-if="hasComments"
          class="mt-6"
          :loading="commentsAreBeingLoaded"
          :total="totalComments"
          :collapsed="commentsAreVisible"
          @click="toggleCommentsVisibility"
        />
      </CollapsableCommentsList>
    </ICardBody>

    <ICardFooter class="text-right">
      <CommentsAdd
        class="self-end"
        commentable-type="notes"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        :commentable-id="noteId"
        @created="
          (commentsAreVisible = true),
            synchronizeResource({
              notes: {
                id: noteId,
                comments: [$event],
              },
            })
        "
      />
    </ICardFooter>
  </ICard>

  <NotesEdit
    v-if="noteBeingEdited"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :note-id="noteId"
    :body="body"
    @cancelled="noteBeingEdited = false"
    @updated="noteBeingEdited = false"
  />
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import HtmlableLightbox from '@/Core/components/Lightbox/HtmlableLightbox.vue'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useResourceable } from '@/Core/composables/useResourceable'

import { useComments } from '@/Comments/composables/useComments'

import NotesEdit from './NotesEdit.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  noteId: { required: true, type: Number },
  commentsCount: { required: true, type: Number },
  createdAt: { required: true, type: String },
  body: { required: true, type: String },
  userId: { required: true, type: Number },
  authorizations: { required: true, type: Object },
  comments: { required: true, type: Array },
  viaResource: { required: true, type: String },
  viaResourceId: { required: true, type: [String, Number] },
  collapsable: Boolean,
})

const resourceName = Innoclapps.resourceName('notes')

const synchronizeResource = inject('synchronizeResource')
const decrementResourceCount = inject('decrementResourceCount')

const { t } = useI18n()
const { localizedDateTime } = useDates()
const { findUserById } = useApp()
const { deleteResource } = useResourceable(resourceName)
const { commentsAreVisible } = useComments(props.noteId, 'notes')

const user = computed(() => findUserById(props.userId))

const noteBeingEdited = ref(false)

async function destroy(id) {
  await deleteResource(id)

  synchronizeResource({ notes: { id, _delete: true } })
  decrementResourceCount('notes_count')

  Innoclapps.success(t('notes::note.deleted'))
}

function toggleEdit(e) {
  // The double click to edit should not work while in edit mode
  if (e.type == 'dblclick' && noteBeingEdited.value) return
  // For double click event
  if (!props.authorizations.update) return

  noteBeingEdited.value = !noteBeingEdited.value
}
</script>
