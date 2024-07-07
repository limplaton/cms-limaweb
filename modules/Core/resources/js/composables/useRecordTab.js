
import { computed, nextTick, ref } from 'vue'

import { useGate } from './useGate'

export function useRecordTab(config) {
  const page = ref(1)
  const search = ref(null)
  const searchResults = ref(null)
  const dataLoadedFirstTime = ref(false)
  const defaultPerPage = ref(config.perPage || 15)
  const { gate } = useGate()

  const isSearching = computed(() => search.value !== null)

  const hasSearchResults = computed(
    () => searchResults.value && searchResults.value.length > 0
  )

  /**
   * Perform search
   *
   * @param  {String|null} value
   *
   * @return {Void}
   */
  function performSearch(value) {
    // Reset the state in case complete so the infinity
    // loading can be performed again
    config.infinityRef.value.state.reset()

    // Reset the page as for each search, the page must be
    // resetted to start from zero, additional pages results
    // are again handle by infinity loader when user scrolling to bottom
    // This also helps when user remove the search value so the infinity
    // loader can load the actual data from page 1 again
    page.value = 1

    if (!value) {
      loadData()
      search.value = null
      searchResults.value = null

      return
    }

    searchResults.value = []
    search.value = value
    loadData(true)
  }

  /**
   * Attempt to load data
   *
   * @param {Boolean} force
   *
   * @return {Void}
   */
  function loadData(force = false) {
    config.infinityRef.value.attemptLoad(force)
  }

  /**
   * Handle the infinity load response
   *
   * @param  {Object} data
   *
   * @return {Void}
   */
  function handleInfinityResult(data) {
    if (config.handleInfinityResult) {
      config.handleInfinityResult(data)
    } else {
      config.synchronizeResource({ [config.timelineRelation]: data.data })
    }
  }

  /**
   * Make the request for data
   *
   * @param  {int} page
   * @param  {int|null} perPage
   *
   * @return {Promise}
   */
  function makeRequestForData(page, perPage) {
    perPage = perPage || defaultPerPage.value

    if (config.makeRequestForData) {
      return config.makeRequestForData(page, perPage)
    }

    return Innoclapps.request(
      `${config.resource.value.path}/${config.timelineRelation}`,
      {
        params: {
          page,
          q: search.value,
          per_page: perPage,
          timeline: 1,
        },
      }
    )
  }

  /**
   * Infinity load handler
   *
   * @param  {Object} $state
   *
   * @return {Void}
   */
  async function infiniteHandler($state) {
    // We must check if the user has the permissions to view the record
    // in order to load the recorable resource
    // Can happen when user creates e.q. contact and assign this contact
    // to another user but the user who created the contact has only permissions
    // to view his own contacts, in this case, we will still show the contact profile
    // but there will be a message tha this user will be unable to view the contact
    if (gate.denies('view', config.resource.value)) {
      $state.complete()

      return
    }

    let data = null

      ; ({ data: data } = await makeRequestForData(page.value))

    if (data.data.length === 0) {
      if (isSearching.value) {
        // No search results and page is equal to 1?
        // In this case, just set the search results to empty
        if (page.value === 1) {
          searchResults.value = []
        }
      }

      $state.complete()
      dataLoadedFirstTime.value = true

      return
    }

    page.value += 1

    if (isSearching.value) {
      searchResults.value = !hasSearchResults.value
        ? data.data
        : searchResults.value.concat(...data.data)
    } else {
      handleInfinityResult(data)

      nextTick(() => (dataLoadedFirstTime.value = true))
    }

    $state.loaded()
  }

  /**
   * Refresh the current resource tab
   */
  function refresh() {
    makeRequestForData(1, defaultPerPage.value * page.value).then(
      ({ data }) => {
        if (data.data.length === 0) {
          config.synchronizeResource({
            [config.timelineRelation]: { _reset: [] },
          })
        } else {
          handleInfinityResult(data)
        }
      }
    )
  }

  /**
   * Retrieve the given associateble resource and scroll the container to the node
   */
  async function focusToAssociateableElement(id, elementSectionPrefix) {
    // We will first retrieve the associatebale record and add to the resource record
    // relationship object, as it may be old record and the associatables record are paginated
    // in this case, if we query the document directly the record may no exists in the document
    let { data: responseResource } = await Innoclapps.request(
      `/${config.timelineRelation}/${id}`,
      {
        params: {
          via_resource: config.resourceName,
          via_resource_id: config.resource.value.id,
        },
      }
    )

    config.synchronizeResource({
      [config.timelineRelation]: [responseResource],
    })

    await nextTick()

    const tabPanelNode = document.getElementById(
      'tabPanel-' + config.timelineRelation
    )

    const recordNode = tabPanelNode.querySelector(
      `.${elementSectionPrefix}-${responseResource.id}`
    )

    const scrollNode = config.scrollElement
      ? document.querySelector(config.scrollElement)
      : window

    if (recordNode) {
      scrollNode.scrollTo({
        top: recordNode.getBoundingClientRect().top,
        behavior: 'smooth',
      })
    }
  }

  return {
    focusToAssociateableElement,
    dataLoadedFirstTime,
    searchResults,
    hasSearchResults,
    infiniteHandler,
    search,
    loadData,
    performSearch,
    isSearching,
    defaultPerPage,
    refresh,
  }
}
