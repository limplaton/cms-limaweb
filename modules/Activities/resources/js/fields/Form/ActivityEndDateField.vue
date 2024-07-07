<template>
  <BaseFormField
    v-slot="{ isReadonly, fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="modelValue"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :field-id="fieldId"
      :validation-errors="validationErrors"
    >
      <IFormLabel
        :for="fieldId + '-end-date'"
        :class="['mb-1 block', field.hideLabel ? 'sm:hidden' : '']"
        :label="$t('activities::activity.end_date')"
        :required="field.isRequired"
      />

      <div class="flex items-center space-x-1">
        <IFormInputDropdown
          v-model="endTime"
          max-height="300px"
          :items="inputDropdownItems"
          :input-id="fieldId + '-end-time'"
          :placeholder="timeFormatForLuxon"
          :disabled="!dueTime"
          :class="[
            'sm:max-w-[150px]',
            {
              '!border-danger-500 ring-danger-500 focus:border-danger-500 focus:ring-danger-500':
                showEndTimeWarning,
            },
          ]"
          condensed
          @show="handleTimeIsShown"
        />

        <DatePicker
          :id="fieldId + '-end-date'"
          v-model="endDate"
          :required="field.isRequired"
          :min-date="dueDate"
          :with-icon="false"
          :name="field.attribute"
          :disabled="isReadonly"
          v-bind="field.attributes"
        />
      </div>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import {
  emitGlobal,
  useGlobalEventListener,
} from '@/Core/composables/useGlobalEventListener'
import FormFieldGroup from '@/Core/fields/FormFieldGroup.vue'
import { generateTimeSlots } from '@/Core/utils'

const props = defineProps({
  field: { type: Object, required: true },
  modelValue: {},
  resourceName: String,
  resourceId: [String, Number],
  validationErrors: Object,
  isFloating: Boolean,
})

const emit = defineEmits(['update:modelValue', 'setInitialValue'])

const { scriptConfig } = useApp()

const { DateTime, timeFormatForLuxon, localizedTime, hasTime } = useDates()

const endDate = ref('')
const endTime = ref('')
const dueDate = ref('')
const dueTime = ref('')

const showEndTimeWarning = computed(() => {
  if (!endTime.value && dueTime.value && endDate.value > dueDate.value) {
    return true
  }

  return false
})

const inputDropdownItems = computed(() => {
  if (!dueTime.value || endDate.value > dueDate.value) {
    return generateTimeSlots('00:00', 15, 'minutes', 23).map(t =>
      localizedTime(DateTime.fromFormat(t, 'HH:mm').toUTC().toISO())
    )
  }

  const startIn24HourFormat = DateTime.fromFormat(
    `${dueDate.value} ${dueTime.value}`,
    `yyyy-MM-dd ${timeFormatForLuxon.value}`
  ).toFormat('HH:mm')

  return generateTimeSlots(startIn24HourFormat, 15, 'minutes', 23).map(t =>
    localizedTime(DateTime.fromFormat(t, 'HH:mm').toUTC().toISO())
  )
})

const UTCValue = computed(() => {
  if (endTime.value) {
    return utcDateTimeFromDateAndDropdownTime(
      endDate.value,
      endTime.value
    ).toISO()
  }

  return endDate.value
})

/**
 * Create UTC DateTime instance from the given date and dropdown time (already local and formatted)
 */
function utcDateTimeFromDateAndDropdownTime(date, time) {
  return DateTime.fromFormat(
    `${date} ${DateTime.fromFormat(
      `${date} ${time}`,
      `yyyy-MM-dd ${timeFormatForLuxon.value}`
    ).toFormat('HH:mm')}`,
    `yyyy-MM-dd HH:mm`
  ).setZone(scriptConfig('timezone'))
}

/**
 * Time shown event
 */
function handleTimeIsShown() {
  if (!endTime.value) {
    endTime.value = dueTime.value
  }
}

function updateModelValue(value) {
  emit('update:modelValue', value)
}

function setInitialValue() {
  let value = props.field.value

  if (!value) {
    let appDateTime

    // We will get the due time from the due time field so we can
    // properly perform a format of the actual end date with timezone.
    if (dueTime.value) {
      appDateTime = utcDateTimeFromDateAndDropdownTime(
        dueDate.value,
        dueTime.value
      )
    } else {
      appDateTime = DateTime.now().setZone(scriptConfig('timezone'))
    }

    emit('setInitialValue', appDateTime.toISODate()) // default current date
    endDate.value = appDateTime.toISODate()
  } else {
    if (hasTime(value)) {
      const appDateTime = DateTime.fromISO(value, {
        zone: scriptConfig('timezone'),
      })

      const localDateTime = appDateTime.toLocal()

      // Ensure consistent format
      emit('setInitialValue', appDateTime.toISO())

      let possibleEndTime = localizedTime(localDateTime.toUTC().toISO())
      endDate.value = localDateTime.toISODate()

      if (
        !(possibleEndTime === dueTime.value && endDate.value === dueDate.value)
      ) {
        endTime.value = possibleEndTime
        emit('setInitialValue', appDateTime.toISO())
      } else {
        emit('setInitialValue', appDateTime.toISODate())
      }
    } else {
      emit('setInitialValue', value)
      endDate.value = value
    }
  }
}

useGlobalEventListener('due-date-initial-value-set', ({ date, time }) => {
  dueDate.value = date
  dueTime.value = time
  setInitialValue()
})

useGlobalEventListener('update-end-time', value => (endTime.value = value))

useGlobalEventListener(
  'due-time-changed',
  event => (dueTime.value = event.newVal)
)

useGlobalEventListener('due-date-changed', event => {
  dueDate.value = event.newVal

  if (event.newVal > endDate.value) {
    endDate.value = event.newVal
  }
})

watch(UTCValue, newVal => updateModelValue(newVal))

watch(dueTime, newVal => {
  if (!newVal) {
    endTime.value = ''
  }
})

watch(endTime, (newVal, oldVal) =>
  emitGlobal('end-time-changed', {
    newVal: newVal,
    oldVal: oldVal,
  })
)

watch(endDate, (newVal, oldVal) =>
  emitGlobal('end-date-changed', {
    newVal: newVal,
    oldVal: oldVal,
  })
)
</script>

<style>
input[name='end_date-end-time'] {
  width: 116px !important;
}
</style>
