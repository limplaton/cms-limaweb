<template>
  <BaseDatePicker
    v-model.string.range="localValue"
    :mode="mode"
    :is-required="required"
    :rules="timeRules"
    :is-date="isDate"
    :is-date-time="isDateTime"
  >
    <template #default="slotProps">
      <slot
        v-bind="{
          ...slotProps,
          inputValue: localizedRangeValue,
        }"
      >
        <div class="flex flex-col items-center justify-start sm:flex-row">
          <div class="relative w-full grow rounded-lg shadow-sm">
            <div
              v-if="withIcon"
              class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
            >
              <Icon
                icon="Calendar"
                class="size-5 text-neutral-500 dark:text-neutral-300"
              />
            </div>

            <IFormInput
              :id="id + '-' + rangeKeys.start"
              autocomplete="off"
              class="pl-10 sm:pl-11"
              :model-value="localizedValueRangeStart"
              :placeholder="placeholder"
              :disabled="disabled"
              :name="name + '-' + rangeKeys.start"
              readonly
              v-on="slotProps.inputEvents[rangeKeys.start]"
            />
          </div>

          <span class="m-2 shrink-0">
            <Icon icon="ArrowRight" class="size-4 text-neutral-600" />
          </span>

          <div class="relative w-full grow rounded-lg shadow-sm">
            <div
              v-if="withIcon"
              class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
            >
              <Icon
                icon="Calendar"
                class="size-5 text-neutral-400 dark:text-neutral-300"
              />
            </div>

            <IFormInput
              :id="id + '-' + rangeKeys.end"
              autocomplete="off"
              class="pl-10 sm:pl-11"
              :model-value="localizedValueRangeEnd"
              :placeholder="placeholder"
              :disabled="disabled"
              :name="name + '-' + rangeKeys.end"
              readonly
              v-on="slotProps.inputEvents[rangeKeys.end]"
            />

            <IButton
              v-if="clearable"
              v-show="Boolean(rangeStart) && Boolean(rangeEnd)"
              class="absolute right-1.5 top-1.5 sm:top-1"
              icon="XSolid"
              basic
              small
              @click="clearValues"
            />
          </div>
        </div>
      </slot>
    </template>
  </BaseDatePicker>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

import { useDates } from '@/Core/composables/useDates'
import { isBlank } from '@/Core/utils'

import BaseDatePicker from './BaseDatePicker.vue'

const props = defineProps({
  modelValue: Object,
  withIcon: { default: true, type: Boolean },
  id: String,
  name: String,
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  clearable: Boolean,
  minutesInterval: { type: Number, default: 5 },
  rangeKeys: { type: Object, default: () => ({ start: 'start', end: 'end' }) },
  mode: {
    type: String,
    default: 'date',
    validator: value => ['date', 'dateTime'].includes(value),
  },
})

const emit = defineEmits(['update:modelValue', 'input'])

const { DateTime, localizedDate, localizedDateTime } = useDates()

const localValue = ref(null)

const timeRules = ref([
  {
    minutes: { interval: props.minutesInterval },
  },
  {
    minutes: { interval: props.minutesInterval },
  },
])

const isDateTime = computed(() => props.mode.toLowerCase() === 'datetime')
const isDate = computed(() => props.mode.toLowerCase() === 'date')

const localizedRangeValue = computed(() => {
  return {
    [props.rangeKeys.start]: localizedValueRangeStart.value,
    [props.rangeKeys.end]: localizedValueRangeEnd.value,
  }
})

const rangeStart = computed(() => localValue.value[props.rangeKeys.start])
const rangeEnd = computed(() => localValue.value[props.rangeKeys.end])

const localizedValueRangeStart = computed(() =>
  rangeStart.value ? localizeValue(rangeStart.value) : ''
)

const localizedValueRangeEnd = computed(() =>
  rangeEnd.value ? localizeValue(rangeEnd.value) : ''
)

watch(localValue, newVal => {
  if (isBlank(newVal)) {
    return emitEmptyValChangeEvent()
  }

  if (isDate.value) {
    emitValChangeEvent(newVal)
  } else if (isDateTime.value) {
    let start = newVal[props.rangeKeys.start]
    let end = newVal[props.rangeKeys.end]

    emitValChangeEvent({
      [props.rangeKeys.start]: dateTimeFromLocal(start).toUTC().toISO(),
      [props.rangeKeys.end]: dateTimeFromLocal(end).toUTC().toISO(),
    })
  } else {
    // TODO time, not yet used
    emitValChangeEvent(newVal)
  }
})

watch(
  () => props.modelValue,
  newVal => {
    if (isEqualToLocalValue(newVal)) return

    setLocalValueFromModelValue(newVal)
  },
  { immediate: true, deep: true }
)

function localizeValue(value) {
  if (isDate.value) {
    return localizedDate(DateTime.fromFormat(value, 'yyyy-MM-dd').toISODate())
  } else if (isDateTime.value) {
    return localizedDateTime(dateTimeFromLocal(value).toISO())
  } else {
    // TODO time, not yet used
    return value
  }
}

function setLocalValueFromModelValue(value) {
  let start = value[props.rangeKeys.start]
  let end = value[props.rangeKeys.end]

  if (isDate.value) {
    localValue.value = {
      [props.rangeKeys.start]: start,
      [props.rangeKeys.end]: end,
    }
  } else if (isDateTime.value) {
    localValue.value = {
      [props.rangeKeys.start]: DateTime.fromISO(start).toFormat(
        'yyyy-MM-dd HH:mm:ss'
      ),
      [props.rangeKeys.end]: DateTime.fromISO(end).toFormat(
        'yyyy-MM-dd HH:mm:ss'
      ),
    }
  } else {
    // TODO time, not yet used
    localValue.value = value
  }
}

function isEqualToLocalValue(value) {
  if (
    (isBlank(value) && isBlank(localValue.value)) ||
    value == localValue.value
  ) {
    return true
  }

  if ((!localValue.value && value) || (!value && localValue.value)) {
    return false
  }

  if (isDateTime.value) {
    return (
      fromUtcToLocal(value[props.rangeKeys.start]) === rangeStart.value &&
      fromUtcToLocal(value[props.rangeKeys.end]) === rangeEnd.value
    )
  } else if (isDate.value) {
    return (
      value[props.rangeKeys.start] === rangeStart.value &&
      value[props.rangeKeys.end] === rangeEnd.value
    )
  } else {
    // TODO time, not yet used
  }
}

function fromUtcToLocal(value) {
  return DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss', {
    zone: 'utc',
  })
    .toLocal()
    .toFormat('yyyy-MM-dd HH:mm:ss')
}

function dateTimeFromLocal(value) {
  return DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss')
}

function clearValues() {
  localValue.value[props.rangeKeys.start] = null
  localValue.value[props.rangeKeys.end] = null
}

function emitValChangeEvent(value) {
  emit('update:modelValue', value)
  emit('input', value)
}

function emitEmptyValChangeEvent() {
  emitValChangeEvent({
    [props.rangeKeys.start]: null,
    [props.rangeKeys.end]: null,
  })
}
</script>
