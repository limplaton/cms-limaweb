
/* eslint-disable vue/one-component-per-file */
import { computed, defineComponent, h, onMounted, onUpdated, ref } from 'vue'
import {
  RadioGroup,
  RadioGroupDescription,
  RadioGroupLabel,
} from '@headlessui/vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../../utils'

export const IFormRadio = defineComponent({
  name: 'IFormRadio',
  inheritAttrs: false,
  props: {
    modelValue: [String, Boolean, Number],
    value: { type: [String, Boolean, Number], required: true },
    name: { type: String, required: true }, // Name is required for radio field
  },

  emits: ['update:modelValue', 'change'],
  setup(props, { emit, attrs }) {
    const inputRef = ref(null)

    const isChecked = computed(
      () => parseValue(props.value) == parseValue(props.modelValue)
    )

    function parseValue(value) {
      // Convert string value 'true' and 'false' to boolean values.
      if (value === 'false') {
        value = false
      } else if (value === 'true') {
        value = true
      }

      return value
    }

    function handleChangeEvent(e) {
      let value = parseValue(e.target.value)

      emit('update:modelValue', value)
      emit('change', value)
    }

    onMounted(() => syncRelatedAttributes(inputRef.value))
    onUpdated(() => syncRelatedAttributes(inputRef.value))

    return () => {
      return h('input', {
        ref: inputRef,
        type: 'radio',
        'data-slot': 'control',
        ...attrs,
        class: twMerge(
          [
            // Base
            'size-4 bg-transparent shadow-sm',

            // Border
            'border-neutral-300 dark:border-neutral-500/30',

            // Hover
            'hover:border-neutral-400/70 dark:hover:border-neutral-200/20',

            // Ring
            'focus:ring-0 focus:ring-offset-0 focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2',

            // Colors
            'text-primary-600 dark:text-primary-600',

            // Disabled
            'disabled:pointer-events-none disabled:opacity-50',
          ],
          attrs.class
        ),
        checked: isChecked.value,
        name: props.name,
        value: props.value,
        onChange: handleChangeEvent,
      })
    }
  },
})

export const IFormRadioGroup = defineComponent({
  name: 'IFormRadioGroup',
  inheritAttrs: false,

  setup(_, { attrs, slots }) {
    return () =>
      h(
        RadioGroup,
        {
          ...attrs,
          'data-slot': 'control',
          class: twMerge(
            [
              // Basic groups
              'space-y-3',

              // With descriptions
              'has-[[data-slot=description]]:space-y-6 [&_[data-slot=label]]:has-[[data-slot=description]]:font-medium',
            ],
            attrs.class
          ),
        },
        slots
      )
  },
})

export const IFormRadioLabel = defineComponent({
  name: 'IFormRadioLabel',
  inheritAttrs: false,
  props: { text: String },
  setup(props, { attrs, slots }) {
    return () =>
      h(
        RadioGroupLabel,
        {
          ...attrs,
          'data-slot': 'label',
          class: twMerge(
            'select-none text-base/6 text-neutral-900 dark:text-white sm:text-sm/6',
            attrs.class
          ),
        },
        {
          default: () => slotNodesOr(props.text, slots.default),
        }
      )
  },
})

export const IFormRadioField = defineComponent({
  name: 'IFormRadioField',
  inheritAttrs: false,

  setup(_, { attrs, slots }) {
    return () =>
      h(
        RadioGroup,
        {
          ...attrs,
          'data-slot': 'field',
          class: twMerge(
            [
              // Base layout
              'grid grid-cols-[1.125rem_1fr] items-center gap-x-2 gap-y-1 sm:grid-cols-[1rem_1fr]',

              // Control layout
              '[&>[data-slot=control]]:col-start-1 [&>[data-slot=control]]:row-start-1 [&>[data-slot=control]]:justify-self-center',

              // Disabled layout
              'has-[[data-slot=control]:disabled]:opacity-70',

              // Label layout
              '[&>[data-slot=label]]:col-start-2 [&>[data-slot=label]]:row-start-1 [&>[data-slot=label]]:justify-self-start',

              // Description layout
              '[&>[data-slot=description]]:col-start-2 [&>[data-slot=description]]:row-start-2',

              // With description
              '[&_[data-slot=label]]:has-[[data-slot=description]]:font-medium',
            ],
            attrs.class
          ),
        },
        slots
      )
  },
})

export const IFormRadioDescription = defineComponent({
  name: 'IFormRadioDescription',
  inheritAttrs: false,
  props: { text: String },
  setup(props, { attrs, slots }) {
    return () =>
      h(
        RadioGroupDescription,
        {
          as: 'p',
          ...attrs,
          'data-slot': 'description',
          class: twMerge(
            'text-base/6 text-neutral-500 dark:text-neutral-300 sm:text-sm/6',
            attrs.class
          ),
        },
        {
          default: () => slotNodesOr(props.text, slots.default),
        }
      )
  },
})

// Plugin
export const IFormRadioPlugin = {
  install(app) {
    app.component('IFormRadioGroup', IFormRadioGroup)
    app.component('IFormRadioField', IFormRadioField)
    app.component('IFormRadioLabel', IFormRadioLabel)
    app.component('IFormRadioDescription', IFormRadioDescription)
    app.component('IFormRadio', IFormRadio)
  },
}

// Function to sync attributes with related label element
const syncRelatedAttributes = inputEl => {
  const labelEl = inputEl.nextElementSibling || inputEl.previousElementSibling

  if (
    labelEl &&
    !inputEl.getAttribute('id') &&
    labelEl.dataset.slot === 'label'
  ) {
    if (!labelEl.getAttribute('for')) {
      labelEl.setAttribute('for', labelEl.getAttribute('id'))
    }

    inputEl.setAttribute('id', labelEl.getAttribute('id'))
  }
}
