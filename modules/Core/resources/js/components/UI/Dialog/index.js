
import { ref } from 'vue'

import { emitGlobal } from '@/Core/composables/useGlobalEventListener'

import IConfirmationDialogComponent from './IConfirmationDialog.vue'
import IModalComponent from './IModal.vue'
import ISlideoverComponent from './ISlideover.vue'

// Components
export const IConfirmationDialog = IConfirmationDialogComponent
export const IModal = IModalComponent
export const ISlideover = ISlideoverComponent

// Plugin
export const IDialogPlugin = {
  install(app, options = {}) {
    app.component('IModal', IModalComponent)
    app.component('ISlideover', ISlideoverComponent)
    app.component('IConfirmationDialog', IConfirmationDialogComponent)

    app.directive('dialog', {
      // eslint-disable-next-line no-unused-vars
      beforeMount: function (el, binding, vnode) {
        el._showDialog = () => emitGlobal('_dialog-show', binding.value)
        el.addEventListener('click', el._showDialog)
      },
      // eslint-disable-next-line no-unused-vars
      unmounted: function (el, binding, vnode) {
        el.removeEventListener('click', el._showDialog)
      },
    })

    app.config.globalProperties.$dialog = {
      _okText: options.dialog?.labels._okText || 'Ok',
      _cancelText: options.dialog?.labels.cancelText || 'Cancel',

      hide: id => emitGlobal('_dialog-hide', id),
      show: id => emitGlobal('_dialog-show', id),
    }

    app.config.globalProperties.confirmationDialog = ref(null)

    app.config.globalProperties.$confirm = function (dialog) {
      let callback = null

      const openDialogs = document.querySelectorAll('.dialog')
      let lastOpenDialogTeleportId = null

      if (openDialogs.length > 0) {
        lastOpenDialogTeleportId =
          openDialogs[openDialogs.length - 1].querySelector(
            '._child-dialogs'
          )?.id
      }

      let confirmOptions = {
        title: options.confirmation?.labels.title,
        confirmText: options.confirmation?.labels.confirmText,
        cancelText: options.confirmation?.labels.cancelText,
        _teleport: lastOpenDialogTeleportId
          ? '#' + lastOpenDialogTeleportId
          : null,
      }

      if (typeof dialog === 'string') {
        confirmOptions.message = dialog
      } else if (typeof dialog === 'function') {
        callback = dialog
      } else {
        confirmOptions = Object.assign(confirmOptions, dialog)
      }

      return new Promise((resolve, reject) => {
        app.config.globalProperties.confirmationDialog.value = Object.assign(
          {},
          confirmOptions,
          {
            resolve: attrs => {
              resolve(attrs)

              if (callback) {
                callback(attrs)
              }

              app.config.globalProperties.confirmationDialog.value = null
            },
            reject: attrs => {
              reject(attrs)

              app.config.globalProperties.confirmationDialog.value = null
            },
          }
        )
      })
    }
  },
}
