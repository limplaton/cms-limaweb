
/* eslint-disable vue/one-component-per-file */
import {
  computed,
  defineComponent,
  h,
  inject,
  provide,
  ref,
  toValue,
  vShow,
  withDirectives,
} from 'vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../utils'

import { IButton } from './Button'
import Icon from './Icon.vue'

const colors = {
  primary: {
    bg: 'bg-primary-50 dark:bg-primary-600/30',
    text: 'text-primary-700 dark:text-primary-100',
    heading: 'text-primary-800 dark:text-primary-100',
    icon: 'text-primary-400 dark:text-primary-300',
  },
  success: {
    bg: 'bg-success-50 dark:bg-success-600/20',
    text: 'text-success-700 dark:text-success-100',
    heading: 'text-success-800 dark:text-success-100',
    icon: 'text-success-400 dark:text-success-300',
  },
  info: {
    bg: 'bg-info-100/60 dark:bg-info-600/30',
    text: 'text-info-700 dark:text-info-100',
    heading: 'text-info-800 dark:text-info-100',
    icon: 'text-info-400 dark:text-info-300',
  },
  warning: {
    bg: 'bg-warning-100/60 dark:bg-warning-600/40',
    text: 'text-warning-700 dark:text-warning-100',
    heading: 'text-warning-800 dark:text-warning-100',
    icon: 'text-warning-400 dark:text-warning-300',
  },
  danger: {
    bg: 'bg-danger-50 dark:bg-danger-600/40',
    text: 'text-danger-700 dark:text-danger-100',
    heading: 'text-danger-800 dark:text-danger-100',
    icon: 'text-danger-400 dark:text-danger-300',
  },
}

const icons = {
  warning: 'ExclamationTriangle',
  danger: 'XCircleSolid',
  success: 'CheckCircle',
}

function colorConfig(key, color) {
  if (!Object.hasOwn(colors, toValue(color))) {
    return ''
  }

  return colors[toValue(color)][key] || ''
}

export const IAlert = defineComponent({
  name: 'IAlert',
  inheritAttrs: false,
  props: {
    show: { type: Boolean, default: true },
    icon: String,
    dismissible: Boolean,
    variant: {
      default: 'info',
      type: String,
      validator(value) {
        return Object.keys(colors).includes(value)
      },
    },
  },

  emits: ['dismissed'],

  setup(props, { slots, attrs, emit }) {
    provide(
      'currentVariant',
      computed(() => props.variant)
    )

    const isDismissed = ref(false)

    const icon = computed(
      () => props.icon || icons[props.variant] || 'InformationCircle'
    )

    const isVisible = computed(() => (!isDismissed.value ? props.show : false))

    function dismiss() {
      isDismissed.value = true
      emit('dismissed')
    }

    return () =>
      withDirectives(
        h(
          'div',
          {
            ...attrs,
            role: 'alert',
            class: twMerge(
              ['rounded-md p-4', colorConfig('bg', props.variant)],
              attrs.class
            ),
          },
          [
            h('div', { class: 'flex' }, [
              h(Icon, {
                icon: icon.value,
                class: [
                  'mt-0.5 size-5 shrink-0',
                  colorConfig('icon', props.variant),
                ],
              }),
              h('div', { class: 'ml-3' }, [
                slots.default ? slots.default({ variant: props.variant }) : '',
              ]),
              props.dismissible &&
              h('div', { class: 'ml-auto pl-3' }, [
                h('div', { class: '-my-1.5' }, [
                  h(IButton, {
                    'aria-labeL': 'Close',
                    class: 'mt-1',
                    icon: 'XSolid',
                    variant: props.variant,
                    small: true,
                    ghost: true,
                    onClick: dismiss,
                  }),
                ]),
              ]),
            ]),
          ]
        ),
        [[vShow, isVisible.value]]
      )
  },
})

export const IAlertHeading = defineComponent({
  name: 'IAlertHeading',
  inheritAttrs: false,
  props: { text: [String, Number] },
  setup(props, { slots, attrs }) {
    const currentVariant = inject('currentVariant')

    return () =>
      h(
        'h3',
        {
          ...attrs,
          class: twMerge(
            [
              'text-base/6 font-medium sm:text-sm/6',
              colorConfig('heading', currentVariant),
            ],
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

export const IAlertBody = defineComponent({
  name: 'IAlertBody',
  inheritAttrs: false,
  props: { text: [String, Number] },
  setup(props, { slots, attrs }) {
    const currentVariant = inject('currentVariant')

    return () =>
      h(
        'div',
        {
          ...attrs,
          class: twMerge(
            ['text-base/6 sm:text-sm/6', colorConfig('text', currentVariant)],
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

export const IAlertActions = defineComponent({
  name: 'IAlertActions',
  setup(_, { slots }) {
    return () =>
      h('div', { class: 'mt-4' }, [
        h(
          'div',
          { class: '-mx-2.5 -my-1.5 flex space-x-4' },
          slots.default ? slots.default() : []
        ),
      ])
  },
})

// Plugin
export const IAlertPlugin = {
  install(app) {
    app.component('IAlert', IAlert)
    app.component('IAlertBody', IAlertBody)
    app.component('IAlertHeading', IAlertHeading)
    app.component('IAlertActions', IAlertActions)
  },
}
