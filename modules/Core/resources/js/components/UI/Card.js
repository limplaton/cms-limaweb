
/* eslint-disable vue/one-component-per-file */
import { defineComponent, h } from 'vue'
import { twMerge } from 'tailwind-merge'

import IOverlay from './IOverlay.vue'
import { ITextDisplay } from './Text'

export const ICard = defineComponent({
  name: 'ICard',
  inheritAttrs: false,
  props: {
    as: { type: String, default: 'div' },
    overlay: Boolean,
  },

  setup(props, { slots, attrs }) {
    return () => {
      return h(IOverlay, { show: props.overlay }, () =>
        h(
          props.as,
          {
            ...attrs,
            class: twMerge(
              [
                // Base
                'overflow-hidden rounded-lg bg-white shadow-sm dark:bg-neutral-900',

                // Border
                'border border-neutral-200 dark:border-neutral-500/30',

                // Header
                '[&>[data-slot=header]]:mb-0 [&>[data-slot=header]]:border-b [&>[data-slot=header]]:border-neutral-200 [&>[data-slot=header]]:px-6 [&>[data-slot=header]]:py-4 [&>[data-slot=header]]:dark:border-neutral-500/30',
              ],
              attrs.class
            ),
          },
          slots.default ? slots.default() : []
        )
      )
    }
  },
})

export const ICardHeader = defineComponent({
  name: 'ICardHeader',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          'data-slot': 'header',
          class: twMerge(
            [
              // Base
              'mb-3 flex flex-wrap items-center gap-x-2 gap-y-3 sm:flex-nowrap',

              // Actions
              'has-[[data-slot=actions]]:justify-between',
            ],
            attrs.class
          ),
        },
        slots.default ? slots.default() : []
      )
    }
  },
})

export const ICardHeading = defineComponent({
  name: 'ICardHeading',

  setup(_, { slots }) {
    return () => {
      return h(
        ITextDisplay,
        {
          'data-slot': 'heading',
        },
        slots
      )
    }
  },
})

export const ICardActions = defineComponent({
  name: 'ICardActions',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          'data-slot': 'actions',
          class: twMerge('flex items-center gap-x-1.5', attrs.class),
        },
        slots.default ? slots.default() : []
      )
    }
  },
})

export const ICardBody = defineComponent({
  name: 'ICardBody',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          class: twMerge('px-6 py-5', attrs.class),
        },
        slots.default ? slots.default() : []
      )
    }
  },
})

export const ICardFooter = defineComponent({
  name: 'ICardFooter',
  inheritAttrs: false,

  setup(_, { slots, attrs }) {
    return () => {
      return h(
        'div',
        {
          ...attrs,
          class: twMerge(
            [
              // Background
              'bg-neutral-50 dark:bg-neutral-800/90',

              // Sizing
              'px-6 py-4',

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
export const ICardPlugin = {
  install(app) {
    app.component('ICard', ICard)
    app.component('ICardHeader', ICardHeader)
    app.component('ICardHeading', ICardHeading)
    app.component('ICardActions', ICardActions)
    app.component('ICardBody', ICardBody)
    app.component('ICardFooter', ICardFooter)
  },
}
