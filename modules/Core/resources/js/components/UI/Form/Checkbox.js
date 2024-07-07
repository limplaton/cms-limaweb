
/* eslint-disable vue/one-component-per-file */
import { computed, defineComponent, h, onMounted, onUpdated, ref } from 'vue'
import { SwitchDescription, SwitchGroup, SwitchLabel } from '@headlessui/vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../../utils'

export const IFormCheckbox = defineComponent({
  name: 'IFormCheckbox',
  inheritAttrs: false,
  props: {
    checked: [Array, String, Boolean, Number],
    value: { type: [Array, String, Boolean, Number], default: true },
    uncheckedValue: { type: [Array, String, Boolean, Number], default: false },
  },

  emits: ['update:checked', 'change'],
  setup(props, { emit, attrs }) {
    const inputRef = ref(null)

    const isChecked = computed(() => {
      if (Array.isArray(props.checked)) {
        return (
          Boolean(
            props.checked.find(value => String(value) === String(props.value))
          ) || false
        )
      }

      return props.checked == props.value
    })

    function updateModelValue(e) {
      const modelValue = props.checked
      const isInputChecked = e.target.checked
      let value

      if (Array.isArray(modelValue)) {
        value = [...modelValue]

        if (isInputChecked) {
          value.push(props.value)
        } else {
          value.splice(
            value.findIndex(value => String(value) === String(props.value)),
            1
          )
        }
      } else {
        value = isInputChecked ? props.value : props.uncheckedValue
      }

      emit('update:checked', value)
      emit('change', value)
    }

    onMounted(() => syncRelatedAttributes(inputRef.value))
    onUpdated(() => syncRelatedAttributes(inputRef.value))

    return () =>
      h('input', {
        ref: inputRef,
        type: 'checkbox',
        'data-slot': 'control',
        ...attrs,
        class: twMerge(
          [
            // Base
            'size-4 rounded-[calc(theme(borderRadius.md)-1px)] bg-transparent',

            // Border
            'border-neutral-300 dark:border-neutral-500/30',

            // Hover
            'hover:border-neutral-400/70 dark:hover:border-neutral-200/20',

            // Ring
            'focus:ring-0 focus:ring-offset-0 focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2',

            // Colors
            'text-primary-600 dark:text-primary-600',

            // Shadow
            'shadow-sm checked:shadow-[inset_0_1px_theme(colors.white/15%)] dark:shadow-none dark:checked:shadow-none',

            // Disabled
            'disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none',
          ],
          attrs.class
        ),
        checked: isChecked.value,
        value: props.value,
        onChange: updateModelValue,
      })
  },
})

export const IFormCheckboxGroup = defineComponent({
  name: 'IFormCheckboxGroup',
  inheritAttrs: false,

  setup(_, { attrs, slots }) {
    return () =>
      h(
        SwitchGroup,
        {
          as: 'div',
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

export const IFormCheckboxLabel = defineComponent({
  name: 'IFormCheckboxLabel',
  inheritAttrs: false,
  props: { text: String },
  setup(props, { attrs, slots }) {
    return () =>
      h(
        SwitchLabel,
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

export const IFormCheckboxField = defineComponent({
  name: 'IFormCheckboxField',
  inheritAttrs: false,

  setup(_, { attrs, slots }) {
    return () =>
      h(
        SwitchGroup,
        {
          as: 'div',
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

export const IFormCheckboxDescription = defineComponent({
  name: 'IFormCheckboxDescription',
  inheritAttrs: false,
  props: { text: String },
  setup(props, { attrs, slots }) {
    return () =>
      h(
        SwitchDescription,
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
export const IFormCheckboxPlugin = {
  install(app) {
    app.component('IFormCheckboxGroup', IFormCheckboxGroup)
    app.component('IFormCheckboxField', IFormCheckboxField)
    app.component('IFormCheckboxLabel', IFormCheckboxLabel)
    app.component('IFormCheckboxDescription', IFormCheckboxDescription)
    app.component('IFormCheckbox', IFormCheckbox)
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
