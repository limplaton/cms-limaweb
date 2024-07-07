<template>
  <IDropdownItem
    :active="isActive"
    :icon="isDefault ? 'Star' : undefined"
    condensed
    @click="$emit('click', filterId)"
  >
    {{ name }}

    <span class="col-start-5 row-start-1 ml-auto shrink-0">
      <Icon
        v-if="canDelete"
        class="size-5 cursor-pointer sm:size-4"
        icon="Trash"
        @click="$emit('deleteRequested')"
      />
    </span>
  </IDropdownItem>
</template>

<script setup>
import { computed } from 'vue'

import { useFilterable } from '../../composables/useFilterable'

const props = defineProps({
  identifier: { type: String, required: true },
  view: { type: String, required: true },
  filterId: { type: Number, required: true },
  canDelete: { type: Boolean, required: true },
  name: { type: String, required: true },
})

defineEmits(['click', 'deleteRequested'])

const { activeFilter, currentUserDefaultFilter } = useFilterable(
  props.identifier,
  props.view
)

/**
 * Indicates whether the current filter is active
 */
const isActive = computed(
  () => activeFilter.value && activeFilter.value.id == props.filterId
)

/**
 * Indicates whether the given filter is default for the current view
 */
const isDefault = computed(() => {
  if (!currentUserDefaultFilter.value) return false

  return props.filterId == currentUserDefaultFilter.value.id
})
</script>
