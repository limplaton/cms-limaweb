
/* eslint-disable vue/one-component-per-file */
import {
  defineComponent,
  h,
  inject,
  mergeProps,
  provide,
  ref,
  toRef,
} from 'vue'
import { twMerge } from 'tailwind-merge'

import { IDropdownItem, IDropdownMinimal } from './Dropdown'

export const ITableOuter = defineComponent({
  name: 'ITableOuter',
  inheritAttrs: false,
  setup(_, { attrs, slots }) {
    return () =>
      h(
        'div',
        {
          ...attrs,
          class: twMerge(
            'overflow-hidden rounded-lg border border-neutral-900/10 bg-white dark:border-white/10 dark:bg-neutral-900',
            attrs.class
          ),
        },
        slots.default ? slots.default() : []
      )
  },
})

export const ITable = defineComponent({
  name: 'ITable',
  inheritAttrs: false,
  props: {
    maxHeight: String,
    condensed: Boolean,
    fixedLayout: Boolean,
    id: String,
    bleed: Boolean,
    grid: Boolean,
  },

  setup(props, { slots, attrs, expose }) {
    const wrapperRef = ref(null)
    const tableRef = ref(null)

    provide('condensed', toRef(props, 'condensed'))
    provide('bleed', toRef(props, 'bleed'))
    provide('grid', toRef(props, 'grid'))

    expose({ $el: tableRef, $wrapperEl: wrapperRef })

    return () => {
      return h('div', { class: 'flow-root' }, [
        h(
          'div',
          {
            ref: wrapperRef,
            ...attrs,
            class: twMerge(
              '-mx-[--gutter] touch-auto overflow-x-auto whitespace-nowrap',
              attrs.class
            ),
            style: mergeProps(attrs.style || {}, {
              maxHeight: props.maxHeight,
            }),
          },
          [
            h(
              'div',
              {
                class: [
                  'inline-block w-full min-w-full align-middle',
                  !props.bleed ? 'sm:px-[--gutter]' : '',
                ],
              },
              [
                h(
                  'table',
                  {
                    ref: tableRef,
                    id: props.id,
                    class: [
                      'relative w-full min-w-full text-left text-sm/6 text-neutral-700 dark:text-neutral-200',
                      props.fixedLayout ? 'table-fixed' : '',
                    ],
                  },
                  slots.default ? slots.default() : []
                ),
              ]
            ),
          ]
        ),
      ])
    }
  },
})

export const ITableRow = defineComponent({
  name: 'ITableRow',
  setup(_, { slots }) {
    return () => h('tr', null, slots.default ? slots.default() : [])
  },
})

export const ITableHead = defineComponent({
  name: 'ITableHead',
  inheritAttrs: false,
  setup(_, { attrs, slots }) {
    return () =>
      h(
        'thead',
        {
          ...attrs,
          class: twMerge('text-neutral-500 dark:text-neutral-400', attrs.class),
        },
        slots.default ? slots.default() : []
      )
  },
})

export const ITableHeader = defineComponent({
  name: 'ITableHeader',
  inheritAttrs: false,
  setup(_, { attrs, slots, expose }) {
    const bleed = inject('bleed')
    const grid = inject('grid')

    const thRef = ref(null)

    expose({ $el: thRef })

    return () =>
      h(
        'th',
        {
          ref: thRef,
          ...attrs,
          class: twMerge(
            [
              'border-b border-b-neutral-900/10 px-4 py-2 font-medium first:pl-[var(--gutter,theme(spacing.2))] last:pr-[var(--gutter,theme(spacing.2))] dark:border-b-white/10',
              grid.value &&
              'border-l border-l-neutral-900/5 first:border-l-0 dark:border-l-white/5',
              !bleed.value && 'sm:first:pl-2 sm:last:pr-2',
            ],
            attrs.class
          ),
        },
        slots.default ? slots.default() : []
      )
  },
})

export const ITableBody = defineComponent({
  name: 'ITableBody',
  setup(_, { slots }) {
    return () => h('tbody', null, slots.default ? slots.default() : [])
  },
})

export const ITableCell = defineComponent({
  name: 'ITableCell',
  inheritAttrs: false,

  setup(_, { attrs, slots, expose }) {
    const condensed = inject('condensed')
    const bleed = inject('bleed')
    const grid = inject('grid')

    const tdRef = ref(null)

    expose({ $el: tdRef })

    return () =>
      h(
        'td',
        {
          ref: tdRef,
          ...attrs,
          class: twMerge(
            [
              'relative border-b border-neutral-900/5 px-4 first:pl-[var(--gutter,theme(spacing.2))] last:pr-[var(--gutter,theme(spacing.2))] dark:border-white/5',
              grid.value &&
              'border-l border-l-neutral-900/5 first:border-l-0 dark:border-l-white/5',
              condensed.value ? 'py-1.5' : 'py-3',
              !bleed.value && 'sm:first:pl-2 sm:last:pr-2',
            ],
            attrs.class
          ),
        },
        slots.default ? slots.default() : []
      )
  },
})

export const ITableRowActions = defineComponent({
  name: 'ITableRowActions',

  setup(_, { slots }) {
    return () =>
      h(
        'div',
        {
          class:
            'absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 transform',
        },
        [
          h(
            IDropdownMinimal,
            {
              placement: 'left-center',
              horizontal: true,
              small: true,
            },
            slots
          ),
        ]
      )
  },
})

export const ITableRowAction = defineComponent({
  name: 'ITableRowAction',

  setup(_, { slots }) {
    return () => h(IDropdownItem, null, slots)
  },
})

// Plugin
export const ITablePlugin = {
  install(app) {
    app.component('ITable', ITable)
    app.component('ITableCell', ITableCell)
    app.component('ITableOuter', ITableOuter)
    app.component('ITableRow', ITableRow)
    app.component('ITableHeader', ITableHeader)
    app.component('ITableBody', ITableBody)
    app.component('ITableHead', ITableHead)
    app.component('ITableRowActions', ITableRowActions)
    app.component('ITableRowAction', ITableRowAction)
  },
}
