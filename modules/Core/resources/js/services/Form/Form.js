
import cloneDeep from 'lodash/cloneDeep'
import isEqual from 'lodash/isEqual'
import merge from 'lodash/merge'

import Errors from './Errors'
import { isFile, objectToFormData } from './utils'

class Form {
  /**
   * Represents a Form, managing its state, options, and error handling.
   *
   * @param {object} [data={}] - Initial data for the form, typically field values.
   * @param {object} [options={}] - Configuration options for the form.
   */
  constructor(data = {}, options = {}) {
    this.busy = false
    this.recentlySuccessful = false
    this.errors = new Errors()
    this.queryString = {}
    this.withData(data).withOptions(options)
  }

  /**
   * Check if the form is dirty.
   *
   * @returns {boolean}
   */
  isDirty() {
    return !isEqual(this.originalData, this.data())
  }

  /**
   * Add form data
   *
   * @param {object} data
   * @returns {Form}
   */
  withData(data) {
    this.successful = false

    this.setInitialData(data)

    for (const field in data) {
      this[field] = data[field]
    }

    return this
  }

  /**
   * Set the form initial data
   *
   * @param {object} values
   * @returns {void}
   */
  setInitialData(values) {
    this.originalData = cloneDeep(values)
  }

  /**
   * Add form options
   * @param {object} options
   * @returns {Form}
   */
  withOptions(options) {
    this.__options = {
      resetOnSuccess: false,
      ...options,
    }

    return this
  }

  /**
   * Populate form data.
   *
   * @param {object} data
   * @returns {Form}
   */
  populate(data) {
    this.keys().forEach(key => {
      this[key] = data[key]
    })

    return this
  }

  /**
   * Set initial form data/attribute.
   * E.q. can be used when resetting the form
   *
   * @param {string|object} attribute
   * @param {Form} value
   */
  set(attribute, value = null) {
    if (typeof attribute === 'object') {
      Object.entries(attribute).forEach(([field, value]) =>
        this.set(field, value)
      )
    } else {
      this.fill(attribute, value)

      // Compare if both values are equal, we need to compare
      // As if the values are array, Vue.js will trigger updates
      if (!isEqual(this.originalData[attribute], value)) {
        this.originalData[attribute] = cloneDeep(value)
      }
    }

    return this
  }

  /**
   * Fill form data/attribute.
   *
   * @param {string|object} attribute
   * @param {any} value
   * @returns {Form}
   */
  fill(attribute, value = null) {
    if (typeof attribute === 'object') {
      Object.entries(attribute).forEach(([field, value]) =>
        this.fill(field, value)
      )
    } else {
      // Compare if both values are equal, we need to compare
      // As if the values are array, Vue.js will trigger updates
      if (!isEqual(this[attribute], value)) {
        this[attribute] = value
      }
    }

    return this
  }

  /**
   * Add form query string
   *
   * @param {object} values
   * @returns {Form}
   */
  withQueryString(values) {
    this.queryString = { ...this.queryString, ...values }

    return this
  }

  /**
   * Get the form data.
   *
   * @returns {object}
   */
  data() {
    return this.keys().reduce(
      (data, key) => ({ ...data, [key]: this[key] }),
      {}
    )
  }

  /**
   * Get the form data keys.
   *
   * @returns {Array}
   */
  keys() {
    return Object.keys(this).filter(key => !this.ignore.includes(key))
  }

  /**
   * Start processing the form.
   *
   * @returns {void}
   */
  startProcessing() {
    this.errors.clear()
    this.busy = true
    this.successful = false
  }

  /**
   * Finish processing the form.
   *
   * @returns {void}
   */
  finishProcessing(response) {
    this.busy = false
    this.successful = true
    this.recentlySuccessful = true

    if (this.__options.resetOnSuccess) {
      this.reset()
    }

    if (typeof this.__options.onSuccess == 'function') {
      this.__options.onSuccess(response)
    }

    setTimeout(() => (this.recentlySuccessful = false), 3000)
  }

  /**
   * Clear the form data and it's errors
   *
   * @returns {Form}
   */
  clear() {
    this.keys().forEach(key => {
      delete this[key]
    })

    this.successful = false

    this.errors.clear()

    this.queryString = {}
    this.setInitialData({})

    return this
  }

  /**
   * Reset the form data.
   *
   * @returns {Form}
   */
  reset() {
    this.keys().forEach(key => {
      this[key] = cloneDeep(this.originalData[key])
    })

    return this
  }

  /**
   * Get the first error message for the given field.
   *
   * @param {string} field
   * @returns {string|undefined}
   */
  getError(field) {
    return this.errors.first(field)
  }

  /**
   * Submit the form via a GET request.
   *
   * @param  {string} url
   * @param  {object} config (axios config)
   * @returns {Promise}
   */
  get(url, config = {}) {
    return this.submit('get', url, config)
  }

  /**
   * Submit the form via a POST request.
   *
   * @param  {string} url
   * @param  {object} config (axios config)
   * @returns {Promise}
   */
  post(url, config = {}) {
    return this.submit('post', url, config)
  }

  /**
   * Submit the form via a PATCH request.
   *
   * @param  {string} url
   * @param  {object} config (axios config)
   * @returns {Promise}
   */
  patch(url, config = {}) {
    return this.submit('patch', url, config)
  }

  /**
   * Submit the form via a PUT request.
   *
   * @param  {string} url
   * @param  {object} config (axios config)
   * @returns {Promise}
   */
  put(url, config = {}) {
    return this.submit('put', url, config)
  }

  /**
   * Submit the form via a DELETE request.
   *
   * @param  {string} url
   * @param  {object} config (axios config)
   * @returns {Promise}
   */
  delete(url, config = {}) {
    return this.submit('delete', url, config)
  }

  /**
   * Submit the form data via an HTTP request.
   *
   * @param  {string} method (get, post, patch, put)
   * @param  {string} url
   * @param  {object} config (axios config)
   * @returns {Promise}
   */
  submit(method, url, config = {}) {
    this.startProcessing()

    let urlData = this.createQueryStringParams(url)

    const data =
      method === 'get'
        ? {
          params: merge(urlData.queryString, this.data()),
        }
        : this.hasFiles()
          ? objectToFormData(this.data())
          : this.data()

    return new Promise((resolve, reject) => {
      Innoclapps.request()
      [method](
        urlData.uri,
        data,
        merge(
          {
            params: urlData.queryString,
          },
          config
        )
      )
        .then(response => {
          resolve(response.data)
          this.finishProcessing(response)
        })
        .catch(error => {
          this.busy = false

          if (error.response) {
            this.errors.set(this.extractErrors(error.response))
          }
          reject(error)
        })
    })
  }

  /**
   * Extract the errors from the response object.
   *
   * @param  {object} response
   * @returns {object}
   */
  extractErrors(response) {
    if (!response.data || typeof response.data !== 'object') {
      return { error: Form.errorMessage }
    }

    if (response.data.errors) {
      return { ...response.data.errors }
    }

    if (response.data.message) {
      return { error: response.data.message }
    }

    return { ...response.data }
  }

  /**
   * Create a query string params for the request URL.
   *
   * @param  {string} url
   *
   * @returns {object}
   */
  createQueryStringParams(url) {
    let urlArray = url.split('?')

    let params = urlArray[1]
      ? Object.fromEntries(new URLSearchParams(urlArray[1]))
      : {}

    return {
      uri: urlArray[0],
      queryString: merge(params, this.queryString),
    }
  }

  hasFiles() {
    for (const property in this.originalData) {
      if (this.hasFilesDeep(this[property])) {
        return true
      }
    }

    return false
  }

  hasFilesDeep(object) {
    if (object === null) {
      return false
    }

    if (typeof object === 'object') {
      for (const key in object) {
        if (Object.hasOwn(object, key)) {
          if (this.hasFilesDeep(object[key])) {
            return true
          }
        }
      }
    }

    if (Array.isArray(object)) {
      for (const key in object) {
        if (Object.hasOwn(object, key)) {
          return this.hasFilesDeep(object[key])
        }
      }
    }

    return isFile(object)
  }

  /**
   * The attributes that should be ignored as data
   */
  get ignore() {
    return [
      '__options',
      'busy',
      'successful',
      'recentlySuccessful',
      'errors',
      'originalData',
      'queryString',
    ]
  }
}

Form.errorMessage = 'Something went wrong. Please try again.'

export default Form
