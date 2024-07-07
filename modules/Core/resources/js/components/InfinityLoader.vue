<template>
  <div ref="rootElRef">
    <slot />

    <div class="mt-3 flex justify-center">
      <ISpinner v-show="isLoading" class="size-4 text-primary-500" />
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'

import { debounce as debounceFn } from '@/Core/utils'
import { isElementVisible, passiveEventArg } from '@/Core/utils'

const props = defineProps({
  scrollElement: String,
  debounce: { type: [Number, Boolean, String], default: 200 },
  offset: { type: Number, default: 0 },
  loadWhenMounted: Boolean,
})

const emit = defineEmits([
  'handle',
  'loaded',
  'complete',
  'reset',
  'pause',
  'resume',
])

const STATUS = {
  COMPLETE: 'complete',
  LOADING: 'loading',
  READY: 'ready',
  PAUSED: 'paused',
}

const rootElRef = ref(null)
const status = ref(STATUS.READY)

let scrollNode = null
let handleOnScroll = null

const state = {
  loaded: () => {
    emit('loaded')
    status.value = STATUS.READY
  },
  complete: () => {
    emit('complete')
    status.value = STATUS.COMPLETE
    removeEvents()
  },
  reset: () => {
    emit('reset')
    status.value = STATUS.READY
    bindEvents()
  },
  pause: () => {
    emit('pause')
    status.value = STATUS.PAUSED
    removeEvents()
  },
  resume: () => {
    if (status.value === STATUS.PAUSED) {
      emit('resume')
      status.value = STATUS.READY
      bindEvents()
    }
  },
}

const isLoading = computed(() => status.value === STATUS.LOADING)

function _handleOnScroll(e) {
  if (status.value === STATUS.READY && isElementVisible(rootElRef.value)) {
    let scrollNode = e.target

    if (e.target === document) {
      scrollNode = scrollNode.scrollingElement || scrollNode.documentElement
    }

    if (
      scrollNode.scrollHeight -
        props.offset -
        scrollNode.scrollTop -
        scrollNode.clientHeight <
      1
    ) {
      attemptLoad()
    }
  }
}

if (props.debounce) {
  handleOnScroll = debounceFn(_handleOnScroll, props.debounce, {
    trailing: true,
  })
} else {
  handleOnScroll = _handleOnScroll
}

function attemptLoad(force = false) {
  if (status.value === STATUS.READY || force === true) {
    status.value = STATUS.LOADING
    emit('handle', state)
  }
}

function removeEvents() {
  scrollNode &&
    scrollNode.removeEventListener('scroll', handleOnScroll, passiveEventArg())
}

function bindEvents() {
  scrollNode &&
    scrollNode.addEventListener('scroll', handleOnScroll, passiveEventArg())
}

function prepareScroller() {
  scrollNode = props.scrollElement
    ? document.querySelector(props.scrollElement)
    : window

  if (props.loadWhenMounted) {
    attemptLoad()
  }

  bindEvents()
}

onMounted(() => {
  // Wait till scroll element rendered
  nextTick(prepareScroller)
})

onUnmounted(removeEvents)

defineExpose({ attemptLoad, state })
</script>
