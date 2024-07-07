<template>
  <div
    :class="[
      'flex items-center text-base/6 sm:text-sm/6',
      isDue
        ? 'text-danger-500 dark:text-danger-400'
        : 'text-neutral-800 dark:text-white',
    ]"
  >
    <div class="flex shrink-0">
      <Icon
        v-if="withIcon"
        icon="Calendar"
        :class="[
          'mr-2 size-5',
          {
            'text-neutral-800 dark:text-white': !isDue,
            'text-danger-400': isDue,
          },
        ]"
      />
      {{
        hasTime(dueDate) ? localizedDateTime(dueDate) : localizedDate(dueDate)
      }}
    </div>

    <span v-if="formattedEndDateForDisplay" class="ml-1 truncate">
      <span>-</span>
      {{ formattedEndDateForDisplay }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { useDates } from '@/Core/composables/useDates'

const props = defineProps({
  dueDate: { required: true },
  endDate: { required: true },
  isDue: { required: true, type: Boolean },
  withIcon: { type: Boolean, default: true },
})

const { DateTime, localizedDate, localizedTime, localizedDateTime, hasTime } =
  useDates()

const formattedEndDateForDisplay = computed(() => {
  if (!props.endDate) return ''

  const endDateHasTime = hasTime(props.endDate)
  const dueDateHasTime = hasTime(props.dueDate)

  const endDate = DateTime.fromISO(props.endDate)
  const dueDate = DateTime.fromISO(props.dueDate)

  if (!endDateHasTime && !dueDateHasTime) {
    // If different day, no time, display end date
    if (!endDate.hasSame(dueDate, 'day')) {
      return localizedDate(props.endDate)
    }
  } else if (endDateHasTime && dueDateHasTime) {
    // If same day, different time, display only end time
    if (endDate.hasSame(dueDate, 'day')) {
      if (endDate.toFormat('HH:mm') !== dueDate.toFormat('HH:mm')) {
        return localizedTime(props.endDate)
      }
    } else {
      // If different day, has time, display end date and end time
      return localizedDateTime(props.endDate)
    }
  } else {
    // Cases where one has time and the other doesn't
    if (!endDate.hasSame(dueDate, 'day')) {
      return endDateHasTime
        ? localizedDateTime(props.endDate)
        : localizedDate(props.endDate)
    }
  }

  return ''
})
</script>
