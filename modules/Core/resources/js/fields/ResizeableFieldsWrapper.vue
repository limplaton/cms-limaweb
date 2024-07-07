<template>
  <div
    ref="wrapperRef"
    :class="[
      isResizeable
        ? 'resize-y [&::-webkit-resizer]:bg-neutral-100 [&::-webkit-resizer]:outline [&::-webkit-resizer]:outline-1 [&::-webkit-resizer]:outline-neutral-200/80 [&::-webkit-resizer]:dark:bg-neutral-800 [&::-webkit-resizer]:dark:outline-neutral-500/30'
        : '',
      collapsed && !fieldsHeight ? initialHeightClass : '',
    ]"
    :style="{
      height: fieldsHeight && collapsed ? `${fieldsHeight}px` : null,
    }"
  >
    <slot />
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import elementResizeEvent from 'element-resize-event'
import { unbind as unbindElementResizeEvent } from 'element-resize-event'

import { useGate } from '@/Core/composables/useGate'
import { debounce } from '@/Core/utils'

import { useApp } from '../composables/useApp'
import { useResourceable } from '../composables/useResourceable'

const props = defineProps({
  disabled: Boolean,
  resourceName: String,
  initialHeightClass: String,
  collapsed: Boolean,
})

const { gate } = useGate()
const { scriptConfig } = useApp()

const { resourceSingularName } = useResourceable(props.resourceName)

const wrapperRef = ref(null)

const configKey = computed(() => `${resourceSingularName.value}_fields_height`)

const fieldsHeight = ref(scriptConfig(configKey.value))

const isResizeable = computed(
  () => !props.disabled && props.collapsed && gate.isSuperAdmin()
)

const updateResourceFieldsHeight = debounce(async function () {
  if (!props.collapsed) {
    return
  }

  let height = wrapperRef.value.offsetHeight

  await Innoclapps.request().post('/settings', {
    [configKey.value]: height,
  })

  scriptConfig(configKey.value, height)
  fieldsHeight.value = height
}, 500)

function createResizableEvent() {
  elementResizeEvent(wrapperRef.value, updateResourceFieldsHeight)
}

function destroyResizableEvent() {
  if (isResizeable.value) {
    unbindElementResizeEvent(wrapperRef.value)
  }
}

function prepareComponent() {
  if (isResizeable.value) {
    nextTick(createResizableEvent)
  }
}

onMounted(prepareComponent)

onBeforeUnmount(destroyResizableEvent)

defineExpose({
  $el: wrapperRef,
})
</script>
