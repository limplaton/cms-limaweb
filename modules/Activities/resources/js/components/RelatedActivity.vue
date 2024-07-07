<template>
  <ICard
    v-show="!activityBeingEdited"
    v-bind="$attrs"
    :class="'activity-' + activityId"
  >
    <ICardHeader>
      <div class="flex w-full flex-1">
        <div class="mr-2 mt-px flex shrink-0 self-start">
          <StateChange
            v-memo="[isCompleted]"
            class="ml-px md:mt-px"
            :activity-id="activityId"
            :is-completed="isCompleted"
            :disabled="!authorizations.update"
            @changed="handleActivityStateChanged"
          />
        </div>

        <div
          class="flex grow flex-col space-y-1 lg:flex-row lg:space-x-3 lg:space-y-0"
        >
          <div class="flex grow flex-col items-start">
            <ICardHeading
              :class="[
                'text-base/6 font-medium sm:text-sm/6',
                isCompleted ? 'line-through' : '',
              ]"
              :text="title"
            />

            <AssociationsPopover
              :associations-count="associationsCount"
              :initial-associateables="relatedResource"
              :resource-id="activityId"
              :resource-name="resourceName"
              :primary-record="relatedResource"
              :primary-resource-name="viaResource"
              @synced="synchronizeResource({ activities: $event })"
            />
          </div>

          <div class="space-x-2 lg:space-x-0">
            <ITextDark
              v-t="'activities::activity.type.type'"
              class="inline font-medium lg:hidden"
            />

            <IDropdown>
              <IDropdownButton
                class="lg:mt-1.5"
                :as="IBadgeButton"
                :disabled="!authorizations.update"
                :color="type.swatch_color"
              >
                <span class="max-w-32 truncate" v-text="type.name" />
              </IDropdownButton>

              <IDropdownMenu>
                <IDropdownItem
                  v-for="activityType in types"
                  :key="activityType.id"
                  :text="activityType.name"
                  :active="typeId === activityType.id"
                  :icon="activityType.icon"
                  @click="updateActivity({ activity_type_id: activityType.id })"
                />
              </IDropdownMenu>
            </IDropdown>
          </div>
        </div>
      </div>

      <ICardActions class="mt-1 self-start">
        <IDropdownMinimal small>
          <IDropdownItem
            v-if="authorizations.update"
            :text="$t('core::app.edit')"
            @click="toggleEdit"
          />

          <IDropdownItem
            :text="$t('activities::activity.download_ics')"
            @click="downloadICS"
          />

          <IDropdownItem
            v-if="authorizations.delete"
            :text="$t('core::app.delete')"
            @click="$confirm(() => destroy(activityId))"
          />
        </IDropdownMinimal>
      </ICardActions>
    </ICardHeader>

    <div
      v-if="isDue"
      :class="[
        '-mt-px flex items-center border-warning-600/20 bg-warning-50 px-4 py-1.5 text-base text-warning-800 dark:border-warning-400/20 dark:bg-warning-400/10 dark:text-warning-400 sm:px-6 sm:text-sm',
        Boolean(note) ? 'border-t' : 'border-y',
      ]"
    >
      <Icon icon="Clock" class="mr-3 size-5" />

      <span>
        {{
          $t('activities::activity.activity_was_due', {
            date: hasTime(dueDate)
              ? localizedDateTime(dueDate)
              : localizedDate(dueDate),
          })
        }}
      </span>
    </div>

    <div @dblclick="toggleEdit">
      <div
        v-if="note"
        class="-mt-px border-y border-warning-600/20 bg-warning-50 dark:border-warning-400/20 dark:bg-warning-400/10"
      >
        <TextCollapse
          class="wysiwyg-text px-4 py-1.5 leading-5 text-warning-800 dark:text-warning-400 sm:px-6"
          :text="note"
          :length="100"
          lightbox
        >
          <template #action="{ collapsed, toggle }">
            <div
              v-show="collapsed"
              class="absolute bottom-0 h-6 w-full cursor-pointer bg-gradient-to-t from-warning-50 to-transparent dark:from-warning-400/20"
              @click="toggle"
            />

            <ILink
              v-show="!collapsed"
              variant="warning"
              class="my-2.5 inline-block px-4 text-base font-medium sm:px-6 sm:text-sm"
              :text="$t('core::app.show_less')"
              @click="toggle"
            />
          </template>
        </TextCollapse>
      </div>

      <ICardBody>
        <div class="space-y-4 sm:space-y-6">
          <div v-if="description" class="mb-8">
            <ITextBlockDark class="mb-1 inline-flex font-medium">
              <Icon
                icon="Bars3BottomLeft"
                class="mr-3 size-5 text-neutral-500 dark:text-neutral-300"
              />

              <span v-t="'activities::activity.description'" />
            </ITextBlockDark>

            <div class="ml-8">
              <TextCollapse
                class="wysiwyg-text leading-5 dark:text-neutral-300 sm:mb-0"
                :text="description"
                :length="200"
                lightbox
              />
            </div>
          </div>

          <div
            class="flex flex-col flex-wrap space-y-2 align-baseline lg:flex-row lg:space-y-0"
          >
            <div
              v-if="selectedUser"
              v-i-tooltip.top="$t('activities::activity.owner')"
              class="mr-0 self-start sm:self-auto lg:-ml-2 lg:mr-4"
            >
              <IDropdown v-if="authorizations.update">
                <IDropdownButton class="-ml-3.5 lg:-ml-1" basic>
                  <IAvatar size="xs" :src="selectedUser.avatar_url" />

                  {{ selectedUser.name }}
                </IDropdownButton>

                <IDropdownMenu class="max-h-64">
                  <IDropdownItem
                    v-for="user in users"
                    :key="user.id"
                    :text="user.name"
                    :active="userId === user.id"
                    @click="updateActivity({ user_id: user.id })"
                  />
                </IDropdownMenu>
              </IDropdown>

              <ITextBlockDark
                v-else
                class="ml-2 flex items-center space-x-1.5 font-medium"
              >
                <IAvatar size="xs" :src="selectedUser.avatar_url" />

                <span v-text="selectedUser.name" />
              </ITextBlockDark>
            </div>

            <ActivityDateDisplay
              :due-date="dueDate"
              :end-date="endDate"
              :is-due="isDue"
            />
          </div>
        </div>

        <IText
          v-if="reminderMinutesBefore && !isReminded"
          class="mt-1 flex items-center"
        >
          <Icon icon="Bell" class="mr-2 size-5" />

          {{ reminderText }}
        </IText>
      </ICardBody>
    </div>

    <div
      class="border-t border-neutral-200 px-4 py-2.5 dark:border-neutral-500/30 sm:px-6"
    >
      <ILink
        class="group inline-flex items-center"
        basic
        @click="attachmentsAreVisible = !attachmentsAreVisible"
      >
        <Icon
          icon="PaperClip"
          class="mr-3 size-5 text-neutral-500 group-hover:text-neutral-700 dark:text-neutral-300 dark:group-hover:text-white"
        />

        <span>
          {{ $t('core::app.attachments') }} ({{ attachmentsCount }})
        </span>

        <Icon
          class="ml-2 size-4"
          :icon="
            attachmentsAreVisible ? 'ChevronDownSolid' : 'ChevronRightSolid'
          "
        />
      </ILink>

      <ResourceRecordMediaList
        v-show="attachmentsAreVisible"
        class="ml-8"
        :resource-name="resourceName"
        :resource-id="activityId"
        :media="media"
        :authorize-delete="authorizations.update"
        @deleted="handleActivityMediaDeleted"
        @uploaded="handleActivityMediaUploaded"
      />
    </div>

    <div
      v-show="commentsCount"
      class="border-t border-neutral-200 px-4 py-2.5 dark:border-neutral-500/30 sm:px-6"
    >
      <CollapsableCommentsList
        v-slot="{
          hasComments,
          totalComments,
          commentsAreBeingLoaded,
          toggleCommentsVisibility,
        }"
        commentable-type="activities"
        class="ml-8 mt-3"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        :commentable-id="activityId"
        :count="commentsCount"
        :comments="comments"
        @updated="
          synchronizeResource({
            activities: { id: activityId, comments: $event },
          })
        "
        @deleted="
          synchronizeResource({
            activities: {
              id: activityId,
              comments: { id: $event, _delete: true },
            },
          })
        "
        @update:comments="
          synchronizeResource({
            activities: { id: activityId, comments: $event },
          })
        "
        @update:count="
          synchronizeResource({
            activities: { id: activityId, comments_count: $event },
          })
        "
      >
        <CollapseableCommentsLink
          v-if="hasComments"
          :loading="commentsAreBeingLoaded"
          :total="totalComments"
          :collapsed="commentsAreVisible"
          @click="toggleCommentsVisibility"
        />
      </CollapsableCommentsList>
    </div>

    <ICardFooter class="text-right">
      <CommentsAdd
        commentable-type="activities"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        :commentable-id="activityId"
        @created="
          (commentsAreVisible = true),
            synchronizeResource({
              activities: {
                id: activityId,
                comments: [$event],
              },
            })
        "
      />
    </ICardFooter>
  </ICard>

  <EditActivity
    v-if="activityBeingEdited"
    :activity-id="activityId"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :related-resource="relatedResource"
    @cancelled="activityBeingEdited = false"
    @updated="activityBeingEdited = false"
  />
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AssociationsPopover from '@/Core/components/AssociationsPopover.vue'
import ResourceRecordMediaList from '@/Core/components/Media/ResourceRecordMediaList.vue'
import { IBadgeButton } from '@/Core/components/UI/Badge'
import { handleActionResponse } from '@/Core/composables/useAction'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useResourceable } from '@/Core/composables/useResourceable'
import {
  determineReminderTypeBasedOnMinutes,
  determineReminderValueBasedOnMinutes,
} from '@/Core/utils'

import { useComments } from '@/Comments/composables/useComments'

import { useActivityTypes } from '../composables/useActivityTypes'

import ActivityDateDisplay from './ActivityDateDisplay.vue'
import StateChange from './ActivityStateChange.vue'
import EditActivity from './RelatedActivityEdit.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  activityId: { required: true, type: Number },
  title: { required: true, type: String },
  commentsCount: { required: true, type: Number },
  isCompleted: { required: true, type: Boolean },
  isReminded: { required: true, type: Boolean },
  isDue: { required: true, type: Boolean },
  typeId: { required: true, type: Number },
  userId: { required: true, type: Number },
  note: { required: true },
  description: { required: true },
  reminderMinutesBefore: { required: true },
  dueDate: { required: true },
  endDate: { required: true },
  attachmentsCount: { required: true, type: Number },
  media: { required: true, type: Array },
  authorizations: { required: true, type: Object },
  comments: { required: true, type: Array },
  associationsCount: { required: true, type: Number },
  viaResource: { required: true, type: String },
  viaResourceId: { required: true, type: [String, Number] },
  relatedResource: { required: true, type: Object },
})

const resourceName = Innoclapps.resourceName('activities')

const synchronizeResource = inject('synchronizeResource')
const incrementResourceCount = inject('incrementResourceCount')
const decrementResourceCount = inject('decrementResourceCount')

const { t } = useI18n()
const { localizedDateTime, localizedDate, hasTime } = useDates()
const { updateResource, deleteResource } = useResourceable(resourceName)
const { users, findUserById } = useApp()
const { commentsAreVisible } = useComments(props.activityId, 'activities')

const activityBeingEdited = ref(false)
const attachmentsAreVisible = ref(false)

const { typesByName: types, findTypeById } = useActivityTypes()

const type = computed(() => findTypeById(props.typeId))

const selectedUser = computed(() => findUserById(props.userId))

const reminderText = computed(() => {
  return t('core::app.reminder_set_for', {
    value: determineReminderValueBasedOnMinutes(props.reminderMinutesBefore),
    type: t(
      'core::dates.' +
        determineReminderTypeBasedOnMinutes(props.reminderMinutesBefore)
    ),
  })
})

/**
 * Download ICS file for the activity.
 */
function downloadICS() {
  Innoclapps.request({
    method: 'post',
    data: {
      ids: [props.activityId],
    },
    responseType: 'blob',
    url: '/activities/actions/download-ics-file/run',
  }).then(handleActionResponse)
}

/**
 * Update the current activity.
 */
function updateActivity(payload = {}) {
  updateResource(
    {
      via_resource: props.viaResource,
      via_resource_id: props.viaResourceId,
      ...payload,
    },
    props.activityId
  ).then(updatedActivity =>
    synchronizeResource({ activities: updatedActivity })
  )
}

/**
 * Delete activity from storage.
 */
async function destroy(id) {
  await deleteResource(id)

  synchronizeResource({ activities: { id, _delete: true } })
  decrementResourceCount('incomplete_activities_for_user_count')

  Innoclapps.success(t('activities::activity.deleted'))
}

/**
 * Activity state changed.
 */
function handleActivityStateChanged(activity) {
  synchronizeResource({ activities: activity })

  if (activity.is_completed) {
    decrementResourceCount('incomplete_activities_for_user_count')
  } else {
    incrementResourceCount('incomplete_activities_for_user_count')
  }
}

/**
 * Toggle edit.
 */
function toggleEdit(e) {
  // The double click to edit should not work while in edit mode
  if (e.type == 'dblclick' && activityBeingEdited.value) return
  // For double click event
  if (!props.authorizations.update) return

  activityBeingEdited.value = !activityBeingEdited.value
}

/**
 * Handle activity media uploaded.
 */
function handleActivityMediaUploaded(media) {
  synchronizeResource({
    activities: {
      id: props.activityId,
      media: [media],
    },
  })
}

/**
 * Handle activity media deleted.
 */
function handleActivityMediaDeleted(media) {
  synchronizeResource({
    activities: {
      id: props.activityId,
      media: { id: media.id, _delete: true },
    },
  })
}
</script>
