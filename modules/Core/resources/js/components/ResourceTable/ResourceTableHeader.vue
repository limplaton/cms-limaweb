<template>
  <ITableHeader
    :class="[
      'group/th relative bg-neutral-50 dark:bg-neutral-900 dark:before:absolute dark:before:inset-0 dark:before:-z-10 dark:before:bg-neutral-500/10',
      isPrimary || isActionsColumn ? 'z-30' : 'z-20',
      {
        'draggable-exclude lg:sticky lg:left-0': isPrimary,
        'table-actions-column !px-2 lg:sticky lg:right-0': isActionsColumn,
        'text-left': align === 'left',
        'text-center': align === 'center',
        'text-right': align === 'right',
        'cursor-pointer hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-700/60 dark:hover:text-neutral-200':
          hasMenu,
      },
    ]"
    :style="{
      maxWidth: isActionsColumn ? width : undefined,
    }"
  >
    <ResourceTableHeaderMenu
      v-if="hasMenu"
      :attribute="attribute"
      :is-sortable="isSortable"
      :is-primary="isPrimary"
      :customizeable="customizeable"
      :label="label"
      :wrap="wrap"
      @updated="$emit('updated', $event)"
      @sort-asc="$emit('sortAsc', $event)"
      @sort-desc="$emit('sortDesc', $event)"
    />

    <ActionColumnSeparator v-if="isActionsColumn" v-once />

    <PrimaryColumnSeparator v-else-if="isPrimary" v-once />

    <CheckboxSeparator
      v-if="isSelectable"
      v-once
      class="z-20"
      :condensed="condensed"
    />

    <div v-if="!isActionsColumn" class="inline-flex items-center">
      <IFormCheckbox
        v-if="isSelectable"
        :class="[condensed ? '-ml-2' : '', 'mr-2']"
        :indeterminate="indeterminate"
        :checked="indeterminate || allRowsSelected"
        @change="$emit('checkboxChanged', indeterminate)"
      />

      <div
        :class="[
          'inline-flex items-center',
          isSelectable ? 'mt-px' : '',
          isSelectable ? (condensed ? 'ml-4' : 'ml-6') : '',
        ]"
      >
        <span
          :class="['truncate', isOrdered ? 'mr-2' : '']"
          :style="{
            maxWidth: `${parseInt(width, 10) - totalXMargin}px`,
          }"
        >
          <slot>{{ label }}</slot>
        </span>

        <Icon
          :icon="sortIcon"
          :class="[
            'size-4 text-neutral-700 dark:text-neutral-200',
            !isOrdered ? 'hidden' : '',
          ]"
        />
      </div>
    </div>

    <div v-else v-once class="flex items-center justify-center">
      <ILink v-if="tableCustomizeable" basic>
        <Icon icon="Cog" class="size-5 sm:size-4" @click="$emit('customize')" />
      </ILink>
    </div>
  </ITableHeader>
</template>

<script setup>
import { computed } from 'vue'

import ActionColumnSeparator from './ActionColumnSeparator.vue'
import CheckboxSeparator from './CheckboxSeparator.vue'
import PrimaryColumnSeparator from './PrimaryColumnSeparator.vue'
import ResourceTableHeaderMenu from './ResourceTableHeaderMenu.vue'

const props = defineProps({
  // Whether the current column is ordered
  isOrdered: Boolean,
  wrap: Boolean,
  attribute: { type: String, required: true },
  customizeable: { type: Boolean, required: true },
  tableCustomizeable: { type: Boolean, required: true },
  width: { required: true },
  label: { required: true },
  align: { type: String, default: 'left' },
  condensed: Boolean,
  isSortedAscending: Boolean,
  isPrimary: Boolean,
  isSelectable: Boolean,
  isSortable: Boolean,
  allRowsSelected: Boolean,
  totalSelected: Number,
})

defineEmits(['checkboxChanged', 'sortAsc', 'sortDesc', 'updated', 'customize'])

const totalXMargin = 25

const indeterminate = computed(
  () => props.totalSelected > 0 && !props.allRowsSelected
)

const sortIcon = computed(() =>
  props.isSortedAscending ? 'ArrowUpSolid' : 'ArrowDownSolid'
)

const isActionsColumn = computed(() => props.attribute === 'actions')

const hasMenu = computed(() => props.isSortable || props.customizeable)
</script>
