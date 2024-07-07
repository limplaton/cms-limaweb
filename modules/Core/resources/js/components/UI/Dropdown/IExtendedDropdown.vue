<template>
  <div class="inline-flex space-x-1">
    <IDropdown
      :placement="placement"
      :adaptive-width="adaptiveWidth"
      :shift="shift"
    >
      <IDropdownButton
        :arrow="arrow"
        :as="IButton"
        :disabled="disabled"
        basic
      />

      <slot />
    </IDropdown>

    <IButton
      :variant="variant"
      :type="type"
      :icon="icon"
      :to="to"
      :disabled="disabled"
      :loading="loading"
      @click="$emit('click', $event)"
    >
      <slot name="button-content">
        {{ text }}
      </slot>
    </IButton>
  </div>
</template>

<script setup>
import IButton from '../Button/IButton.vue'

import { IDropdown } from './index'

defineProps({
  text: [String, Number],
  to: [Object, String],
  adaptiveWidth: Boolean,
  placement: { type: String, default: 'bottom-end' },
  arrow: { type: Boolean, default: true },
  shift: [Boolean, Number],
  disabled: Boolean,
  loading: Boolean,
  variant: { type: String, default: 'primary' },
  icon: { type: String },
  type: { type: String, default: 'button' },
})

defineEmits(['click'])
</script>
