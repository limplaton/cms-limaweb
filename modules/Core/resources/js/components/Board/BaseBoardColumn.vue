<template>
  <div
    class="inline-flex h-full w-80 flex-col overflow-y-hidden rounded-lg border border-neutral-300/40 bg-neutral-200/40 align-top shadow dark:border-neutral-500/30 dark:bg-neutral-900"
  >
    <div class="px-3 py-2">
      <div class="flex items-center">
        <slot name="header">
          <div
            class="mr-auto truncate text-base font-medium text-neutral-800 dark:text-white sm:text-sm"
            v-text="name"
          />

          <div>
            <slot name="actions" />
          </div>
        </slot>
      </div>

      <slot name="after-header" />
    </div>

    <div
      :id="'boardColumn' + columnId"
      class="h-auto overflow-y-auto overflow-x-hidden"
    >
      <SortableDraggable
        v-bind="{
          ...$draggable.scrollable,
          delay: 5,
          preventOnFilter: false,
          itemKey: item => item.id,
          emptyInsertThreshold: 100,
          move: onMoveCallback,
          group: { name: boardId },
        }"
        v-model="model"
        :data-column="columnId"
        @start="onDragStart"
        @end="onDragEnd"
        @change="onChangeEventHandler"
      >
        <template #item="{ element }">
          <div
            class="m-2 overflow-hidden whitespace-normal rounded-md bg-white shadow dark:bg-neutral-800"
          >
            <slot name="card" :card="element">
              <div class="px-4 py-5 sm:p-6">
                {{ element.display_name }}
              </div>
            </slot>
          </div>
        </template>
      </SortableDraggable>
    </div>

    <div class="flex items-center p-3" />

    <InfinityLoader
      :scroll-element="'#boardColumn' + columnId"
      @handle="infiniteHandler($event)"
    />
  </div>
</template>

<script setup>
import InfinityLoader from '@/Core/components/InfinityLoader.vue'

const props = defineProps({
  name: { required: true, type: String },
  columnId: { required: true, type: Number },
  boardId: { required: true, type: String },
  loader: { required: true, type: Function },
  move: Function,
})

const emit = defineEmits([
  'dragStart',
  'dragEnd',
  'updated',
  'added',
  'removed',
])

const model = defineModel({ type: Array, required: true })

function infiniteHandler(state) {
  props.loader(props.columnId, state)
}

function onDragStart(e) {
  emit('dragStart', e)
}

function onDragEnd(e) {
  emit('dragEnd', e)
}

function onMoveCallback(evt, originalEvent) {
  if (props.move && props.move(evt, originalEvent) === false) {
    return false
  }
}

function onChangeEventHandler(e) {
  if (e.removed) {
    emit('removed', {
      columnId: props.columnId,
      event: e,
    })
  }

  if (e.moved) {
    emit('updated', {
      columnId: props.columnId,
      event: e,
    })
  }

  if (e.added) {
    emit('added', {
      columnId: props.columnId,
      event: e,
    })

    emit('updated', {
      columnId: props.columnId,
      event: e,
    })
  }
}
</script>
