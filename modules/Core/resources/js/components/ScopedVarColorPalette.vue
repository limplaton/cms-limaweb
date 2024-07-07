<template>
  <component
    :is="tag"
    :style="{
      [`--color-${name}-50`]: parsed[50],
      [`--color-${name}-100`]: parsed[100],
      [`--color-${name}-200`]: parsed[200],
      [`--color-${name}-300`]: parsed[300],
      [`--color-${name}-400`]: parsed[400],
      [`--color-${name}-500`]: parsed[500],
      [`--color-${name}-600`]: parsed[600],
      [`--color-${name}-700`]: parsed[700],
      [`--color-${name}-800`]: parsed[800],
      [`--color-${name}-900`]: parsed[900],
    }"
  >
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'

import { hexToTailwindColor } from '@/Core/utils'

import { DEFAULT_PALETTE_CONFIG, DEFAULT_STOP } from '@/ThemeStyle/constants'
import { createSwatches } from '@/ThemeStyle/createSwatches'

const props = defineProps({
  color: { type: String, required: true },
  tag: { type: String, default: 'span' },
  name: { type: String, default: 'custom' },
})

const excludedShades = [0, 950, 1000]

const parsed = computed(() => {
  const hex = props.color

  const paletteConfig = Object.assign({}, DEFAULT_PALETTE_CONFIG, {
    value: hex.substring(1),
    valueStop: DEFAULT_STOP,
    lMax: DEFAULT_PALETTE_CONFIG.lMax,
    lMin: DEFAULT_PALETTE_CONFIG.lMin,
  })

  const palette = createSwatches(paletteConfig).filter(
    swatch => !excludedShades.includes(swatch.stop)
  )

  return palette.reduce((accumulator, color) => {
    accumulator[color.stop] = hexToTailwindColor(color.hex)

    return accumulator
  }, {})
})
</script>
