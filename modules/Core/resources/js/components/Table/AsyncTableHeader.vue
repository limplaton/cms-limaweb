<template>
  <ITableHeader
    ref="headerRef"
    class="group bg-neutral-50 dark:bg-neutral-500/10"
  >
    <ILink
      v-if="isSortable"
      class="inline-flex w-full items-center hover:text-neutral-700 focus:outline-none dark:hover:text-neutral-400"
      plain
      @click="toggleSortable"
    >
      {{ heading }}
      <Icon
        :icon="isSortedAscending ? 'ChevronUpSolid' : 'ChevronDownSolid'"
        :class="[
          'ml-1.5 size-5 sm:size-4',
          isTableOrderedByCurrentField
            ? 'opacity-100'
            : 'opacity-0 group-hover:opacity-100',
        ]"
      />
    </ILink>

    <span v-else v-text="heading" />
  </ITableHeader>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  isSortable: Boolean,
  heading: String,
  headingKey: { type: String, required: true },
  ctx: { type: Object, required: true },
})

const emit = defineEmits(['update:ctx'])

const headerRef = ref(null)

/**
 * Check whether the table is ordered by current field
 */
const isTableOrderedByCurrentField = computed(() => {
  return props.ctx.sortBy === props.headingKey
})

/**
 * Check whether current field is sorted ascending
 */
const isSortedAscending = computed(() => {
  return props.ctx.direction === 'asc' && isTableOrderedByCurrentField.value
})

/**
 * Toggle sortable column
 */
function toggleSortable() {
  const ctx = {}

  if (isTableOrderedByCurrentField.value) {
    ctx.sortBy = props.headingKey
    ctx.direction = props.ctx.direction === 'desc' ? 'asc' : 'desc'
  } else {
    ctx.sortBy = props.headingKey
    ctx.direction = 'desc'
  }

  emit('update:ctx', Object.assign({}, props.ctx, ctx))
}

defineExpose({ header: headerRef })
</script>
