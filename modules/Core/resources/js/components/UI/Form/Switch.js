
/* eslint-disable vue/one-component-per-file */
import { defineComponent, h, ref, watch } from 'vue'
import {
  Switch,
  SwitchDescription,
  SwitchGroup,
  SwitchLabel,
} from '@headlessui/vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../../utils'

export const IFormSwitch = defineComponent({
  name: 'IFormSwitch',
  inheritAttrs: false,
  props: {
    modelValue: {},
    disabled: Boolean,
    value: { default: true },
    uncheckedValue: { default: false },
  },

  emits: ['update:modelValue', 'change'],

  setup(props, { emit, attrs }) {
    const enabled = ref(false)

    // Watcher to handle internal state changes
    watch(enabled, newVal => {
      const value = newVal ? props.value : props.uncheckedValue

      if (value !== props.modelValue) {
        emit('update:modelValue', value)
        emit('change', value)
      }
    })

    // Watcher for external v-model changes
    watch(
      () => props.modelValue,
      newVal => {
        enabled.value = newVal === props.value
      },
      { immediate: true }
    )

    return () =>
      h(
        Switch,
        {
          ...attrs.class,
          modelValue: enabled.value,
          'onUpdate:modelValue': val => {
            enabled.value = val
          },
          'data-slot': 'control',
          disabled: props.disabled,
          class: twMerge(
            [
              // Base
              'relative inline-flex shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',

              // Size
              'h-5 w-9',

              // Ring
              'focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2',

              // Disabled
              'disabled:pointer-events-none disabled:opacity-70',

              // Enabled/disabled state colors
              enabled.value
                ? 'bg-primary-600'
                : 'bg-neutral-200 dark:bg-neutral-700',
            ],
            attrs.class
          ),
        },
        {
          default: () => [
            h('span', {
              'aria-hidden': 'true',
              class: [
                enabled.value
                  ? 'translate-x-4 dark:bg-neutral-300'
                  : 'translate-x-0 dark:bg-neutral-400',
                'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
              ],
            }),
          ],
        }
      )
  },
})

export const IFormSwitchGroup = defineComponent({
  name: 'IFormSwitchGroup',
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
              'space-y-3 [&_[data-slot=label]]:font-normal',

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

export const IFormSwitchLabel = defineComponent({
  name: 'IFormSwitchLabel',
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

export const IFormSwitchField = defineComponent({
  name: 'IFormSwitchField',
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
              'grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1 sm:grid-cols-[1fr_auto]',

              // Disabled layout
              'has-[[data-slot=control]:disabled]:opacity-70',

              // Control layout
              '[&>[data-slot=control]]:col-start-2 [&>[data-slot=control]]:self-center',

              // Label layout
              '[&>[data-slot=label]]:col-start-1 [&>[data-slot=label]]:row-start-1 [&>[data-slot=label]]:justify-self-start',

              // Description layout
              '[&>[data-slot=description]]:col-start-1 [&>[data-slot=description]]:row-start-2',

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

export const IFormSwitchDescription = defineComponent({
  name: 'IFormSwitchDescription',
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
export const IFormSwitchPlugin = {
  install(app) {
    app.component('IFormSwitchGroup', IFormSwitchGroup)
    app.component('IFormSwitchField', IFormSwitchField)
    app.component('IFormSwitchLabel', IFormSwitchLabel)
    app.component('IFormSwitchDescription', IFormSwitchDescription)
    app.component('IFormSwitch', IFormSwitch)
  },
}
