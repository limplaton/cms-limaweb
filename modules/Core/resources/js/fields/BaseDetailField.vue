<template>
  <div
    :class="[
      'group relative rounded-md px-3 py-2',
      isRequiredAndMissingValue
        ? 'bg-danger-100 dark:bg-danger-600/40'
        : 'hover:bg-neutral-50 dark:hover:bg-neutral-800/60',
    ]"
  >
    <FieldInlineEdit
      class="hidden group-hover:block"
      :field="field"
      :resource="resource"
      :resource-name="resourceName"
      :resource-id="resourceId"
      :edit-action="editAction"
      :is-floating="isFloating"
      @updated="$emit('updated', $event)"
    >
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>
    </FieldInlineEdit>

    <div class="grid grid-cols-3 gap-4">
      <div
        :class="[
          'col-span-1 max-w-full justify-self-end truncate text-right text-base/6 hover:max-w-none hover:overflow-visible hover:whitespace-normal sm:text-sm/6 ',
          isRequiredAndMissingValue
            ? 'text-danger-800 dark:text-danger-400'
            : 'text-neutral-500 dark:text-neutral-300',
        ]"
        v-text="field.label"
      />

      <div
        :class="[
          'col-span-2 break-words text-base/6 sm:text-sm/6',
          isRequiredAndMissingValue
            ? 'text-danger-700'
            : 'text-neutral-800 dark:text-white',
        ]"
      >
        <slot :has-value="fieldHasValue" :value="field.value">
          <p v-if="fieldHasValue">
            {{ field.value }}
          </p>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { isBlank } from '@/Core/utils'

const props = defineProps([
  'field',
  'resource',
  'resourceName',
  'resourceId',
  'isFloating',
  'editAction',
])

defineEmits(['updated'])

const fieldHasValue = computed(() => !isBlank(props.field.value))

const isRequiredAndMissingValue = computed(
  () => props.field.isRequired && !fieldHasValue.value
)
</script>
