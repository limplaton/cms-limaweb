<template>
  <MainLayout :overlay="isLoading" no-padding>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <NavbarItems>
        <ActivitiesNavbarViewSelector active="calendar" />

        <IButton
          icon="PlusSolid"
          variant="primary"
          :text="$t('activities::activity.create')"
          @click="eventBeingCreated = true"
        />
      </NavbarItems>
    </template>

    <div class="px-3 py-4 sm:px-5">
      <FullCalendar
        ref="calendarRef"
        calendar-id="activities"
        :config="{ events }"
        @event-drop="updateOnDrop"
        @event-resize="updateOnResize"
        @loading="setLoading"
        @event-click="
          floatResourceInEditMode({
            resourceName,
            resourceId: parseInt($event.event.id),
          })
        "
        @date-click="initiateNewActivityOnDateClick"
      >
        <template #eventContent="arg">
          <FullCalendarEvent
            v-slot="{ usesExtendedView, timeForDisplay }"
            :color="arg.event.extendedProps.type.swatch_color"
            :arg="arg"
          >
            <template v-if="usesExtendedView">
              <span v-if="usesExtendedView" class="block space-x-1 opacity-90">
                <span v-if="!arg.event.allDay" v-text="timeForDisplay" />

                <span v-text="arg.event.extendedProps.type.name" />
              </span>

              <span class="font-semibold" v-text="arg.event.title" />
            </template>

            <span v-else>
              {{
                timeForDisplay +
                ' ' +
                arg.event.extendedProps.type.name +
                ' - ' +
                arg.event.title
              }}
            </span>
          </FullCalendarEvent>
        </template>

        <template #header>
          <div class="flex items-center space-x-2">
            <IFormLabel :label="$t('activities::activity.type.type')" />

            <IDropdown>
              <IDropdownButton
                :as="IBadgeButton"
                :color="selectedType ? selectedType.swatch_color : '#94a3b8'"
                :text="selectedType?.name || $t('core::app.all')"
              />

              <IDropdownMenu>
                <IDropdownItem
                  v-for="activityType in typesByName"
                  :key="activityType.id"
                  :text="activityType.name"
                  :icon="activityType.icon"
                  :active="data.activity_type_id === activityType.id"
                  @click="data.activity_type_id = activityType.id"
                />

                <IDropdownSeparator />

                <IDropdownItem
                  :text="$t('core::app.all')"
                  :active="!data.activity_type_id"
                  @click="data.activity_type_id = null"
                />
              </IDropdownMenu>
            </IDropdown>
          </div>

          <div
            v-if="$gate.userCan('view all activities')"
            class="flex items-center space-x-2"
          >
            <IFormLabel :label="$t('activities::activity.owner')" />

            <IDropdown>
              <IDropdownButton
                :text="selectedUser?.name || $t('core::app.all')"
                basic
              />

              <IDropdownMenu>
                <IDropdownItem
                  v-for="user in users"
                  :key="user.id"
                  :text="user.name"
                  :active="data.user_id === user.id"
                  @click="data.user_id = user.id"
                />

                <IDropdownSeparator />

                <IDropdownItem
                  :text="$t('core::app.all')"
                  :active="!data.user_id"
                  @click="data.user_id = null"
                />
              </IDropdownMenu>
            </IDropdown>
          </div>
        </template>
      </FullCalendar>

      <CreateActivityModal
        :visible="eventBeingCreated"
        :due-date="createDueDate"
        :end-date="createEndDate"
        @created="onActivityCreatedEventHandler"
        @hidden="handleActivityCreateModalHidden"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useStorage } from '@vueuse/core'

import FullCalendarEvent from '@/Core/components/FullCalendarEvent.vue'
import { IBadgeButton } from '@/Core/components/UI/Badge'
import { useApp } from '@/Core/composables/useApp'
import { usePrivateChannel } from '@/Core/composables/useBroadcast'
import { useDates } from '@/Core/composables/useDates'
import { useFloatingResourceModal } from '@/Core/composables/useFloatingResourceModal'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { useLoader } from '@/Core/composables/useLoader'
import { useResourceable } from '@/Core/composables/useResourceable'

import ActivitiesNavbarViewSelector from '../components/ActivitiesNavbarViewSelector.vue'
import { useActivityTypes } from '../composables/useActivityTypes'

const resourceName = Innoclapps.resourceName('activities')

const { setLoading, isLoading } = useLoader()
const { floatResourceInEditMode } = useFloatingResourceModal()
const { currentUser, users } = useApp()
const { DateTime } = useDates()
const { typesByName, findTypeById } = useActivityTypes()
const { updateResource } = useResourceable(resourceName)

const calendarRef = ref(null)

const createDueDate = ref(null)
const createEndDate = ref(null)
const eventBeingCreated = ref(false)

const data = useStorage('activitiesCalendar', {
  activity_type_id: null,
  user_id: currentUser.value.id,
})

const selectedUser = computed(() =>
  users.value.find(u => u.id == data.value.user_id)
)

const selectedType = computed(() => findTypeById(data.value.activity_type_id))

watch(() => [data.value.activity_type_id, data.value.user_id], refreshEvents)

function events(info, successCallback, failureCallback) {
  Innoclapps.request('/calendar', {
    params: {
      resource_name: resourceName,
      activity_type_id: data.value.activity_type_id,
      user_id: data.value.user_id,
      start_date: DateTime.fromJSDate(info.start)
        .toUTC()
        .toFormat('yyyy-MM-dd HH:mm:ss'),
      end_date: DateTime.fromJSDate(info.end)
        .toUTC()
        .toFormat('yyyy-MM-dd HH:mm:ss'),
    },
  })
    .then(({ data }) => successCallback(prepareEventsForCalendar(data)))
    .catch(error => {
      console.error(error)
      failureCallback('Error while retrieving events', error)
    })
}

function initiateNewActivityOnDateClick(info) {
  createDueDate.value = info.allDay
    ? info.dateStr
    : DateTime.fromISO(data.dateStr).toUTC().toISO()

  // On end date, we will format with the user timezone as the end date
  // has not time when on dateClick click and for this reason, we must get the actual date
  // to be displayed in the create modal e.q. if user click on day view 19th April 12 AM
  // the dueDate will be shown properly but not the end date as if we format the end date
  // with UTC will 18th April e.q. 18th April 22:00 (UTC)
  createEndDate.value = info.allDay
    ? info.dateStr
    : DateTime.fromISO(info.dateStr).toUTC().toISODate()

  eventBeingCreated.value = true
}

function updateOnResize(resizeInfo) {
  const { id, allDay, startStr, endStr } = resizeInfo.event

  if (allDay) {
    updateResource(
      { due_date: startStr, end_date: endDateForStorage(endStr) },
      id
    )

    return
  }

  const dueDateTimeInstance = DateTime.fromISO(startStr).toUTC()
  const endDateTimeInstance = DateTime.fromISO(endStr).toUTC()

  updateResource(
    {
      due_date: dueDateTimeInstance.toISO(),
      end_date: endDateTimeInstance.toISO(),
    },
    id
  )
}

function updateOnDrop(dropInfo) {
  const payload = {}

  const { event } = dropInfo
  const { allDay, startStr, endStr } = event

  if (allDay) {
    payload.due_date = startStr

    // When dropping event from time column to all day e.q. on week view
    // there is no end date as it's the same day, for this reason, we need to update the
    // end date to be the same like the start date for the update request payload
    if (!event.end) {
      payload.end_date = payload.due_date
    } else {
      // Multi days event, we will remove the one day to store
      // the end date properly in database as here for the calendar they are endDate + 1 day so they are
      // displayed properly see prepareEventsForCalendar method
      payload.end_date = endDateForStorage(endStr)
    }

    event.setExtendedProp('isAllDay', true)
    event.setEnd(endDateForCalendar(payload.end_date))
  } else {
    const dueDateUTCInstance = DateTime.fromISO(startStr).toUTC()

    payload.due_date = dueDateUTCInstance.toISO()

    // When dropping all day event to non all day e.q. on week view from top to the timeline
    // we need to update the end date as well
    if (dropInfo.oldEvent.allDay && !allDay) {
      let endDateLocalInstance = DateTime.fromISO(startStr).plus({
        hours: 1,
      })

      payload.end_date = endDateLocalInstance.toUTC().toISO()
      event.setEnd(endDateLocalInstance.toISO())
      event.setExtendedProp('hasEndTime', true)
    } else {
      // We will check if the actual endStr is set, if not will use the due dates as due time
      // because this may happen when the activity due and end
      // date are the same, in this case, fullcalendar does not provide the endStr
      payload.end_date = endStr
        ? DateTime.fromISO(endStr).toUTC().toISODate()
        : payload.due_date

      // Time can be modified on week and day view, on month view we will
      // only modify the time on actual activities with time
      if (
        dropInfo.view.type !== 'dayGridMonth' ||
        event.extendedProps.hasEndTime
      ) {
        payload.end_date = endStr
          ? DateTime.fromISO(endStr).toUTC().toISO()
          : payload.due_date
        event.setExtendedProp('hasEndTime', true)
      }
    }

    event.setExtendedProp('isAllDay', false)
  }

  updateResource(payload, event.id)
}

function onActivityCreatedEventHandler() {
  refreshEvents()
  eventBeingCreated.value = false
}

function handleActivityCreateModalHidden() {
  eventBeingCreated.value = false
  createDueDate.value = null
  createEndDate.value = null
}

/**
 * Create end date for the calendar
 *
 * @see  prepareEventsForCalendar
 *
 * @param  {string} date
 *
 * @returns {string}
 */
function endDateForCalendar(date) {
  return DateTime.fromISO(date).plus({ days: 1 }).toISODate()
}

/**
 * Create end date for storage
 *
 * @see  prepareEventsForCalendar
 *
 * @param  {string} date
 *
 * @returns {string}
 */
function endDateForStorage(date) {
  return DateTime.fromISO(date).minus({ days: 1 }).toISODate()
}

function prepareEventsForCalendar(events) {
  return events.map(event => {
    // Remove the default colors of the events
    event.backgroundColor = 'inherit'
    event.borderColor = 'transparent'

    // @see https://stackoverflow.com/questions/30323397/fullcalendar-event-shows-wrong-end-date-by-one-day
    // @see https://fullcalendar.io/docs/event-parsing
    // e.q. event with start 2021-04-01 and end date 2021-04-03 in the calendar is displayed
    // from 2021-04-01 to 2021-04-02, in this case on fetch, we will add 1 days so they are
    // displayed properly and on update, we will remove 1 day so they are saved properly
    event.extendedProps.isAllDay = event.allDay

    if (event.allDay) {
      event.end = endDateForCalendar(event.end)
    } else if (!/\d{4}-\d{2}-\d{2}\T?\d{2}:\d{2}:\d{2}$/.test(event.end)) {
      // no end time, is not in y-m-dTh:i:s format
      // to prevent clogging the calendar with events showing
      // over the week/day view, we will just add the start hour:minute
      // as end hour:minute + 30 minutes to be shown in one simple box
      // this can usually happen when to due and the end date are the same and there is no end time
      const dateTimeStart = DateTime.fromISO(event.start)
      const dateTimeEnd = DateTime.fromISO(event.end)

      event.end = dateTimeEnd
        .set({
          hour: dateTimeStart.hour,
          minute: dateTimeStart.minute,
          second: 0,
        })
        .plus({ minutes: 30 })
        .toFormat("yyyy-MM-dd'T'HH:mm:ss")
      event.extendedProps.hasEndTime = false
    } else {
      event.extendedProps.hasEndTime = true
    }

    if (event.isReadOnly) {
      event.editable = false
    }

    return event
  })
}

function refreshEvents() {
  calendarRef.value.refresh()
}

usePrivateChannel(
  `Modules.Users.App.Models.User.${currentUser.value.id}`,
  '.Modules\\Activities\\App\\Events\\CalendarSyncFinished',
  refreshEvents
)

useGlobalEventListener('floating-resource-hidden', refreshEvents)
</script>

<style>
/* past days with unfinished activities */
.fc .fc-day-past:not(.fc-popover):has(.fc-daygrid-event-harness) {
  @apply bg-danger-200/10 dark:bg-danger-500/10;
}
</style>
