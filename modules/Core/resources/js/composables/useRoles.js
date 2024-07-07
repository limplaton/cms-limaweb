
import { computed, ref } from 'vue'
import { createGlobalState } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import { useLoader } from '@/Core/composables/useLoader'

export const useRoles = createGlobalState(() => {
  const { setLoading, isLoading: rolesAreBeingFetched } = useLoader()

  const roles = ref([])

  const rolesByName = computed(() => orderBy(roles.value, 'name'))
  const rolesNames = computed(() => rolesByName.value.map(role => role.name))

  // Only excuted once
  fetchRoles()

  function idx(id) {
    return roles.value.findIndex(role => role.id == id)
  }

  function removeRole(id) {
    roles.value.splice(idx(id), 1)
  }

  function addRole(role) {
    roles.value.push(role)
  }

  function setRole(id, role) {
    roles.value[idx(id)] = role
  }

  async function fetchRole(id, config) {
    const { data } = await Innoclapps.request(`/roles/${id}`, config)

    return data
  }

  async function deleteRole(id) {
    await Innoclapps.request().delete(`/roles/${id}`)
    removeRole(id)
  }

  function fetchRoles() {
    setLoading(true)

    Innoclapps.request('/roles')
      .then(({ data }) => (roles.value = data))
      .finally(() => setLoading(false))
  }

  return {
    roles,
    rolesByName,
    rolesNames,
    rolesAreBeingFetched,

    addRole,
    removeRole,
    setRole,

    fetchRoles,
    fetchRole,
    deleteRole,
  }
})
