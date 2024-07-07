
/* eslint-disable vue/one-component-per-file */
import { defineComponent, h } from 'vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../utils'

export const IText = defineComponent({
  name: 'IText',
  inheritAttrs: false,
  props: { text: [String, Number], as: { type: String, default: 'p' } },

  setup(props, { attrs, slots }) {
    return () =>
      h(
        props.as,
        {
          ...attrs,
          class: twMerge(
            'text-neutral-500 dark:text-neutral-300',
            'text-base/6 sm:text-sm/6',
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

export const ITextBlock = defineComponent({
  name: 'ITextBlock',
  inheritAttrs: false,
  props: { text: [String, Number] },

  setup(props, { attrs, slots }) {
    return () => h(IText, { as: 'div', ...attrs, text: props.text }, slots)
  },
})

export const ITextDark = defineComponent({
  name: 'ITextDark',
  inheritAttrs: false,
  props: { text: [String, Number], as: { type: String, default: 'p' } },

  setup(props, { attrs, slots }) {
    return () =>
      h(
        props.as,
        {
          ...attrs,
          class: twMerge(
            'text-neutral-800 dark:text-white',
            'text-base/6 sm:text-sm/6',
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

export const ITextBlockDark = defineComponent({
  name: 'ITextBlockDark',
  inheritAttrs: false,
  props: { text: [String, Number] },

  setup(props, { attrs, slots }) {
    return () => h(ITextDark, { as: 'div', ...attrs, text: props.text }, slots)
  },
})

export const ITextDisplay = defineComponent({
  name: 'ITextDisplay',
  inheritAttrs: false,
  props: { text: [String, Number], as: { type: String, default: 'h3' } },

  setup(props, { attrs, slots }) {
    return () =>
      h(
        props.as,
        {
          ...attrs,
          class: twMerge(
            'text-lg/6 font-semibold text-neutral-900 dark:text-white sm:text-base/6',
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

export const ITextSmall = defineComponent({
  name: 'ITextSmall',
  inheritAttrs: false,
  props: { text: [String, Number], as: { type: String, default: 'p' } },

  setup(props, { attrs, slots }) {
    return () =>
      h(
        props.as,
        {
          ...attrs,
          class: twMerge(
            'text-sm/5 text-neutral-500 dark:text-neutral-300 sm:text-xs/5',
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

// Plugin
export const ITextPlugin = {
  install(app) {
    app.component('IText', IText)
    app.component('ITextBlock', ITextBlock)
    app.component('ITextBlockDark', ITextBlockDark)
    app.component('ITextDark', ITextDark)
    app.component('ITextDisplay', ITextDisplay)
    app.component('ITextSmall', ITextSmall)
  },
}
