<template>
  <BaseFormField
    v-slot="{ fieldId }"
    :resource-name="resourceName"
    :field="field"
    :value="modelValue"
    :is-floating="isFloating"
  >
    <FormFieldGroup
      :field="field"
      :label="field.label"
      :field-id="fieldId"
      :validation-errors="validationErrors"
    >
      <div class="flex items-center">
        <div class="relative mr-2">
          <IButton
            v-if="field.cancelable"
            class="absolute left-1.5 top-1.5 sm:top-1"
            :icon="!cancelled ? 'XSolid' : 'Bell'"
            basic
            small
            @click="cancelReminder()"
          />

          <IFormNumericInput
            :id="fieldId"
            v-model="reminderValue"
            type="number"
            :class="field.cancelable ? '!pl-11' : ''"
            :name="field.attribute"
            :max="maxAttribute"
            :min="1"
            :disabled="cancelled"
            :precision="0"
            :placeholder="$t('core::dates.' + selectedType)"
          />
        </div>

        <div class="flex items-center space-x-2">
          <IFormSelect
            :id="fieldId + '-' + 'reminder-type'"
            v-model="selectedType"
            class="sm:flex-1"
            :disabled="cancelled"
          >
            <option
              v-for="reminderType in types"
              :key="reminderType"
              :value="reminderType"
            >
              {{ $t('core::dates.' + reminderType) }}
            </option>
          </IFormSelect>

          <IText
            class="ml-2 truncate"
            :text="$t('core::app.reminder_before_due')"
          />
        </div>
      </div>
    </FormFieldGroup>
  </BaseFormField>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import {
  determineReminderTypeBasedOnMinutes,
  determineReminderValueBasedOnMinutes,
} from '@/Core/utils'

import FormFieldGroup from '../FormFieldGroup.vue'

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

const types = ['minutes', 'hours', 'days', 'weeks']
const reminderValue = ref(scriptConfig('defaults.reminder_minutes'))
const selectedType = ref('minutes')
const cancelled = ref(false)

/**
 * Get the actual value in minutes
 */
const valueInMinutes = computed(() => {
  if (cancelled.value) {
    return null
  }

  if (selectedType.value === 'minutes') {
    return parseInt(reminderValue.value)
  } else if (selectedType.value === 'hours') {
    return parseInt(reminderValue.value) * 60
  } else if (selectedType.value === 'days') {
    return parseInt(reminderValue.value) * 1440
  } else if (selectedType.value === 'weeks') {
    return parseInt(reminderValue.value) * 10080
  }

  // Minutes, should not hit here.
  return parseInt(30)
})

/**
 * Max attribute for the field
 *
 * @return {Number}
 */
const maxAttribute = computed(() => {
  if (selectedType.value === 'minutes') {
    return 59
  } else if (selectedType.value === 'hours') {
    return 23
  } else if (selectedType.value === 'days') {
    return 6
  }

  // For weeks, as Google allow max 4 weeks reminder
  return 4
})

watch(valueInMinutes, newVal => {
  updateModelValue(newVal)
})

/**
 * Set/toggle the no reminder option
 */
function cancelReminder(force) {
  cancelled.value = force === undefined ? !cancelled.value : force
  reminderValue.value = scriptConfig('defaults.reminder_minutes')
  selectedType.value = 'minutes'
}

/*
 * Parse the initial value for the field
 */
function parseInitialValue() {
  if (props.field.value) {
    reminderValue.value = determineReminderValueBasedOnMinutes(
      props.field.value
    )

    selectedType.value = determineReminderTypeBasedOnMinutes(props.field.value)

    return props.field.value
  } else if (props.field.value === null && props.field.cancelable) {
    cancelReminder()
  } else {
    return reminderValue.value
  }
}

function updateModelValue(value) {
  emit('update:modelValue', value)
}

function setInitialValue() {
  emit('setInitialValue', parseInitialValue() || null)
}

setInitialValue()
</script>
