<template>
  <Tab v-slot="{ selected }" ref="tabRef" as="template" :disabled="disabled">
    <button
      type="button"
      data-slot="tab"
      :data-state="selected ? 'active' : 'inactive'"
      :class="[
        // Base
        'group inline-flex min-w-full shrink-0 snap-start snap-always items-center justify-center whitespace-nowrap border-b-2 text-base/5 font-semibold focus:outline-none sm:min-w-0 ',

        // Inactive
        'data-[state=inactive]:border-transparent data-[state=inactive]:text-neutral-700 data-[state=inactive]:hover:border-neutral-300 data-[state=inactive]:hover:text-neutral-800 data-[state=inactive]:dark:text-neutral-50 data-[state=inactive]:dark:hover:border-neutral-500 data-[state=inactive]:dark:hover:text-neutral-300',

        // Active
        'data-[state=active]:border-primary-500 data-[state=active]:text-primary-600 data-[state=active]:dark:border-primary-400 data-[state=active]:dark:text-primary-300',

        // Disaabled
        'disabled:pointer-events-none disabled:opacity-70',

        // Sizing
        'px-1 py-4 sm:text-sm/5',

        // Icon
        '[&>[data-slot=icon]]:-ml-0.5 [&>[data-slot=icon]]:mr-1.5 [&>[data-slot=icon]]:size-5 [&>[data-slot=icon]]:sm:size-4',

        // Badge
        '[&>[data-slot=badge]]:ml-1.5',
      ]"
    >
      <slot>
        {{ title }}
      </slot>
    </button>
  </Tab>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { Tab } from '@headlessui/vue'
import { useActiveElement } from '@vueuse/core'

defineProps({
  title: String,
  disabled: Boolean,
})

const emit = defineEmits(['activated'])

const tabRef = ref(null)
const activeElement = useActiveElement()

watch(
  activeElement,
  newEl => {
    if (tabRef.value && tabRef.value.el.isEqualNode(newEl)) {
      emit('activated')
    }
  },
  { flush: 'post' }
)

onMounted(() => {
  if (tabRef.value.el.getAttribute('aria-selected') === 'true') {
    emit('activated')
  }
})
</script>
