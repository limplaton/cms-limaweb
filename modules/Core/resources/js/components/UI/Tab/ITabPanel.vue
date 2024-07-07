<template>
  <TabPanel
    ref="panelRef"
    class="focus:outline-none"
    data-slot="panel"
    :unmount="lazy"
  >
    <slot />
  </TabPanel>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { TabPanel } from '@headlessui/vue'
import { useActiveElement } from '@vueuse/core'

defineProps({
  lazy: Boolean,
})

const emit = defineEmits(['activated'])

const activeElement = useActiveElement()

const panelRef = ref(null)

watch(
  activeElement,
  newEl => {
    // Is lazy and unmounted
    if (!(panelRef.value.$el instanceof Element)) {
      return
    }

    if (
      newEl.dataset.slot &&
      newEl.dataset.slot === 'tab' &&
      newEl.id === panelRef.value.$el.getAttribute('aria-labelledby')
    ) {
      emit('activated')
    }
  },
  { flush: 'post' }
)

onMounted(() => {
  if (
    // is lazy?
    !panelRef.value.el.$ &&
    panelRef.value.el.dataset.headlessuiState === 'selected'
  ) {
    emit('activated')
  }
})
</script>
