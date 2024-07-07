<template>
  <div class="h-full w-full bg-white dark:bg-neutral-900">
    <IBadge :id="'event-' + arg.event.id" class="h-full w-full" :color="color">
      <div class="block self-start truncate font-normal">
        <slot
          :uses-extended-view="usesExtendedView"
          :time-for-display="timeForDisplay"
        >
          <span v-text="arg.event.title" />
        </slot>
      </div>
    </IBadge>
  </div>
</template>

<script setup>
import { computed, onMounted, onUpdated, ref } from 'vue'

import { useDates } from '@/Core/composables/useDates'

const props = defineProps({
  arg: { type: Object, required: true },
  color: { type: String, required: true },
  minHeight: { type: Number, default: 26 },
})

const { DateTime, localizedTime } = useDates()

const isOnOneLine = ref(true)
const isMonthView = computed(() => props.arg.view.type === 'dayGridMonth')
const usesExtendedView = computed(() => !isOnOneLine.value || isMonthView.value)

/**
 * @see https://fullcalendar.io/docs/event-render-hooks
 */
const timeForDisplay = computed(() => {
  const { arg } = props

  if (arg.event.allDay) {
    return ''
  }

  let localDateTimeStartInstance = DateTime.fromISO(arg.event.startStr)
  let localDateTimeEndInstance

  let startTime = localizedTime(localDateTimeStartInstance.toUTC().toISO())

  if (arg.isMirror && arg.isDragging && arg.event.extendedProps.isAllDay) {
    // Dropping from all day to non-all day
    // In this case, there is no end date, we will automatically add 1 hour to the start date
    localDateTimeEndInstance = DateTime.fromISO(arg.event.startStr).plus({
      hours: 1,
    })
  } else if (
    ((arg.isMirror && arg.isResizing) ||
      (arg.isMirror && arg.isDragging) ||
      (arg.event.endStr && arg.event.extendedProps.hasEndTime === true)) &&
    // This may happen when the activity due and end
    // date are the same, in this case, fullcalendar does not provide the endStr
    // attribute and the time will be shown only from the startStr
    arg.event.endStr != arg.event.startStr
  ) {
    localDateTimeEndInstance = DateTime.fromISO(arg.event.endStr)
  }

  if (localDateTimeEndInstance) {
    let endTime = localizedTime(localDateTimeEndInstance.toUTC().toISO())

    if (localDateTimeEndInstance.day != localDateTimeStartInstance.day) {
      startTime +=
        ' - ' + endTime + ' ' + localDateTimeEndInstance.toFormat('LLL d')
    } else {
      startTime += ' - ' + endTime
    }
  }

  return startTime
})

function updateIsOnOneLine() {
  let el

  if (props.arg.isMirror) {
    el = document.querySelector('.fc-event-mirror')
  } else {
    el = document.getElementById(`event-${props.arg.event.id}`)
  }

  isOnOneLine.value = el.offsetHeight <= props.minHeight - 1
}

onUpdated(updateIsOnOneLine)
onMounted(updateIsOnOneLine)
</script>
