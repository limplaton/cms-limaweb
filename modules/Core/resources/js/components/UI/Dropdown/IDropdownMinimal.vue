<template>
  <IDropdown :placement="placement" :offset="10">
    <IDropdownButton basic no-caret v-bind="$attrs">
      <Icon
        :icon="horizontal ? 'EllipsisHorizontalSolid' : 'EllipsisVerticalSolid'"
      />
    </IDropdownButton>

    <IDropdownMenu>
      <slot />
    </IDropdownMenu>
  </IDropdown>
</template>

<script setup>
import { IDropdown, IDropdownButton } from './index'

defineOptions({ inheritAttrs: false })

defineProps({
  horizontal: Boolean,
  placement: { type: String, default: 'bottom' },
})
</script>
