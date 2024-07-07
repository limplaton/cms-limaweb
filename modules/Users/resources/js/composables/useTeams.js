
import { computed, ref } from 'vue'
import { createGlobalState } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import { useLoader } from '@/Core/composables/useLoader'

export const useTeams = createGlobalState(() => {
  const { setLoading, isLoading: teamsAreBeingFetched } = useLoader()

  const teams = ref([])

  const teamsByName = computed(() => orderBy(teams.value, 'name'))

  // Only excuted once
  fetchTeams()

  function idx(id) {
    return teams.value.findIndex(team => team.id == id)
  }

  function removeTeam(id) {
    teams.value.splice(idx(id), 1)
  }

  function addTeam(team) {
    teams.value.push(team)
  }

  function setTeam(id, team) {
    teams.value[idx(id)] = team
  }

  async function deleteTeam(id) {
    await Innoclapps.request().delete(`/teams/${id}`)
    removeTeam(id)
  }

  function fetchTeams(config) {
    setLoading(true)

    Innoclapps.request('/teams', config)
      .then(({ data }) => (teams.value = data))
      .finally(() => setLoading(false))
  }

  return {
    teams,
    teamsByName,
    teamsAreBeingFetched,

    addTeam,
    removeTeam,
    setTeam,

    deleteTeam,
    fetchTeams,
  }
})
