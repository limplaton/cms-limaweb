
const DefaultCollection = {
  data: [],
  current_page: 1,
  per_page: 25,
  per_page_options: [25, 50, 100],
  total: 0,
  last_page: 1,
  from: 1,
  to: 1,
}

class Paginator {
  /**
   * Creates an instance of Paginator.
   * @param {object|DefaultCollection} [state={}] - Initial state for the paginator.
   */
  constructor(state = {}) {
    this.state = Object.assign({}, DefaultCollection, state)
  }

  /**
   * Gets the pagination links.
   * @returns {Array}
   */
  get pagination() {
    return this.buildLinks(this.currentPage, this.lastPage)
  }

  /**
   * (Getter) items
   * @returns {Array}
   */
  get items() {
    return this.state.data
  }

  /**
   * (Getter) currentPage
   * @returns {number}
   */
  get currentPage() {
    return this.getPaginationAttribute('current_page')
  }

  /**
   * (Setter) currentPage
   * @param value {number}
   * @returns void
   */
  set currentPage(value) {
    this.setPaginationAttribute('current_page', value)
  }

  /**
   * (Getter) from
   * @returns {number}
   */
  get from() {
    return this.getPaginationAttribute('from')
  }

  /**
   * (Getter) to
   * @returns {number}
   */
  get to() {
    return this.getPaginationAttribute('to')
  }

  /**
   * (Getter) lastPage
   * @returns {number}
   */
  get lastPage() {
    return this.getPaginationAttribute('last_page')
  }

  /**
   * (Getter) perPage
   * @returns {number}
   */
  get perPage() {
    return this.getPaginationAttribute('per_page')
  }

  /**
   * (Setter) perPage
   * @param value {number}
   * @returns void
   */
  set perPage(value) {
    this.setPaginationAttribute('per_page', value)
    this.currentPage = 1
  }

  /**
   * (Getter) total
   * @returns {number}
   */
  get total() {
    return this.getPaginationAttribute('total')
  }

  /**
   * (Getter) perPageOptions
   * @returns {number}
   */
  get perPageOptions() {
    let perPageOptions = this.state.per_page_options
    let perPage = parseInt(this.perPage)

    if (perPageOptions.includes(perPage)) {
      return perPageOptions
    }

    perPageOptions.push(perPage)

    perPageOptions.sort(function (a, b) {
      return a - b
    })

    return perPageOptions
  }

  /**
   * (Setter) perPageOptions
   * @param {Array} value
   * @returns {Array}
   */
  set perPageOptions(value) {
    this.state.per_page_options = value
  }

  /**
   * (Getter) hasPagination
   * @returns {boolean}
   */
  get hasPagination() {
    return this.lastPage > 1
  }

  /**
   * (Getter) shouldRenderLinks
   * @returns {boolean}
   */
  get shouldRenderLinks() {
    return this.pagination.includes(this.currentPage)
  }

  /**
   * (Getter) hasNextPage
   * @returns {boolean}
   */
  get hasNextPage() {
    return this.currentPage + 1 <= this.lastPage
  }

  /**
   * (Getter) hasPreviousPage
   * @returns {boolean}
   */
  get hasPreviousPage() {
    return this.currentPage - 1 >= 1
  }

  /**
   * Check if the collection has any items.
   * @returns {boolean}
   */
  isNotEmpty() {
    return this.items.length > 0
  }

  /**
   * Check if the collection does not any items.
   * @returns {boolean}
   */
  isEmpty() {
    return !this.isNotEmpty()
  }

  /**
   * Navigates to the previous page.
   * @returns {void}
   */
  previousPage() {
    this.page(this.currentPage - 1)
  }

  /**
   * Navigates to the next page.
   * @returns {void}
   */
  nextPage() {
    this.page(this.currentPage + 1)
  }

  /**
   * Sets the current page.
   * @param {number} value
   * @returns {void}
   */
  page(value) {
    this.currentPage = value
  }

  /**
   * Checks if the given page number is the current page.
   * @param {number} value
   * @returns {boolean}
   */
  isCurrentPage(value) {
    return this.currentPage === value
  }

  /**
   * Builds pagination links.
   * @param {number} currentPage
   * @param {number} pageCount
   * @param {number} [delta=3]
   * @returns {Array}
   */
  buildLinks(currentPage, pageCount, delta = 3) {
    let range = []

    for (
      let i = Math.max(2, currentPage - delta);
      i <= Math.min(pageCount - 1, currentPage + delta);
      i++
    ) {
      range.push(i)
    }

    if (currentPage - delta > 2) {
      range.unshift('...')
    }

    if (currentPage + delta < pageCount - 1) {
      range.push('...')
    }

    range.unshift(1)
    range.push(pageCount)

    return range
  }

  /**
   * Gets a pagination attribute.
   * @param {string} attribute
   * @returns {*}
   */
  getPaginationAttribute(attribute) {
    return this.state[attribute]
  }

  /**
   * Sets a pagination attribute.
   * @param {string} attribute
   * @param {*} value
   * @returns {Paginator}
   */
  setPaginationAttribute(attribute, value) {
    if (this.state[attribute]) {
      this.state[attribute] = value
    }

    return this
  }

  /**
   * Sets the state of the paginator.
   * @param {object} state
   * @returns {void}
   */
  setState(state) {
    this.state = Object.assign({}, this.state, state)
  }

  /**
   * Resets the state to the default collection.
   * @returns {void}
   */
  flush() {
    this.state = DefaultCollection
  }
}

export default Paginator
