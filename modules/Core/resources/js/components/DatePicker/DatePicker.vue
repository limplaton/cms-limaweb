<template>
  <BaseDatePicker
    v-model.string="localValue"
    :mode="mode"
    :is-required="required"
    :rules="parsedRules"
    :min-date="minDate === 'now' ? dateNow : minDate"
    :is-date="isDate"
    :is-date-time="isDateTime"
  >
    <template #default="slotProps">
      <slot
        v-bind="{
          ...slotProps,
          inputValue: localizedValue,
        }"
      >
        <div class="relative rounded-lg shadow-sm">
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
            :id="id"
            autocomplete="off"
            :class="withIcon ? 'pl-10 sm:pl-11' : ''"
            :model-value="localizedValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :name="name"
            readonly
            v-on="slotProps.inputEvents"
          />

          <IButton
            v-if="clearable"
            v-show="Boolean(localValue)"
            class="absolute right-1.5 top-1.5 sm:top-1"
            icon="XSolid"
            basic
            small
            @click="clearValues"
          />
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
  modelValue: String,
  withIcon: { default: true, type: Boolean },
  id: String,
  name: String,
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  clearable: Boolean,
  minDate: [Date, String],
  minutesInterval: { type: Number, default: 5 },
  mode: {
    type: String,
    default: 'date',
    validator: value => ['date', 'dateTime'].includes(value),
  },
})

const emit = defineEmits(['update:modelValue', 'input'])

const dateNow = new Date()

const { DateTime, localizedDate, localizedDateTime, onDifferentDay } =
  useDates()

const localValue = ref(null)

const isDateTime = computed(() => props.mode.toLowerCase() === 'datetime')
const isDate = computed(() => props.mode.toLowerCase() === 'date')

const parsedRules = computed(() => {
  if (!isDateTime.value) {
    return undefined
  }

  if (props.minDate === 'now') {
    return {
      hours: function (h, { isValid, date }) {
        if (!isValid) return false // not valid
        if (onDifferentDay(date, dateNow)) return true

        return h >= dateNow.getHours()
      },
      minutes: function (m, { isValid, date }) {
        if (!isValid) return false // not valid
        if (m % props.minutesInterval !== 0) return false
        if (onDifferentDay(date, dateNow)) return true

        return m > dateNow.getMinutes() || date.getHours() > dateNow.getHours()
      },
    }
  }

  return {
    minutes: { interval: props.minutesInterval },
  }
})

const localizedValue = computed(() =>
  localValue.value ? localizeValue(localValue.value) : ''
)

watch(localValue, newVal => {
  if (isBlank(newVal)) {
    return emitEmptyValChangeEvent()
  }

  if (isDate.value) {
    emitValChangeEvent(newVal)
  } else if (isDateTime.value) {
    emitValChangeEvent(
      DateTime.fromFormat(newVal, 'yyyy-MM-dd HH:mm:ss').toUTC().toISO()
    )
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
    return localizedDateTime(
      DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss').toISO()
    )
  } else {
    // TODO time, not yet used
    return value
  }
}

function setLocalValueFromModelValue(value) {
  if (isDateTime.value) {
    localValue.value = DateTime.fromISO(value).toFormat('yyyy-MM-dd HH:mm:ss')
  } else if (isDate.value) {
    localValue.value = value
  } else {
    // TODO time, not yet used
    localValue.value = value
  }

  return
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
      DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss', {
        zone: 'utc',
      })
        .toLocal()
        .toFormat('yyyy-MM-dd HH:mm:ss') === localValue.value
    )
  } else if (isDate.value) {
    return value === localValue.value
  } else {
    // TODO time, not yet used
  }
}

function clearValues() {
  localValue.value = null
}

function emitValChangeEvent(value) {
  emit('update:modelValue', value)
  emit('input', value)
}

function emitEmptyValChangeEvent() {
  emitValChangeEvent(null)
}
</script>
