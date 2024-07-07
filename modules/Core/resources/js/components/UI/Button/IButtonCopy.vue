<template>
  <component
    :is="as"
    :basic="as === IButton ? true : undefined"
    :icon="as === IButton ? $attrs.icon || 'Clipboard' : undefined"
    @click="performCopy"
  >
    <slot />
  </component>
</template>

<script setup>
import { toRef } from 'vue'
import { useClipboard } from '@vueuse/core'

import IButton from './IButton.vue'

const props = defineProps({
  text: [String, Number],
  as: { type: [String, Object], default: IButton },
  successMessage: {
    type: String,
    default: 'Text copied to clipboard.',
  },
})

const { copy } = useClipboard({
  source: toRef(props, 'text'),
  legacy: true,
})

function performCopy() {
  copy()

  Innoclapps.info(props.successMessage)
}
</script>
