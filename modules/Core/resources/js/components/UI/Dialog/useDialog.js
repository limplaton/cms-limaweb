
import { computed, onMounted, unref } from 'vue'

import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'

export function useDialog(show, hide, dialogId) {
  function globalShow(id) {
    if (id === unref(dialogId)) {
      show()
    }
  }

  function globalHide(id) {
    if (id === unref(dialogId)) {
      hide()
    }
  }

  onMounted(() => {
    useGlobalEventListener('_dialog-hide', globalHide)
    useGlobalEventListener('_dialog-show', globalShow)
  })
}

export function useDialogSize(size) {
  return computed(() => {
    let plainSize = unref(size)

    if (plainSize === 'xs') {
      return 'sm:max-w-md'
    }

    if (!plainSize || plainSize === 'sm') {
      return 'sm:max-w-lg'
    }

    if (plainSize === 'md') {
      return 'sm:max-w-2xl'
    }

    if (plainSize === 'lg') {
      return 'sm:max-w-3xl'
    }

    if (plainSize === 'xl') {
      return 'sm:max-w-4xl'
    }

    // xxl
    return 'sm:max-w-5xl'
  })
}
