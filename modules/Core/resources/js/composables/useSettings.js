
import { nextTick, readonly, ref, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStore } from 'vuex'

import { useForm } from '@/Core/composables/useForm'

import { useApp } from './useApp'

/**
 * Composable function for managing settings.
 *
 * @returns {{
 *   isReady: import('vue').Ref<boolean>,
 *   submit: (callback?: Function) => Promise<void>,
 *   saveSettings: (callback?: Function) => void,
 *   originalSettings: Readonly<import('vue').Ref<Record<string, any>>>,
 *   form: ReturnType<typeof useForm>
 * }}
 */
export function useSettings() {
  const store = useStore()
  const { t } = useI18n()
  const { form } = useForm()
  const { scriptConfig } = useApp()

  /** @type {import('vue').Ref<boolean>} */
  const isReady = ref(false)

  /** @type {import('vue').Ref<Record<string, any>>} */
  const originalSettings = shallowRef({})

  /**
   * Fetches and sets the settings data.
   */
  async function fetchAndSetSettings() {
    const { data: settings } = await Innoclapps.request('/settings')

    form.set(settings)
    originalSettings.value = settings
    isReady.value = true
  }

  /**
   * Synchronizes the settings with the application state.
   *
   * @param {Record<string, any>} data - The settings data to be synchronized.
   */
  function syncSettings(data) {
    for (let key in data) {
      let configSettingsKey = `settings.${key}`

      if (scriptConfig(configSettingsKey) !== undefined) {
        scriptConfig(configSettingsKey, data[key])
        store.commit('SET_SETTINGS', { [key]: data[key] })
      }
    }
  }

  /**
   * Saves the settings data.
   *
   * @param {Function} [callback] - Optional callback function to be called after saving.
   */
  function saveSettings(callback) {
    form.post('settings').then(settings => {
      Innoclapps.success(t('core::settings.updated'))

      if (typeof callback === 'function') {
        callback(form, settings)
      }

      syncSettings(form.data())
    })
  }

  /**
   * Submits the settings data.
   *
   * @param {Function} [callback] - Optional callback function to be called after submission.
   */
  async function submit(callback) {
    // Wait till v-model update e.g. on checkboxes when using @change="submit".
    await nextTick()

    saveSettings(callback)
  }

  fetchAndSetSettings()

  return {
    isReady,
    submit,
    saveSettings,
    originalSettings: readonly(originalSettings),
    form,
  }
}
