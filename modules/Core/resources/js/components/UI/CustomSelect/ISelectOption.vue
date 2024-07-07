<template>
  <li
    :id="`cs${uid}__option-${index}`"
    v-memo="[isHighlighted, isSelectable, label, swatchColor]"
    role="option"
    class="relative"
    :aria-selected="isHighlighted ? true : null"
  >
    <a
      href="#"
      :class="[
        'group block px-4 py-2 text-base/6 focus:outline-none sm:text-sm/6',
        computedClasses,
      ]"
      @click.prevent="$emit('selected')"
      @mouseover.self.passive="
        isSelectable ? $emit('typeAheadPointer', index) : null
      "
    >
      <component
        :is="swatchColor ? IBadge : 'span'"
        :color="swatchColor || undefined"
      >
        <slot :label="label">
          {{ label }}
        </slot>
      </component>
    </a>

    <slot name="option-inner" :index="index" />
  </li>
</template>

<script setup>
import { computed } from 'vue'

import { IBadge } from '../Badge'

const props = defineProps([
  'label',
  'uid',
  'index',
  'active',
  'isSelected',
  'isSelectable',
  'swatchColor',
])

defineEmits(['typeAheadPointer', 'selected'])

const isHighlighted = computed(() => props.isSelected || props.active)

const computedClasses = computed(() => ({
  'bg-neutral-100/80 text-neutral-700 hover:text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100 dark:hover:text-white':
    isHighlighted.value,
  'text-neutral-700 dark:text-neutral-100 dark:hover:text-white':
    !isHighlighted.value,
  'pointer-events-none opacity-50': !props.isSelectable,
}))
</script>
