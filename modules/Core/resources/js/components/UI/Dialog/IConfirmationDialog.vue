<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="dialog relative" :open="open" static>
      <TransitionChild
        as="template"
        enter="ease-out duration-100"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <IDialogOverlay />
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
              :class="[
                'relative w-full transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl ring-1 ring-neutral-600/10 transition-all dark:bg-neutral-900 dark:ring-neutral-500/20 sm:my-8 sm:rounded-2xl sm:p-6',
                sizeClass,
              ]"
            >
              <component
                :is="dialog.component"
                v-if="dialog.component"
                :close="close"
                :cancel="cancel"
                :dialog="dialog"
              />

              <template v-else>
                <div class="sm:flex sm:items-start">
                  <div
                    :class="[
                      'mx-auto flex size-12 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-10',
                      dialog.iconWrapperColorClass
                        ? dialog.iconWrapperColorClass
                        : 'bg-danger-100',
                    ]"
                  >
                    <Icon
                      :icon="dialogIcon"
                      :class="[
                        'size-6',
                        dialog.iconColorClass
                          ? dialog.iconColorClass
                          : 'text-danger-600',
                      ]"
                    />
                  </div>

                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <DialogTitle
                      class="mt-2 text-base/6 font-medium text-neutral-700 dark:text-white"
                    >
                      <!-- eslint-disable-next-line vue/no-v-html -->
                      <span v-if="dialog.html" v-html="title"></span>

                      <span v-else v-text="title"></span>
                    </DialogTitle>

                    <div
                      v-if="dialog.message"
                      :class="Boolean(title) ? 'mt-2' : ''"
                    >
                      <IText>
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <span v-if="dialog.html" v-html="dialog.message" />

                        <span v-else v-text="dialog.message" />
                      </IText>
                    </div>
                  </div>
                </div>

                <div class="mt-6 sm:flex sm:flex-row-reverse">
                  <IButton
                    class="w-full sm:ml-2 sm:w-auto"
                    :variant="confirmVariant"
                    :text="dialog.confirmText"
                    @click="confirm"
                  />

                  <IButton
                    class="mt-2 w-full sm:mt-0 sm:w-auto"
                    :text="dialog.cancelText"
                    basic
                    @click="cancel"
                  />
                </div>
              </template>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed, ref, toRef } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

import IButton from '../Button/IButton.vue'
import { IText } from '../Text'

import IDialogOverlay from './IDialogOverlay.vue'
import { useDialogSize } from './useDialog'

const props = defineProps({
  dialog: { required: true, type: Object },
})

const sizeClass = useDialogSize(toRef(props.dialog, 'size'))

const open = ref(true)

const title = computed(() =>
  props.dialog.title === false ? null : props.dialog.title
)

const dialogIcon = computed(() => props.dialog.icon || 'ExclamationTriangle')

const confirmVariant = computed(() => props.dialog.confirmVariant || 'danger')

function close() {
  open.value = false
}

function confirm() {
  props.dialog.resolve()
  close()
}

function cancel() {
  props.dialog.reject()
  close()
}
</script>
