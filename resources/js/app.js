
import { createApp } from 'vue'
import get from 'lodash/get'
import set from 'lodash/set'
import mitt from 'mitt'
import Mousetrap from 'mousetrap'

import VueI18n from '@/Core/i18n'
import router from '@/Core/router'
import Broadcast from '@/Core/services/Broadcast'
import HTTP from '@/Core/services/HTTP'
import store from '@/Core/store'

import '@/Core/app.js'
import '@/Users/app.js'
import '@/Activities/app.js'
import '@/Billable/app.js'
import '@/Brands/app.js'
import '@/Calls/app.js'
import '@/Comments/app.js'
import '@/Contacts/app.js'
import '@/Deals/app.js'
import '@/Documents/app.js'
import '@/MailClient/app.js'
import '@/Notes/app.js'
import '@/Translator/app.js'
import '@/WebForms/app.js'
import '@/ThemeStyle/app.js'

import 'unfonts.css'
import '../css/app.css'

window.CreateApplication = (config, callbacks = []) => {
  return new Application(config).booting(callbacks)
}

export default class Application {
  constructor(config) {
    this.bus = mitt()
    this.config = config
    this.bootingCallbacks = []
    this.availableTimezones = []

    this.axios = HTTP
    this.axios.defaults.baseURL = config.apiURL
  }

  /**
   * Start the application
   */
  start() {
    Mousetrap.init()

    const app = createApp({})

    this.boot(app, router)

    app.use(router).use(store).use(VueI18n)

    this.app = app

    this.app.mount('#app')
  }

  /**
   * Register a callback to be called before the application starts
   */
  booting(callback) {
    if (Array.isArray(callback)) {
      callback.forEach(c => this.booting(c))
    } else {
      this.bootingCallbacks.push(callback)
    }

    return this
  }

  /**
   * Execute all of the booting callbacks.
   */
  boot(app, router) {
    // Global properties
    app.config.globalProperties.$scriptConfig = (...args) =>
      this.scriptConfig(...args)
    app.config.globalProperties.$csrfToken = this.scriptConfig('csrfToken')

    // Broadcasting
    if (this.scriptConfig('broadcasting') && this.scriptConfig('user_id')) {
      this.broadcaster = new Broadcast(this.scriptConfig('broadcasting'))
    }

    store.commit('SET_SETTINGS', this.scriptConfig('settings') || {})

    store.commit(
      'SET_SIDEBAR_MENU_ITEMS',
      this.scriptConfig('menu.sidebar') || []
    )

    this.bootingCallbacks.forEach(callback =>
      callback.call(this, app, router, store)
    )

    this.bootingCallbacks = []
  }

  /**
   * Get all of the available resource objects.
   */
  resources() {
    return this.scriptConfig('resources')
  }

  /**
   * Get the serialized resource object.
   */
  resource(name) {
    return this.scriptConfig(`resources.${name}`)
  }

  /**
   * Get the given resource name.
   *
   * NOTE: Useful to avoid using plain names in .vue files and always use
   * the name from the serialized resource object.
   */
  resourceName(name) {
    return this.resource(name).name
  }

  /**
   * Get configuration for the given key.
   */
  scriptConfig(key, value = undefined) {
    if (value === undefined) {
      return get(this.config, key)
    } else {
      set(this.config, key, value)

      return this
    }
  }

  /**
   * Helper request function
   */
  request(config) {
    if (config !== undefined) {
      if (typeof config === 'string') {
        return this.axios.get(config, arguments[1] || undefined)
      } else {
        return this.axios(config)
      }
    }

    return this.axios
  }

  /**
   * Register global event
   */
  $on(...args) {
    this.bus.on(...args)
  }

  /**
   * Deregister event
   */
  $off(...args) {
    this.bus.off(...args)
  }

  /**
   * Emit global event
   */
  $emit(...args) {
    this.bus.emit(...args)
  }

  /**
   * Show toasted success message
   */
  success(message, duration = 4000, options = {}) {
    this.notify(message, 'success', duration, options)
  }

  /**
   * Show toasted info message
   */
  info(message, duration = 4000, options = {}) {
    this.notify(message, 'info', duration, options)
  }

  /**
   * Show toasted error message
   */
  error(message, duration = 4000, options = {}) {
    this.notify(message, 'error', duration, options)
  }

  /**
   * Show toasted notification
   */
  notify(message, type, duration = 4000, options = {}, group = 'app') {
    this.app.config.globalProperties.$notify(
      Object.assign({}, options, {
        text: message,
        type: type,
        group: group,
      }),
      duration
    )
  }

  /**
   * Add new a keyboard shortcut
   */
  addShortcut(keys, callback) {
    Mousetrap.bind(keys, callback)
  }

  /**
   * Disable keyboard shortcut
   */
  disableShortcut(keys) {
    Mousetrap.unbind(keys)
  }

  /**
   * Show a confirmation dialog
   */
  confirm(options) {
    return this.app.config.globalProperties.$confirm(options)
  }

  /**
   * Get the global dialog instance
   */
  dialog() {
    return this.app.config.globalProperties.$dialog
  }

  /**
   * Get or set the app available timezones
   */
  async timezones(timezones = null) {
    if (Array.isArray(timezones)) {
      this.availableTimezones = Object.freeze(timezones)

      return timezones
    }

    // Only within sanctum
    if (this.availableTimezones.length === 0) {
      let { data: timezones } = await this.request('/timezones')

      this.availableTimezones = Object.freeze(timezones)
    }

    return this.availableTimezones
  }
}
