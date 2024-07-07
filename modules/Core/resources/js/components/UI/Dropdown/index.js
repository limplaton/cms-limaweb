
/* eslint-disable vue/one-component-per-file */
import { computed, defineComponent, h, inject, provide, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Float, FloatArrow } from '@headlessui-float/vue'
import { twMerge } from 'tailwind-merge'

import { slotNodesOr } from '../../utils'
import { IButton, IButtonLink } from '../Button'
import Icon from '../Icon.vue'

import DropdownMinimal from './IDropdownMinimal.vue'
import ExtendedDropdown from './IExtendedDropdown.vue'

export const IDropdownMinimal = DropdownMinimal
export const IExtendedDropdown = ExtendedDropdown

export const IDropdown = defineComponent({
  name: 'IDropdown',
  inheritAttrs: false,
  props: {
    arrow: { type: Boolean, default: true },
    portal: { type: Boolean, default: true },
    placement: { type: String, default: 'bottom' },
    zIndex: { type: Number, default: 1250 },
    offset: { type: [Number, Function, Object], default: 15 },
    flip: { type: [Boolean, Number], default: true },
  },

  emits: ['show', 'hide'],
  setup(props, { emit, attrs, slots }) {
    provide(
      'arrow',
      computed(() => props.arrow)
    )

    return () =>
      h(Menu, null, {
        default: ({ close }) => [
          h(
            Float,
            {
              enter: 'ease-out duration-100 overflow-y-hidden',
              enterFrom: 'opacity-0 scale-95 overflow-y-hidden',
              enterTo: 'opacity-100 scale-100 overflow-y-hidden',
              leave: 'ease-in duration-75 overflow-y-hidden',
              leaveFrom: 'opacity-100 scale-100 overflow-y-hidden',
              leaveTo: 'opacity-0 scale-95 overflow-y-hidden',
              placement: props.placement,
              arrow: props.arrow,
              offset: props.offset,
              zIndex: props.zIndex,
              flip: props.flip,
              portal: props.portal,
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
      })
  },
})

export const IDropdownButton = defineComponent({
  name: 'IDropdownButton',
  inheritAttrs: false,

  props: {
    as: { type: [String, Object], default: IButton },
    link: Boolean,
    noCaret: Boolean,
    text: [String, Number],
  },

  setup(props, { attrs, slots }) {
    return () => {
      const ButtonComponent = props.link ? IButtonLink : props.as

      return h(
        MenuButton,
        {
          as: ButtonComponent,
          ...attrs,
        },
        {
          default: () => [
            slotNodesOr(props.text, slots.default),

            !props.noCaret
              ? h(Icon, {
                icon: 'ChevronDownSolid',
                class: 'ml-auto size-5 shrink-0 sm:size-4',
              })
              : null,
          ],
        }
      )
    }
  },
})

export const IDropdownMenu = defineComponent({
  name: 'IDropdownMenu',

  setup(_, { slots }) {
    const arrow = inject('arrow')

    return () =>
      h(
        MenuItems,
        {
          class:
            'overflow-y-auto overflow-x-hidden rounded-lg border border-neutral-200 bg-white shadow-lg outline-none focus:outline-none dark:border-neutral-500/30 dark:bg-neutral-800',
        },
        {
          default: () => [
            arrow
              ? h(FloatArrow, {
                class:
                  'absolute w-5 h-5 rotate-45 border border-neutral-200 bg-white dark:border-neutral-500/30 dark:bg-neutral-800',
              })
              : null,
            h(
              'div',
              {
                class: 'relative rounded-lg bg-white p-1 dark:bg-neutral-800',
              },
              slots.default ? slots.default() : []
            ),
          ],
        }
      )
  },
})

export const IDropdownItem = defineComponent({
  name: 'IDropdownItem',
  inheritAttrs: false,
  props: {
    active: Boolean,
    condensed: Boolean,
    disabled: Boolean,
    icon: String,
    href: String,
    text: [String, Number],
    to: [Object, String],
    confirmable: Boolean,
    confirmText: { type: [String, Number], default: 'Confirm' },
  },

  emits: ['click', 'confirmed'],

  setup(props, { emit, attrs, slots }) {
    const isBeingConfirmed = ref(false)
    const linkRef = ref(null)
    const router = useRouter()

    const localHref = computed(() => {
      if (props.href) return props.href
      if (props.to) return router.resolve(props.to).href

      return '#'
    })

    function requiresConfirmation(e) {
      if (!props.confirmable) return false

      if (!isBeingConfirmed.value) {
        linkRef.value.style.minWidth = `${linkRef.value.offsetWidth}px`
        isBeingConfirmed.value = true

        linkRef.value.focus()
        e.preventDefault()
        e.stopPropagation()

        return true
      }

      emit('confirmed', e)

      isBeingConfirmed.value = false

      return false
    }

    function handleClickEvent(e, close) {
      if (props.disabled) return

      if (requiresConfirmation(e)) return

      emit('click', e)

      if (e.defaultPrevented) return

      if (props.to) {
        router.push(props.to)
      }

      if (!props.href) {
        e.preventDefault()
      }

      close()
    }

    return () =>
      h(
        MenuItem,
        {
          disabled: props.disabled,
        },
        {
          default: ({ active: headlessActive, close }) => [
            h(
              'a',
              {
                ref: linkRef,
                ...attrs,
                href: localHref.value,
                class: twMerge(
                  [
                    // Base classes and conditional classes based on props and state
                    'grid grid-cols-[auto_1fr_1.5rem_0.5rem_auto] items-center rounded-lg text-left text-base/6 focus:outline-none sm:text-sm/6',
                    '[&>[data-slot=icon]]:col-start-1 [&>[data-slot=icon]]:row-start-1 [&>[data-slot=icon]]:mr-2.5 [&>[data-slot=icon]]:size-5 sm:[&>[data-slot=icon]]:mr-2 [&>[data-slot=icon]]:sm:size-4',
                    props.disabled ? 'pointer-events-none opacity-50' : '',
                    props.condensed
                      ? 'px-2.5 py-1.5 sm:px-2 sm:py-1'
                      : 'px-3.5 py-2.5 sm:px-3 sm:py-1.5',
                    !isBeingConfirmed.value
                      ? headlessActive || props.active
                        ? 'bg-neutral-100 text-neutral-800 dark:bg-neutral-900/60 dark:text-white'
                        : 'text-neutral-800 dark:text-white'
                      : 'justify-center bg-danger-600 text-white hover:bg-danger-500 focus-visible:outline-danger-500',
                  ],
                  attrs.class
                ),
                onClick: e => handleClickEvent(e, close),
              },
              isBeingConfirmed.value
                ? props.confirmText
                : [
                  props.icon ? h(Icon, { icon: props.icon }) : null,
                  slotNodesOr(props.text, slots.default),
                ]
            ),
          ],
        }
      )
  },
})

export const IDropdownItemLabel = defineComponent({
  name: 'IDropdownItemLabel',
  inheritAttrs: false,
  props: {
    text: [String, Number],
  },

  setup(props, { attrs, slots }) {
    return () =>
      h(
        'div',
        {
          ...attrs,
          class: twMerge('col-start-2 row-start-1', attrs.class),
        },
        slotNodesOr(props.text, slots.default)
      )
  },
})

export const IDropdownItemDescription = defineComponent({
  name: 'IDropdownItemDescription',
  inheritAttrs: false,
  props: { text: [String, Number] },

  setup(props, { slots, attrs }) {
    return () => {
      return h(
        'p',
        {
          ...attrs,
          'data-slot': 'description',
          class: twMerge(
            'col-span-2 col-start-2 row-start-2 text-sm/5 text-neutral-500 dark:text-neutral-400 sm:text-xs/5',
            attrs.class
          ),
        },
        slotNodesOr(props.text, slots.default)
      )
    }
  },
})

export const IDropdownSeparator = defineComponent({
  name: 'IDropdownSeparator',
  inheritAttrs: false,

  setup(_, { attrs, slots }) {
    return () =>
      h(
        'div',
        {
          ...attrs,
          class: twMerge(
            'col-span-full mx-3.5 my-1 h-px border-0 bg-neutral-200 dark:bg-white/10 sm:mx-3',
            attrs.class
          ),
        },
        slots.default ? slots.default() : ''
      )
  },
})

// Plugin
export const IDropdownPlugin = {
  install(app) {
    app.component('IDropdown', IDropdown)
    app.component('IDropdownButton', IDropdownButton)
    app.component('IDropdownItemLabel', IDropdownItemLabel)
    app.component('IDropdownMenu', IDropdownMenu)
    app.component('IDropdownItem', IDropdownItem)
    app.component('IDropdownItemDescription', IDropdownItemDescription)
    app.component('IDropdownSeparator', IDropdownSeparator)
    app.component('IDropdownMinimal', IDropdownMinimal)
    app.component('IExtendedDropdown', IExtendedDropdown)
  },
}
