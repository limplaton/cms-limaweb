
import Paginator from './Paginator'

/**
 *   When to paginate:
 *
 *   watch: {
            'collection.currentPage': function(newVal, oldVal) {
                emit('paginate', newVal)
            }
        }
 */
const DefaultCollection = {
  data: [],
  meta: {
    current_page: 1,
    from: 1,
    last_page: 1,
    per_page: 25,
    total: 0,
    to: 1,
  },
  per_page_options: [25, 50, 100],
}

class ResourcePaginator extends Paginator {
  /**
   * Creates an instance of ResourcePaginator.
   * @param {object|DefaultCollection} [state={}] - Initial state for the paginator instance.
   */
  constructor(state = {}) {
    super()
    this.state = Object.assign({}, DefaultCollection, state)
  }

  /**
   * Gets an attribute from the pagination state.
   * @param {string} attribute
   * @returns {*}
   */
  getPaginationAttribute(attribute) {
    return this.state.meta[attribute]
  }

  /**
   * Sets a pagination attribute to a given value.
   * @param {string} attribute
   * @param {*} value
   * @returns {ResourcePaginator}
   */
  setPaginationAttribute(attribute, value) {
    if (this.state.meta) {
      this.state.meta[attribute] = value
    }

    return this
  }

  /**
   * Flushes the current state and resets it to the default.
   * @returns {void}
   */
  flush() {
    this.state = DefaultCollection
  }
}

export default ResourcePaginator
