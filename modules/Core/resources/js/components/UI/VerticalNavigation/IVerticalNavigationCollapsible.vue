<template>
  <div ref="elRef" class="relative cursor-pointer" @click="toggle">
    <div
      :class="[
        '[&>:not(:first-child)]:ml-6',
        visible || fixed
          ? '[&>:not(:first-child)]:block'
          : '[&>:not(:first-child)]:hidden',
      ]"
    >
      <slot />
    </div>

    <Icon
      v-if="!fixed && hasChildren"
      icon="ChevronDownSolid"
      class="pointer-events-none absolute right-4 top-2.5 size-5 sm:size-4"
    />
  </div>
</template>

<script setup>
import { nextTick, onMounted, onUpdated, ref, watch } from 'vue'

const props = defineProps({ fixed: Boolean })

const elRef = ref(null)
const visible = ref(props.fixed === true)
const hasChildren = ref(false)
const hasActiveChildren = ref(false)

function toggle(e) {
  if (
    e.target.tagName === 'A' &&
    elRef.value.querySelector('a:first-child') !== e.target
  ) {
    return
  }

  if (props.fixed) return
  visible.value = !visible.value
}

async function syncRefs() {
  await nextTick()

  if (!elRef.value) {
    return false
  }

  hasChildren.value = elRef.value.querySelectorAll('a').length > 1
  hasActiveChildren.value = elRef.value.querySelectorAll('.active').length > 0
}

watch(hasActiveChildren, newVal => {
  visible.value = newVal
})

onUpdated(syncRefs)
onMounted(syncRefs)
</script>
