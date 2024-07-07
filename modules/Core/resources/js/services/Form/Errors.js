
import castArray from 'lodash/castArray'

export default class Errors {
  /**
   * Create a new error bag instance.
   */
  constructor() {
    this.errors = {}
  }

  /**
   * Set the errors object or field error messages.
   *
   * @param {object|string} field
   * @param {Array|string|undefined} messages
   *
   * @returns {void}
   */
  set(field, messages) {
    if (typeof field === 'object') {
      this.errors = field
    } else {
      this.set({ ...this.errors, [field]: castArray(messages) })
    }
  }

  /**
   * Get all the errors.
   *
   * @returns {object} The errors object.
   */
  all() {
    return this.errors
  }

  /**
   * Determine if there is an error for the given field.
   *
   * @param  {string} field
   * @returns {boolean}
   */
  has(field) {
    return Object.hasOwn(this.errors, field)
  }

  /**
   * Determine if there are any errors for the given fields.
   *
   * @param  {...string} fields
   * @returns {boolean}
   */
  hasAny(...fields) {
    return fields.some(field => this.has(field))
  }

  /**
   * Determine if there are any errors.
   *
   * @returns {boolean}
   */
  any() {
    return Object.keys(this.errors).length > 0
  }

  /**
   * Get the first error message for the given field.
   *
   * @param  {string} field
   * @returns {string|undefined}
   */
  first(field) {
    if (this.has(field)) {
      return this.getAll(field)[0]
    }
  }

  /**
   * Get all the error messages for the given field.
   *
   * @param  {string} field
   * @returns {Array}
   */
  getAll(field) {
    return castArray(this.errors[field] || [])
  }

  /**
   * Clear one or all error fields.
   *
   * @param {string|undefined} field
   */
  clear(field) {
    const errors = {}

    if (field) {
      Object.keys(this.errors).forEach(key => {
        if (key !== field) {
          errors[key] = this.errors[key]
        }
      })
    }

    this.set(errors)
  }

  /**
   * Get the errors grouped by array validation and regular validation.
   */
  groupByField() {
    const groupedErrors = {}
    const errors = this.all()

    Object.keys(errors).forEach(key => {
      // Check if the key has an array-like pattern 'key.index.attribute'
      const arrayPatternMatch = key.match(/(.*?)\.\d+\..+/)

      if (arrayPatternMatch) {
        const baseKey = arrayPatternMatch[1]

        if (!groupedErrors[baseKey]) {
          groupedErrors[baseKey] = { messages: [], array: {} }
        }

        groupedErrors[baseKey].array[key] = errors[key][0]
      } else {
        if (!groupedErrors[key]) {
          groupedErrors[key] = { messages: [] }
        }
        groupedErrors[key].messages.push(...errors[key])
      }
    })

    return groupedErrors
  }
}
