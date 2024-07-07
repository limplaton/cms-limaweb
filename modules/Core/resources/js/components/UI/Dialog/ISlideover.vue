<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog
      as="div"
      class="dialog relative"
      :initial-focus="initialFocus"
      :open="isOpen"
      static
      @close="maybePreventClosing"
    >
      <TransitionChild
        as="template"
        enter="ease-in-out duration-400"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in-out duration-400"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <IDialogOverlay v-show="overlay" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div
            class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"
          >
            <TransitionChild
              as="template"
              enter="transform transition ease-in-out duration-400 sm:duration-600"
              enter-from="translate-x-full"
              enter-to="translate-x-0"
              leave="transform transition ease-in-out duration-400 sm:duration-600"
              leave-from="translate-x-0"
              leave-to="translate-x-full"
            >
              <DialogPanel
                :as="form ? 'form' : 'div'"
                :novalidate="form ? true : undefined"
                :class="['pointer-events-auto w-screen', sizeClass]"
                @submit.prevent="$emit('submit', $event)"
              >
                <div
                  class="flex h-full flex-col divide-y divide-neutral-600/10 border-l border-neutral-500/10 bg-white shadow-xl dark:divide-neutral-500/30 dark:border-neutral-500/30 dark:bg-neutral-900"
                >
                  <div
                    class="flex min-h-0 flex-1 flex-col overflow-y-scroll py-6"
                  >
                    <div v-show="!hideHeader" class="px-4 sm:px-6">
                      <div class="flex items-start justify-between">
                        <IDialogTitle :sub-title="subTitle">
                          <slot name="title" :title="title">{{ title }}</slot>
                        </IDialogTitle>

                        <div class="ml-3 h-5">
                          <IDialogCloseButton
                            v-if="!hideHeaderClose"
                            @click="hide"
                          />
                        </div>
                      </div>
                    </div>

                    <div class="relative mt-6 flex-1 px-4 sm:px-6">
                      <slot />
                    </div>
                  </div>

                  <div v-show="!hideFooter" class="shrink-0 px-4 py-4">
                    <slot name="modal-footer" :cancel="hide">
                      <div
                        class="flex shrink-0 flex-wrap justify-end space-x-2 sm:flex-nowrap"
                      >
                        <slot
                          name="modal-cancel"
                          :cancel="hide"
                          :text="cancelText || $dialog._cancelText"
                        >
                          <IButton
                            :disabled="computedCancelDisabled"
                            :text="cancelText || $dialog._cancelText"
                            basic
                            @click="hide"
                          />
                        </slot>

                        <slot name="modal-ok" :text="okText || $dialog._okText">
                          <IButton
                            :type="form ? 'submit' : 'button'"
                            :variant="okVariant"
                            :disabled="computedOkDisabled"
                            :loading="okLoading"
                            :text="okText || $dialog._okText"
                            @click="handleOkClick"
                          />
                        </slot>
                      </div>
                    </slot>
                  </div>
                </div>

                <div
                  :id="id + '-teleport'"
                  ref="teleportRef"
                  class="_child-dialogs"
                />
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, toRef, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { useTimeoutFn } from '@vueuse/core'

import IButton from '../Button/IButton.vue'

import IDialogCloseButton from './IDialogCloseButton.vue'
import IDialogOverlay from './IDialogOverlay.vue'
import IDialogTitle from './IDialogTitle.vue'
import propsDefinition from './props'
import { useDialog, useDialogSize } from './useDialog'

const props = defineProps(propsDefinition)

const emit = defineEmits([
  'hidden',
  'show',
  'shown',
  'ok',
  'update:visible',
  'submit',
])

useDialog(show, hide, toRef(props, 'id'))

const sizeClass = useDialogSize(toRef(props, 'size'))
const isOpen = ref(false)
const hiding = ref(false)
const teleportRef = ref(null)

const computedOkDisabled = computed(() => {
  return props.busy || props.okDisabled
})

const computedCancelDisabled = computed(() => {
  return props.busy || props.cancelDisabled
})

watch(
  () => props.visible,
  newVal => (newVal ? show() : hide())
)

function maybePreventClosing() {
  if (teleportRef.value.children.length > 0) {
    return
  }

  if (!props.staticBackdrop) {
    hide()
  }
}

function show() {
  emit('show')
  isOpen.value = true
  emit('update:visible', true)
  nextTick(() => emit('shown'))
}

function hide() {
  // Sometimes when the modal is hidden via the close button,
  // the v-model:visible is updated later and causing the hide event to be fired twice
  if (hiding.value) {
    return
  }

  hiding.value = true
  isOpen.value = false

  emit('update:visible', false)

  nextTick(() => {
    useTimeoutFn(() => {
      emit('hidden')
      hiding.value = false
    }, 200)
  })
}

function handleOkClick(e) {
  emit('ok', e)
}

onMounted(() => {
  if (props.visible) {
    show()
  }
})

defineExpose({ hide, show })
</script>
