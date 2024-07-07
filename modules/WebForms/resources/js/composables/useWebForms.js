
import { computed, ref } from 'vue'
import { createGlobalState } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import { useLoader } from '@/Core/composables/useLoader'

export const useWebForms = createGlobalState(() => {
  const { setLoading, isLoading: formsAreBeingFetched } = useLoader()

  const webForms = ref([])

  const webFormsOrderedByNameAndStatus = computed(() =>
    orderBy(webForms.value, ['status', 'title'], ['asc', 'asc'])
  )

  // Only excuted once
  fetchWebForms()

  function idx(id) {
    return webForms.value.findIndex(form => form.id == id)
  }

  function removeWebForm(id) {
    webForms.value.splice(idx(id), 1)
  }

  function addWebForm(form) {
    webForms.value.push(form)
  }

  function setWebForm(id, form) {
    webForms.value[idx(id)] = form
  }

  function findWebForm(id) {
    return webForms.value[idx(id)]
  }

  async function fetchWebForm(id, config) {
    const { data } = await Innoclapps.request(`/forms/${id}`, config)

    return data
  }

  async function deleteWebForm(id) {
    await Innoclapps.request().delete(`/forms/${id}`)
    removeWebForm(id)
  }

  function fetchWebForms(config) {
    setLoading(true)

    Innoclapps.request('/forms', config)
      .then(({ data }) => (webForms.value = data))
      .finally(() => setLoading(false))
  }

  return {
    webForms,
    webFormsOrderedByNameAndStatus,
    formsAreBeingFetched,

    addWebForm,
    removeWebForm,
    setWebForm,
    findWebForm,

    fetchWebForms,
    fetchWebForm,
    deleteWebForm,
  }
})
