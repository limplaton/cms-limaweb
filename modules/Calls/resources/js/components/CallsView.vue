<template>
  <ICard v-show="!callBeingEdited" v-bind="$attrs" :class="'call-' + callId">
    <ICardHeader>
      <div class="flex flex-1">
        <div class="mr-1 mt-1 shrink-0">
          <IAvatar :src="user.avatar_url" />
        </div>

        <div
          class="flex grow flex-col lg:flex-row lg:items-center lg:space-x-2"
        >
          <ITextBlockDark class="ml-1 mt-1.5 grow lg:mt-0.5">
            <I18nT scope="global" keypath="calls::call.info_created">
              <template #user>
                <span class="font-medium" v-text="user.name" />
              </template>

              <template #date>
                <span
                  class="font-medium"
                  v-text="localizedDateTime(callDate)"
                />
              </template>
            </I18nT>
          </ITextBlockDark>

          <div class="ml-1 mt-1 space-x-2 lg:ml-0 lg:mt-0 lg:space-x-0">
            <ITextDark
              class="inline font-medium lg:hidden"
              :text="$t('calls::call.outcome.outcome')"
            />

            <IDropdown>
              <IDropdownButton
                :as="IBadgeButton"
                :disabled="!authorizations.update"
                :color="outcome.swatch_color"
              >
                <span class="truncate lg:max-w-36" v-text="outcome.name" />
              </IDropdownButton>

              <IDropdownMenu>
                <IDropdownItem
                  v-for="callOutcome in outcomesByName"
                  :key="callOutcome.id"
                  :text="callOutcome.name"
                  :active="outcomeId === callOutcome.id"
                  @click="update({ call_outcome_id: callOutcome.id })"
                />
              </IDropdownMenu>
            </IDropdown>
          </div>
        </div>
      </div>

      <ICardActions class="mt-1 self-start lg:self-auto">
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
            @click="$confirm(() => destroy(callId))"
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
        commentable-type="calls"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        :commentable-id="callId"
        :count="commentsCount"
        :comments="comments"
        @updated="
          synchronizeResource({
            calls: { id: callId, comments: $event },
          })
        "
        @deleted="
          synchronizeResource({
            calls: { id: callId, comments: { id: $event, _delete: true } },
          })
        "
        @update:comments="
          synchronizeResource({
            calls: { id: callId, comments: $event },
          })
        "
        @update:count="
          synchronizeResource({
            calls: { id: callId, comments_count: $event },
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
        commentable-type="calls"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        :commentable-id="callId"
        @created="
          (commentsAreVisible = true),
            synchronizeResource({
              calls: {
                id: callId,
                comments: [$event],
              },
            })
        "
      />
    </ICardFooter>
  </ICard>

  <CallsEdit
    v-if="callBeingEdited"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :call-id="callId"
    @cancelled="callBeingEdited = false"
    @updated="callBeingEdited = false"
  />
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import HtmlableLightbox from '@/Core/components/Lightbox/HtmlableLightbox.vue'
import { IBadgeButton } from '@/Core/components/UI/Badge'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useResourceable } from '@/Core/composables/useResourceable'

import { useComments } from '@/Comments/composables/useComments'

import { useCallOutcomes } from '../composables/useCallOutcomes'

import CallsEdit from './CallsEdit.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  callId: { required: true, type: Number },
  commentsCount: { required: true, type: Number },
  callDate: { required: true, type: String },
  body: { required: true, type: String },
  userId: { required: true, type: Number },
  outcomeId: { required: true, type: Number },
  viaResource: { required: true, type: String },
  viaResourceId: { required: true, type: [String, Number] },
  authorizations: { required: true, type: Object },
  comments: { required: true, type: Array },
  collapsable: Boolean,
})

const synchronizeResource = inject('synchronizeResource')
const decrementResourceCount = inject('decrementResourceCount')

const { t } = useI18n()
const { localizedDateTime } = useDates()
const { outcomesByName } = useCallOutcomes()
const { findUserById } = useApp()

const { updateResource, deleteResource } = useResourceable(
  Innoclapps.resourceName('calls')
)

const outcome = computed(() =>
  outcomesByName.value.find(o => o.id == props.outcomeId)
)

const user = computed(() => findUserById(props.userId))

const { commentsAreVisible } = useComments(props.callId, 'calls')

const callBeingEdited = ref(false)

async function update(payload = {}) {
  let call = await updateResource(
    {
      call_outcome_id: props.outcomeId,
      date: props.callDate,
      body: props.body,
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
      ...payload,
    },
    props.callId
  )

  synchronizeResource({ calls: call })
}

async function destroy(id) {
  await deleteResource(id)

  synchronizeResource({ calls: { id, _delete: true } })
  decrementResourceCount('calls_count')

  Innoclapps.success(t('calls::call.deleted'))
}

function toggleEdit(e) {
  // The double click to edit should not work while in edit mode
  if (e.type == 'dblclick' && callBeingEdited.value) return
  // For double click event
  if (!props.authorizations.update) return

  callBeingEdited.value = !callBeingEdited.value
}
</script>
