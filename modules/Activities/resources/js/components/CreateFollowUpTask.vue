<template>
  <div class="flex items-center">
    <IFormCheckboxField class="shrink-0">
      <IFormCheckbox
        v-model:checked="withTask"
        @change="handleCheckboxChange"
      />

      <IFormCheckboxLabel
        :class="withTask ? 'hidden sm:block' : ''"
        :text="$t('activities::activity.create_follow_up_task')"
      />
    </IFormCheckboxField>

    <div v-if="withTask" class="flex sm:ml-2">
      <div v-show="!isCustomDateSelected" class="shrink-0">
        <IDropdown>
          <IDropdownButton basic>
            {{ dropdownLabel }}
          </IDropdownButton>

          <IDropdownMenu>
            <IDropdownItem
              v-for="date in dates"
              :key="date.value"
              :text="date.label"
              condensed
              @click="onDropdownSelected(date.value)"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>

      <DatePicker v-if="isCustomDateSelected" v-model="model" :required="true">
        <template #default="{ inputValue, inputEvents }">
          <input
            class="cursor-pointer rounded-md border-neutral-300 bg-transparent py-1 text-base font-medium text-neutral-700 ring-primary-600 focus:border-transparent focus:ring-primary-600 dark:border-neutral-500/30 dark:text-neutral-100 dark:ring-primary-500 dark:focus:ring-primary-500 sm:text-sm"
            :value="inputValue"
            v-on="inputEvents"
          />
        </template>
      </DatePicker>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTimeoutFn } from '@vueuse/core'
import find from 'lodash/find'

import { useDates } from '@/Core/composables/useDates'

const model = defineModel()

const { t } = useI18n()
const { DateTime, LocalDateTimeInstance, UTCDateTimeInstance } = useDates()

const selectedDropdownDate = ref('')
const withTask = ref(false)

/**
 * Today's date object
 */
const dateToday = computed(() => ({
  label: t('core::dates.today'),
  value: UTCDateTimeInstance.toISODate(),
}))

/**
 * Tomorrow date object
 */
const dateTomorrow = computed(() => ({
  label: t('core::dates.tomorrow'),
  value: UTCDateTimeInstance.plus({ days: 1 }).toISODate(),
  default: true,
}))

/**
 * Date in 2 days object
 */
const dateIn2Days = computed(() => ({
  label:
    t('core::dates.in_x_days', 2) +
    ' (' +
    LocalDateTimeInstance.plus({ days: 2 }).toFormat('cccc') +
    ')',
  value: UTCDateTimeInstance.plus({ days: 2 }).toISODate(),
}))

/**
 * Date in  days object
 */
const dateIn3Days = computed(() => ({
  label:
    t('core::dates.in_x_days', 3) +
    ' (' +
    LocalDateTimeInstance.plus({ days: 3 }).toFormat('cccc') +
    ')',
  value: UTCDateTimeInstance.plus({ days: 3 }).toISODate(),
}))

/**
 * Date in 4 days object
 */
const dateIn4Days = computed(() => ({
  label:
    t('core::dates.in_x_days', 4) +
    ' (' +
    LocalDateTimeInstance.plus({ days: 4 }).toFormat('cccc') +
    ')',
  value: UTCDateTimeInstance.plus({ days: 4 }).toISODate(),
}))

/**
 * Date in 5 days object
 */
const dateIn5Days = computed(() => ({
  label:
    t('core::dates.in_x_days', 5) +
    ' (' +
    LocalDateTimeInstance.plus({ days: 5 }).toFormat('cccc') +
    ')',
  value: UTCDateTimeInstance.plus({ days: 5 }).toISODate(),
}))

/**
 * Date in 1 week object
 */
const dateIn1Week = computed(() => {
  const dateTimeInstance = addToDateTimeAvoidWeekends(
    LocalDateTimeInstance,
    'weeks',
    1
  )

  return {
    label:
      t('core::dates.in_x_weeks', 1) +
      ' (' +
      dateTimeInstance.toLocaleString(DateTime.DATE_MED_WITH_WEEKDAY) +
      ')',
    value: dateTimeInstance.toUTC().toISODate(),
  }
})

/**
 * Date in 2 weeks object
 */
const dateIn2Weeks = computed(() => {
  const dateTimeInstance = addToDateTimeAvoidWeekends(
    LocalDateTimeInstance,
    'weeks',
    2
  )

  return {
    label:
      t('core::dates.in_x_weeks', 2) +
      ' (' +
      dateTimeInstance.toLocaleString(DateTime.DATE_MED_WITH_WEEKDAY) +
      ')',
    value: dateTimeInstance.toUTC().toISODate(),
  }
})

/**
 * Date in 1 month object
 */
const dateIn1Month = computed(() => {
  const dateTimeInstance = addToDateTimeAvoidWeekends(
    LocalDateTimeInstance,
    'month',
    1
  )

  return {
    label:
      t('core::dates.in_x_months', 1) +
      ' (' +
      dateTimeInstance.toLocaleString(DateTime.DATE_MED_WITH_WEEKDAY) +
      ')',
    value: dateTimeInstance.toUTC().toISODate(),
  }
})

/**
 * Whether the "custom" dropdown option is selected
 */
const isCustomDateSelected = computed(
  () => selectedDropdownDate.value === 'custom'
)

/**
 * Dates for dropdown
 */
const dates = computed(() => [
  unref(dateToday),
  unref(dateTomorrow),
  unref(dateIn2Days),
  unref(dateIn3Days),
  unref(dateIn4Days),
  unref(dateIn5Days),
  unref(dateIn1Week),
  unref(dateIn2Weeks),
  unref(dateIn1Month),
  {
    label: t('core::dates.custom'),
    value: 'custom',
  },
])

/**
 * Label for the dropdown text based on selected date
 */
const dropdownLabel = computed(() => {
  let selected = find(dates.value, ['value', selectedDropdownDate.value])

  if (selected) {
    return selected.label
  }

  return ''
})

/**
 * Add a unit to the given DateTime instance but if it's weekend, use monday.
 *
 * @param {DateTime} startDate
 * @param {string} unit
 * @param {number} units
 */
function addToDateTimeAvoidWeekends(startDate, unit, units) {
  let newDate = startDate.plus({ [unit]: units })

  // Check if the new date is Saturday (6) or Sunday (7)
  if (newDate.weekday === 6) {
    // If Saturday, add 2 days to get to Monday
    newDate = newDate.plus({ days: 2 })
  } else if (newDate.weekday === 7) {
    // If Sunday, add 1 day to get to Monday
    newDate = newDate.plus({ days: 1 })
  }

  return newDate
}

/**
 * The default value
 *
 * @return {String}
 */
const defaultValue = computed(() => find(dates.value, ['default', true]).value)

/**
 * On date option selected from the dropdown
 * @param  {String} value
 * @return {Void}
 */
function onDropdownSelected(value) {
  if (value !== 'custom') {
    model.value = value
  }

  // Use timeout to avoid the dropdown flickering when hiding
  useTimeoutFn(() => (selectedDropdownDate.value = value), 300)
}

/**
 * Handle the checkbox "Create follow up task" change event
 *
 * @param  {Boolean} value
 *
 * @return {Void}
 */
function handleCheckboxChange(value) {
  if (value && !model.value) {
    nextTick(() => {
      model.value = defaultValue.value
      selectedDropdownDate.value = defaultValue.value
    })
  } else if (!value) {
    model.value = null
  }
}

function reset() {
  withTask.value = false
}

defineExpose({ reset })
</script>
