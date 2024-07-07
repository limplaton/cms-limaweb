
/* eslint-disable vue/one-component-per-file */
import { defineComponent, h } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { Float, FloatContent, FloatReference } from '@headlessui-float/vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../utils'

import { IButton, IButtonLink } from './Button'
import Icon from './Icon.vue'
import { ITextDark } from './Text'

export const IPopover = defineComponent({
  name: 'IPopover',
  inheritAttrs: false,
  props: {
    portal: { type: Boolean, default: true },
    placement: { type: String, default: 'bottom' },
    zIndex: { type: Number, default: 1250 },
    offset: { type: [Number, Function, Object], default: 10 },
    flip: { type: [Boolean, Number], default: true },
  },

  emits: ['show', 'hide'],

  setup(props, { attrs, slots, emit }) {
    return () => {
      return h(
        Popover,
        {
          as: 'template',
        },
        {
          default: ({ close }) => [
            h(
              Float,
              {
                enter: 'ease-out duration-100',
                'enter-from': 'opacity-0 scale-95',
                'enter-to': 'opacity-100 scale-100',
                leave: 'ease-in duration-75',
                'leave-from': 'opacity-100 scale-100',
                'leave-to': 'opacity-0 scale-95',
                offset: props.offset,
                'z-index': props.zIndex,
                placement: props.placement,
                portal: props.portal,
                flip: props.flip,
                composable: true,
                ...attrs,
                onShow: () => emit('show'),
                onHide: () => emit('hide'),
              },
              {
                default: () =>
                  slots.default ? slots.default({ hide: close }) : [],
              }
            ),
          ],
        }
      )
    }
  },
})

export const IPopoverButton = defineComponent({
  name: 'IPopoverButton',
  inheritAttrs: false,

  props: {
    as: { type: [String, Object], default: IButton },
    link: Boolean,
    caret: { type: Boolean },
    text: [String, Number],
  },

  setup(props, { attrs, slots }) {
    return () => {
      const ButtonComponent = props.link ? IButtonLink : props.as

      return h(FloatReference, null, () =>
        h(
          PopoverButton,
          {
            as: ButtonComponent,
            ...attrs,
          },
          {
            default: () => [
              slotNodesOr(props.text, slots.default),

              props.caret
                ? h(Icon, {
                  icon: 'ChevronDownSolid',
                  class: 'ml-auto size-5 shrink-0 sm:size-4',
                })
                : null,
            ],
          }
        )
      )
    }
  },
})

export const IPopoverHeader = defineComponent({
  name: 'IPopoverHeader',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          class: twMerge(
            [
              // Sizing
              'px-5 py-2.5',

              // Border
              'border-b border-neutral-200 dark:border-neutral-500/30',
            ],
            attrs.class
          ),
        },
        slots.default ? slots.default() : []
      )
    }
  },
})

export const IPopoverHeading = defineComponent({
  name: 'IPopoverHeading',

  setup(_, { slots }) {
    return () => {
      return h(ITextDark, { class: 'font-medium' }, slots)
    }
  },
})

export const IPopoverPanel = defineComponent({
  name: 'IPopoverPanel',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(FloatContent, null, {
        default: () => [
          h(
            PopoverPanel,
            {
              ...attrs,
              class: twMerge(
                [
                  // Base
                  'overflow-hidden rounded-lg bg-white shadow-lg outline-none focus:outline-none dark:bg-neutral-800',

                  // Background
                  'border border-neutral-200 dark:border-neutral-500/30',
                ],
                attrs.class
              ),
            },
            slots
          ),
        ],
      })
    }
  },
})

export const IPopoverBody = defineComponent({
  name: 'IPopoverBody',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          class: twMerge('px-4 py-3 sm:p-5', attrs.class),
        },
        slots.default ? slots.default() : []
      )
    }
  },
})

export const IPopoverFooter = defineComponent({
  name: 'IPopoverFooter',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          class: twMerge(
            [
              // Sizing
              'px-5 py-2.5',

              // Border
              'border-t border-neutral-200 dark:border-neutral-500/30',
            ],
            attrs.class
          ),
        },
        slots.default ? slots.default() : []
      )
    }
  },
})

// Plugin
export const IPopoverPlugin = {
  install(app) {
    app.component('IPopover', IPopover)
    app.component('IPopoverButton', IPopoverButton)
    app.component('IPopoverPanel', IPopoverPanel)
    app.component('IPopoverHeader', IPopoverHeader)
    app.component('IPopoverHeading', IPopoverHeading)
    app.component('IPopoverBody', IPopoverBody)
    app.component('IPopoverFooter', IPopoverFooter)
  },
}
