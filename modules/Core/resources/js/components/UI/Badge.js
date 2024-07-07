
/* eslint-disable vue/one-component-per-file */
import { defineComponent, h } from 'vue'
import { twMerge } from 'tailwind-merge'

import ScopedVarColorPalette from '../ScopedVarColorPalette.vue'
import { slotNodesOr } from '../utils'

import Icon from './Icon.vue'

const colors = {
  primary:
    'bg-primary-50 text-primary-700 ring-primary-700/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30',
  neutral:
    'bg-neutral-100 text-neutral-700 ring-neutral-600/10 dark:bg-neutral-400/10 dark:text-neutral-400 dark:ring-neutral-400/20',
  success:
    'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20',
  info: 'bg-info-50 text-info-700 ring-info-700/10 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30',
  warning:
    'bg-warning-50 text-warning-800 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-500 dark:ring-warning-400/20',
  danger:
    'bg-danger-50 text-danger-700 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20',
  custom:
    'bg-[rgba(var(--color-custom-50))] text-[rgba(var(--color-custom-700))] ring-[rgba(var(--color-custom-600),0.1)] dark:bg-[rgba(var(--color-custom-400),0.1)] dark:text-[rgba(var(--color-custom-300))] dark:ring-[rgba(var(--color-custom-400),0.2)]',
}

export const IBadge = defineComponent({
  name: 'IBadge',
  inheritAttrs: false,

  props: {
    text: [String, Number],
    tag: { type: String, default: 'span' },
    pill: Boolean,
    icon: String,
    color: String,
    variant: {
      default: 'neutral',
      type: String,
      validator(value) {
        return Object.keys(colors).includes(value)
      },
    },
  },

  setup(props, { attrs, slots }) {
    return () =>
      h(
        props.color ? ScopedVarColorPalette : props.tag,
        {
          ...attrs,
          'data-slot': 'badge',
          tag: props.color ? props.tag : undefined,
          color: props.color || undefined,
          name: props.color ? 'custom' : '',
          class: twMerge(
            [
              'inline-flex items-center gap-x-1.5 px-2 py-1 text-sm font-medium ring-1 ring-inset sm:text-xs',
              colors[props.color ? 'custom' : props.variant],
              props.pill
                ? 'min-h-6 min-w-6 justify-center rounded-full'
                : 'rounded-md',
            ],
            attrs.class
          ),
        },
        {
          default: () => [
            props.icon ? h(Icon, { class: 'size-4', icon: props.icon }) : null,
            slotNodesOr(props.text, slots.default),
          ],
        }
      )
  },
})

export const IBadgeButton = defineComponent({
  name: 'IBadgeButton',
  props: {
    text: [String, Number],
  },

  setup(props, { slots }) {
    return () =>
      h(
        IBadge,
        {
          type: 'button',
          tag: 'button',
          class: 'hover:opacity-80 disabled:pointer-events-none',
        },
        () => slotNodesOr(props.text, slots.default)
      )
  },
})

// Plugin
export const IBadgePlugin = {
  install(app) {
    app.component('IBadge', IBadge)
    app.component('IBadgeButton', IBadgeButton)
  },
}
