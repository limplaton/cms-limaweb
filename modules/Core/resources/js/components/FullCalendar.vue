<template>
  <div class="mb-4 space-y-3 sm:flex sm:flex-wrap sm:items-center sm:space-y-0">
    <div class="grow">
      <div
        class="space-y-2 sm:flex sm:flex-wrap sm:items-center sm:space-x-3 sm:space-y-0"
      >
        <slot name="header" />
      </div>
    </div>

    <div
      class="flex w-full items-center justify-between space-x-3 sm:w-auto sm:justify-end"
    >
      <div class="flex space-x-0.5">
        <IButton
          v-i-tooltip="
            $t('activities::calendar.fullcalendar.locale.buttonText.prev')
          "
          icon="ChevronLeftSolid"
          basic
          @click="goToPrev"
        />

        <IButton
          class="font-semibold"
          variant="secondary"
          :text="
            $t('activities::calendar.fullcalendar.locale.buttonText.today')
          "
          @click="goToToday"
        />

        <IButton
          v-i-tooltip="
            $t('activities::calendar.fullcalendar.locale.buttonText.next')
          "
          icon="ChevronRightSolid"
          basic
          @click="goToNext"
        />
      </div>

      <IDropdown adaptive-width>
        <IDropdownButton
          variant="secondary"
          icon="Calendar"
          :text="activeViewText"
        />

        <IDropdownMenu>
          <IDropdownItem
            :text="
              $t('activities::calendar.fullcalendar.locale.buttonText.week')
            "
            :active="data.view === 'timeGridWeek'"
            @click="changeView('timeGridWeek')"
          />

          <IDropdownItem
            :text="
              $t('activities::calendar.fullcalendar.locale.buttonText.month')
            "
            :active="data.view === 'dayGridMonth'"
            @click="changeView('dayGridMonth')"
          />

          <IDropdownItem
            :text="
              $t('activities::calendar.fullcalendar.locale.buttonText.day')
            "
            :active="data.view === 'timeGridDay'"
            @click="changeView('timeGridDay')"
          />
        </IDropdownMenu>
      </IDropdown>
    </div>
  </div>

  <div
    ref="wrapperRef"
    class="rounded-lg bg-white text-sm text-neutral-500 dark:bg-neutral-900 dark:text-neutral-200"
    @mousedown="closeMorePopoverIfVisibleOnEventClick"
  >
    <BaseFullCalendar
      v-if="data.view"
      ref="calendarRef"
      class="h-screen"
      :options="options"
    >
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>
    </BaseFullCalendar>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import luxonPlugin from '@fullcalendar/luxon3'
import timeGridPlugin from '@fullcalendar/timegrid'
import BaseFullCalendar from '@fullcalendar/vue3'
import { useStorage } from '@vueuse/core'

import { useApp } from '../composables/useApp'
import { usePageTitle } from '../composables/usePageTitle'

const props = defineProps({
  calendarId: { type: String, required: true }, // should not be reactive
  config: Object,
  eventMinHeight: { type: Number, default: 26 },
  defaultView: { type: String, default: 'timeGridWeek' },
})

const emit = defineEmits([
  'dateClick',
  'eventClick',
  'eventResize',
  'eventDrop',
  'loading',
])

const { t } = useI18n()
const pageTitle = usePageTitle()
const { currentUser, locale: currentLocale } = useApp()

const wrapperRef = ref(null)
const calendarRef = ref(null)

const data = useStorage(`cfc${props.calendarId}`, {
  view: props.defaultView,
  last_date: null,
})

const defaultOptions = {
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, luxonPlugin],
  locale: currentLocale.value.toLowerCase().replace('_', '-'),

  locales: [
    {
      code: currentLocale.value.toLowerCase().replace('_', '-'),
      ...lang[currentLocale.value].activities.calendar.fullcalendar.locale,
    },
  ],

  views: {
    day: { dayMaxEventRows: false },
    timeGridWeek: { eventMinHeight: props.eventMinHeight },
    timeGridDay: { eventMinHeight: props.eventMinHeight },
  },

  headerToolbar: false,
  dayMaxEventRows: true, // for all non-TimeGrid views
  eventDisplay: 'block',
  initialView: data.value.view,
  initialDate: data.value.last_date,
  timeZone: currentUser.value.timezone,
  lazyFetching: false,
  editable: true,
  droppable: true,
  scrollTime: '00:00:00', // not scroll to current time e.q. on day view

  // Remove the top left all day text as it's not suitable
  allDayContent: arg => {
    arg.text = ''

    return arg
  },

  viewDidMount: arg => {
    data.value.view = arg.view.type
    pageTitle.value = arg.view.title
  },

  datesSet: function (dateInfo) {
    let date = new Date(dateInfo.view.currentStart)

    data.value.last_date = date.toISOString()
  },

  // note this is only applicable for all day events as non-all days events cannot be dragged
  // on month view (fullcalendar limitation)
  eventResize: resizeInfo => emit('eventResize', resizeInfo),
  eventDrop: dropInfo => emit('eventDrop', dropInfo),
  eventClick: eventInfo => emit('eventClick', eventInfo),
  dateClick: dateInfo => emit('dateClick', dateInfo),
  loading: isLoading => emit('loading', isLoading),
}

const options = computed(() => Object.assign({}, defaultOptions, props.config))
const eventMinHeightInRem = computed(() => props.eventMinHeight / 16 + 'rem')

const activeViewText = computed(() => {
  switch (data.value.view) {
    case 'timeGridWeek':
      return t('activities::calendar.fullcalendar.locale.buttonText.week')
    case 'dayGridMonth':
      return t('activities::calendar.fullcalendar.locale.buttonText.month')
    case 'timeGridDay':
      return t('activities::calendar.fullcalendar.locale.buttonText.day')
  }

  return ''
})

async function updatePageTitle() {
  await nextTick()
  pageTitle.value = calendarRef.value.getApi().currentData.viewTitle
}

function changeView(viewName) {
  calendarRef.value.getApi().changeView(viewName)

  data.value.view = viewName
  updatePageTitle()
}

function goToPrev() {
  calendarRef.value.getApi().prev()
  updatePageTitle()
}

function goToNext() {
  calendarRef.value.getApi().next()
  updatePageTitle()
}

function goToToday() {
  calendarRef.value.getApi().today()
  updatePageTitle()
}

function refresh() {
  calendarRef.value.getApi().refetchEvents()
}

function closeMorePopoverIfVisibleOnEventClick(e) {
  // right click
  if (e.which === 3) {
    return
  }

  const fcPopover = e.target.closest('.fc-popover.fc-more-popover')

  const fcEvent = e.target.closest('.fc-event')

  if (fcPopover && fcEvent) {
    setTimeout(() => {
      // the parent el is '.fc-daygrid-event-harness', if hidden
      // means that the user is dragging the event, in this case no need for close.
      if (fcEvent.parentElement.style.visibility !== 'hidden') {
        fcPopover.querySelector('.fc-popover-close').click()
      }
    }, 200)
  }
}

defineExpose({ refresh })
</script>

<style>
.fc .fc-scrollgrid {
  @apply rounded-lg;
}

.fc .fc-timegrid-slot {
  height: v-bind(eventMinHeightInRem);
}

.fc .fc-event {
  @apply overflow-hidden rounded-md border-transparent shadow-none outline-none;
}

.fc .fc-col-header-cell-cushion {
  @apply p-2 text-sm font-medium text-neutral-700 dark:text-neutral-300;
}

.fc .fc-timegrid-slot-label-cushion {
  @apply text-xs uppercase text-neutral-400;
}

.fc .fc-day-other {
  @apply bg-neutral-50 dark:bg-neutral-700/10;
}

.fc .fc-popover {
  @apply ml-1 mt-1 min-w-80 max-w-lg rounded-lg bg-white shadow-sm dark:bg-neutral-800;
}

.fc .fc-popover .fc-popover-body {
  @apply max-h-96 overflow-y-auto;
}

.fc .fc-popover .fc-popover-header {
  @apply px-3 py-2;
}

.fc .fc-daygrid-day-number {
  @apply text-xs;
}

.fc .fc-day.fc-day-today .fc-daygrid-day-number {
  @apply mr-2 mt-1 flex size-6 items-center justify-center rounded-full bg-primary-600 font-semibold text-white dark:text-white;
}

.fc .fc-more-link {
  @apply text-neutral-500 hover:bg-transparent hover:text-neutral-700 dark:text-neutral-300 dark:hover:text-neutral-400;
}

.fc {
  --fc-border-color: rgba(var(--color-neutral-200));
  --fc-highlight-color: rgba(var(--color-primary-400), 0.1);

  --fc-today-bg-color: transparent;
  --fc-event-selected-overlay-color: transparent;
  --fc-event-border-color: transparent;
  --fc-event-text-color: transparent;
  --fc-event-selected-overlay-color: transparent;

  --fc-more-link-bg-color: inherit;
  --fc-more-link-text-color: inherit;

  --fc-neutral-bg-color: rgba(var(--color-neutral-300), 0.3);
  --fc-neutral-text-color: rgba(var(--color-neutral-600));
  --fc-now-indicator-color: rgba(var(--color-primary-600));
}

.dark .fc {
  --fc-border-color: rgba(var(--color-neutral-700));
  --fc-highlight-color: rgba(var(--color-primary-600), 0.3);
  --fc-neutral-bg-color: rgba(var(--color-neutral-700), 0.6);
  --fc-neutral-text-color: rgba(var(--color-neutral-200));
}
</style>
