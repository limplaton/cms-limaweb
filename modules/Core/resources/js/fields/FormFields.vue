<template>
  <div ref="wrapperRef">
    <div class="grid grid-cols-12 gap-x-4">
      <div
        v-for="field in iterableFields"
        :key="field.attribute"
        :class="
          field.width === 'half' ? 'col-span-12 sm:col-span-6' : 'col-span-12'
        "
      >
        <slot :name="`before-${field.attribute}-field`" :field="field" />

        <div
          :class="
            field.displayNone || field.hidden || (collapsed && field.collapsed)
              ? 'hidden'
              : ''
          "
        >
          <component
            :is="field.formComponent"
            :field="field"
            :resource-id="resourceId"
            :resource-name="resourceName"
            :is-floating="isFloating"
            :validation-errors="groupedValidationErrors[field.attribute]"
            :model-value="form[field.attribute]"
            @update:model-value="
              $emit('updateFieldValue', {
                attribute: field.attribute,
                value: $event,
              })
            "
            @set-initial-value="
              $emit('setInitialValue', {
                attribute: field.attribute,
                value: $event,
              })
            "
          />
        </div>

        <slot :name="`after-${field.attribute}-field`" :field="field" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useTimeoutFn } from '@vueuse/core'
import castArray from 'lodash/castArray'

const props = defineProps({
  fields: { required: true, type: Array },
  form: { required: true, type: Object },

  collapsed: Boolean,

  resourceId: [String, Number],
  resourceName: String,

  except: [Array, String],
  only: [Array, String],

  isFloating: Boolean,
  focusFirst: Boolean,
})

defineEmits(['updateFieldValue', 'setInitialValue'])

const wrapperRef = ref(null)

const only = computed(() => (props.only ? castArray(props.only) : []))
const except = computed(() => (props.except ? castArray(props.except) : []))

const iterableFields = computed(() => {
  if (!props.fields) {
    return []
  }

  if (props.only) {
    return props.fields.filter(
      field => only.value.indexOf(field.attribute) > -1
    )
  } else if (props.except) {
    return props.fields.filter(
      field => except.value.indexOf(field.attribute) === -1
    )
  }

  return props.fields
})

const groupedValidationErrors = computed(() => props.form.errors.groupByField())

function focusToFirstFocusableElement() {
  const focusAbleInputs = [
    'date',
    'datetime-local',
    'email',
    'file',
    'month',
    'number',
    'password',
    'range',
    'search',
    'tel',
    'text',
    'time',
    'url',
    'week',
  ]

  const input = wrapperRef.value.querySelector('div:first-child input')
  const textarea = wrapperRef.value.querySelector('div:first-child textarea')

  if (input && focusAbleInputs.indexOf(input.getAttribute('type')) > -1) {
    input.focus()
  } else if (textarea) {
    textarea.focus()
  }
}

if (props.focusFirst) {
  onMounted(() => {
    useTimeoutFn(focusToFirstFocusableElement, 600)
  })
}
</script>
