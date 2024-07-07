<template>
  <Teleport to="body">
    <div
      ref="tooltipRef"
      class="pointer-events-none absolute left-0 top-0 z-[1300]"
    >
      <Transition
        enter-active-class="duration-200 ease"
        enter-from-class="transform opacity-0"
        leave-to-class="transform opacity-0"
      >
        <div
          v-if="reference"
          :class="['overflow-hidden rounded-md', variants[variant].wrapper]"
        >
          <div
            ref="arrowRef"
            :class="['absolute size-2 rotate-45', variants[variant].arrow]"
          />

          <div :class="['relative max-w-sm', variants[variant].inner]">
            <div
              :class="[
                'px-4 py-1.5 text-center text-base/5 sm:text-sm/5',
                variants[variant].content,
              ]"
              v-text="content"
            />
          </div>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
  arrow,
  computePosition,
  offset as floatingOffset,
  shift,
} from '@floating-ui/vue'

const props = defineProps({
  delay: { type: Number, default: 300 },
  offset: { type: Number, default: 10 },
})

const variants = {
  dark: {
    wrapper: 'border border-neutral-900/90 shadow-lg bg-neutral-900/90',
    inner: 'bg-neutral-900/90',
    content: 'text-white',
    arrow: 'border border-neutral-900/90 bg-neutral-900/90',
  },
  light: {
    wrapper:
      'border border-neutral-200 dark:border-neutral-500/30 shadow-lg bg-white dark:bg-neutral-800',
    inner: 'bg-white dark:bg-neutral-800',
    content: 'text-neutral-700 dark:text-neutral-200',
    arrow:
      'border border-neutral-200 dark:border-neutral-500/30 bg-white dark:bg-neutral-800',
  },
}

let delayTimeout = null

const arrowRef = ref(null)
const tooltipRef = ref()
const reference = ref(null)
const content = ref('')
const variant = ref('dark')

const mouseoverListener = e => {
  const target = e.target.closest('[v-tooltip]')

  if (delayTimeout) {
    clearTimeout(delayTimeout)
    delayTimeout = null
  }

  if (reference.value !== target) {
    reference.value = null
  }

  if (target) {
    delayTimeout = setTimeout(() => {
      reference.value = target
    }, props.delay)
  }
}

const showTooltip = async () => {
  if (!reference.value) return

  await nextTick()

  const elPlacement = reference.value.getAttribute('v-tooltip-placement')
  variant.value = reference.value.getAttribute('v-tooltip-variant')
  content.value = reference.value.getAttribute('v-tooltip') || ''

  const options = {
    placement: elPlacement,
    middleware: [
      floatingOffset(props.offset),
      shift(),
      arrow({ element: arrowRef.value }),
    ],
  }

  computePosition(reference.value, tooltipRef.value, options).then(
    ({ x, y, placement, middlewareData }) => {
      const staticSide = {
        top: 'bottom',
        right: 'left',
        bottom: 'top',
        left: 'right',
      }[placement.split('-')[0]]

      Object.assign(arrowRef.value.style, {
        ...(middlewareData.arrow?.y && {
          top: `${middlewareData.arrow.y}px`,
        }),
        ...(middlewareData.arrow?.x && {
          left: `${middlewareData.arrow.x}px`,
        }),
        right: '',
        bottom: '',
        [staticSide]: '-0.25rem',
      })

      Object.assign(tooltipRef.value.style, {
        left: `${x}px`,
        top: `${y}px`,
      })
    }
  )
}

const hideTooltip = () => {
  reference.value = null
  content.value = null
}

watch(reference, async el => {
  if (el) await showTooltip()
  else hideTooltip()
})

onMounted(() => {
  document.addEventListener('mouseover', mouseoverListener)
})

onBeforeUnmount(() => {
  if (delayTimeout) clearTimeout(delayTimeout)
  document.removeEventListener('mouseover', mouseoverListener)
})
</script>
