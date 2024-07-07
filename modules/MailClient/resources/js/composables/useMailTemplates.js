
import { computed, ref } from 'vue'
import { createGlobalState } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import { useLoader } from '@/Core/composables/useLoader'

export const useMailTemplates = createGlobalState(() => {
  const { setLoading, isLoading: templatesAreBeingFetched } = useLoader()

  const mailTemplates = ref([])

  const templatesByName = computed(() => orderBy(mailTemplates.value, 'name'))

  // Only excuted once
  fetchMailTemplates()

  function idx(id) {
    return mailTemplates.value.findIndex(template => template.id == id)
  }

  function findTemplateById(id) {
    return mailTemplates.value[idx(id)]
  }

  function removeTemplate(id) {
    mailTemplates.value.splice(idx(id), 1)
  }

  function addTemplate(template) {
    mailTemplates.value.push(template)
  }

  function setTemplate(id, template) {
    mailTemplates.value[idx(id)] = template
  }

  async function fetchTemplate(id, config) {
    const { data } = await Innoclapps.request('/mails/templates/' + id, config)

    return data
  }

  async function deleteTemplate(id) {
    await Innoclapps.request().delete(`/mails/templates/${id}`)
    removeTemplate(id)
  }

  function fetchMailTemplates(config) {
    setLoading(true)

    Innoclapps.request('/mails/templates', config)
      .then(({ data }) => (mailTemplates.value = data))
      .finally(() => setLoading(false))
  }

  return {
    mailTemplates,
    templatesByName,
    templatesAreBeingFetched,

    addTemplate,
    removeTemplate,
    setTemplate,
    findTemplateById,

    fetchMailTemplates,
    fetchTemplate,
    deleteTemplate,
  }
})
