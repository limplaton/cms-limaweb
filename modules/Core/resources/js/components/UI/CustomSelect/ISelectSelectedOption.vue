<template>
  <component
    :is="
      (typeof option === 'object' && option.swatch_color) || multiple
        ? IBadge
        : 'span'
    "
    v-bind="attributes"
  >
    <slot :label="label">
      {{ label }}
    </slot>

    <ISelectSelectedOptionDeselectButton
      v-if="multiple && !disabled"
      :deselect="deselect"
      :label="label"
      :option="option"
    />
  </component>
</template>

<script setup>
import { computed } from 'vue'

import { IBadge } from '../Badge'

import ISelectSelectedOptionDeselectButton from './ISelectSelectedOptionDeselectButton.vue'

const props = defineProps([
  'option',
  'label',
  'multiple',
  'searching',
  'disabled',
  'deselect',
  'simple',
])

const attributes = computed(() => {
  const { option, multiple, disabled, simple, searching } = props

  const isObject = typeof option === 'object'

  let classList = ['gap-x-2']

  let attributes = {
    class: '',
    ...(isObject && option.swatch_color && { color: option.swatch_color }),
  }

  if (multiple || (isObject && option.swatch_color)) {
    if (!props.simple) {
      classList.push('!py-0.5', '!text-sm', '-my-px sm:-my-0')
    }

    if (multiple) {
      classList.push('hover:opacity-90')
    }

    if (isObject && !option.swatch_color) {
      attributes.variant = 'neutral'
    }
  } else {
    classList.push('dark:text-white')
  }

  if ((disabled && !simple) || searching) {
    classList.push('opacity-60', 'dark:opacity-80')
  }

  attributes.class = classList.join(' ')

  return attributes
})
</script>
