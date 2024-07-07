<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog
      as="div"
      class="dialog relative"
      :open="isOpen"
      :initial-focus="initialFocus"
      static
      @close="maybePreventClosing"
    >
      <TransitionChild
        as="template"
        enter="ease-out duration-100"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <IDialogOverlay v-show="overlay" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div
          class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
        >
          <TransitionChild
            as="template"
            enter="ease-out duration-100"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-100"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              :as="form ? 'form' : 'div'"
              :novalidate="form ? true : undefined"
              :class="[
                sizeClass,
                'relative w-full transform overflow-hidden rounded-2xl bg-white text-left shadow-lg ring-1 ring-neutral-500/10 transition-all dark:bg-neutral-900 dark:ring-neutral-500/20 sm:my-8',
              ]"
              @submit.prevent="$emit('submit', $event)"
            >
              <div class="bg-white p-6 dark:bg-neutral-900 sm:p-8">
                <div v-if="!hideHeader" class="mb-6">
                  <IDialogTitle
                    v-show="!hideHeader"
                    class="mr-5"
                    :sub-title="subTitle"
                  >
                    <slot name="title" :title="title">{{ title }}</slot>
                  </IDialogTitle>

                  <div
                    v-if="!hideHeaderClose"
                    class="absolute right-5 top-6 -mt-px sm:right-7 sm:top-8 sm:mt-0"
                  >
                    <IDialogCloseButton @click="hide" />
                  </div>
                </div>

                <div>
                  <slot />
                </div>
              </div>

              <div v-show="!hideFooter" class="px-6 pb-6 sm:px-8 sm:pb-8">
                <slot name="modal-footer" :cancel="hide">
                  <div class="flex justify-end space-x-2">
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

              <div
                :id="id + '-teleport'"
                ref="teleportRef"
                class="_child-dialogs"
              />
            </DialogPanel>
          </TransitionChild>
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
