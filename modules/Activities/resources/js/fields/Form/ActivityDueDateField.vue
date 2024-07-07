<template>
  <BaseFormField
    v-slot="{ isReadonly, fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="modelValue"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      class="relative"
      :field="field"
      :field-id="fieldId"
      :validation-errors="validationErrors"
    >
      <IFormLabel
        :for="fieldId + '-due-date'"
        :class="['mb-1 block', field.hideLabel ? 'sm:hidden' : '']"
        :label="$t('activities::activity.due_date')"
        :required="field.isRequired"
      />

      <div class="flex items-center space-x-1">
        <DatePicker
          :id="fieldId + '-due-date'"
          v-model="dueDate"
          :name="field.attribute"
          :disabled="isReadonly"
          :with-icon="false"
          :required="field.isRequired"
          v-bind="field.attributes"
        />

        <IFormInputDropdown
          v-model="dueTime"
          max-height="300px"
          :items="inputDropdownItems"
          :placeholder="timeFormatForLuxon"
          :input-id="fieldId + '-due-time'"
          :class="[
            'sm:max-w-[150px]',
            {
              '!border-danger-500 ring-danger-500 focus:border-danger-500 focus:ring-danger-500':
                showDueTimeWarning,
            },
          ]"
          condensed
          @blur="maybeSetEndTimeToEmpty"
          @cleared="maybeSetEndTimeToEmpty"
        />

        <div
          class="absolute -right-3 hidden text-neutral-900 dark:text-neutral-300 md:block"
        >
          -
        </div>
      </div>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { onMounted } from 'vue'

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

const showDueTimeWarning = computed(() => endTime.value && !dueTime.value)

const inputDropdownItems = computed(() =>
  generateTimeSlots('00:00', 15, 'minutes').map(t =>
    localizedTime(DateTime.fromFormat(t, 'HH:mm').toUTC().toISO())
  )
)

const UTCValue = computed(() => {
  if (dueTime.value) {
    return utcDateTimeFromDateAndDropdownTime(
      dueDate.value,
      dueTime.value
    ).toISO()
  }

  return dueDate.value
})

/**
 * Create UTC DateTime instance from the given date and dropdown time (already formatted)
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
 * Invoke update end time event
 */
function invokeUpdateEndTimeValueEvent(value) {
  emitGlobal('update-end-time', value)
}

/**
 * If we don't have due time we will set the end time to empty
 */
async function maybeSetEndTimeToEmpty() {
  await nextTick()

  if (!dueTime.value && endTime.value) {
    invokeUpdateEndTimeValueEvent('')
  }
}

function updateModelValue(value) {
  emit('update:modelValue', value)
}

function setInitialValue() {
  let value = props.field.value

  if (!value) {
    const appDateTime = DateTime.now()
      .setZone(scriptConfig('timezone'))
      .plus({ hour: 1 })
      .startOf('hour')

    const localDateTime = appDateTime.toLocal()

    dueDate.value = localDateTime.toISODate()
    dueTime.value = localizedTime(localDateTime.toUTC().toISO())

    emit('setInitialValue', appDateTime.toISO())
  } else {
    if (hasTime(value)) {
      const appDateTime = DateTime.fromISO(value, {
        zone: scriptConfig('timezone'),
      })

      const localDateTime = appDateTime.toLocal()

      // Ensure consistent format
      emit('setInitialValue', appDateTime.toISO())
      dueDate.value = localDateTime.toISODate()
      dueTime.value = localizedTime(localDateTime.toUTC().toISO())
    } else {
      emit('setInitialValue', value)
      dueDate.value = value
    }
  }

  emitGlobal('due-date-initial-value-set', {
    date: dueDate.value,
    time: dueTime.value,
  })
}

useGlobalEventListener(
  'end-time-changed',
  event => (endTime.value = event.newVal)
)

useGlobalEventListener(
  'end-date-changed',
  event => (endDate.value = event.newVal)
)

onMounted(setInitialValue)

watch(UTCValue, newVal => updateModelValue(newVal))

watch(dueDate, (newVal, oldVal) =>
  emitGlobal('due-date-changed', {
    newVal: newVal,
    oldVal: oldVal,
  })
)

watch(dueTime, (newVal, oldVal) => {
  emitGlobal('due-time-changed', {
    newVal: newVal,
    oldVal: oldVal,
  })

  if (!endTime.value || endTime.value === dueDate.value) {
    return
  }

  let newDueDate = utcDateTimeFromDateAndDropdownTime(dueDate.value, newVal)

  let currentEndDate = utcDateTimeFromDateAndDropdownTime(
    endDate.value,
    endTime.value
  )

  let oldDueDate = utcDateTimeFromDateAndDropdownTime(dueDate.value, oldVal)

  let newEndTime = localizedTime(
    newDueDate
      .plus({ minutes: currentEndDate.diff(oldDueDate, 'minutes').minutes })
      .toISO()
  )

  invokeUpdateEndTimeValueEvent(newEndTime)
})
</script>

<style>
input[name='due_date-due-time'] {
  width: 116px !important;
}
</style>
