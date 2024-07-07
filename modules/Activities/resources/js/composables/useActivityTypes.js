
import { computed, ref, watchEffect } from 'vue'
import orderBy from 'lodash/orderBy'

import { useApp } from '@/Core/composables/useApp'
import { useLoader } from '@/Core/composables/useLoader'

const activityTypes = ref([])

export const useActivityTypes = () => {
  const { setLoading, isLoading: typesAreBeingFetched } = useLoader()
  const { scriptConfig } = useApp()

  activityTypes.value = [...(scriptConfig('activities.types') || [])]

  watchEffect(() => {
    scriptConfig('activities.types', [...activityTypes.value])
  })

  const typesByName = computed(() => orderBy(activityTypes.value, 'name'))

  const typesForIconPicker = computed(() =>
    formatTypesForIcons(typesByName.value)
  )

  function findTypeById(id) {
    return typesByName.value.find(t => t.id == id)
  }

  function findTypeByFlag(flag) {
    return typesByName.value.find(t => t.flag == flag)
  }

  function formatTypesForIcons(types) {
    return types.map(type => ({
      id: type.id,
      icon: type.icon,
      tooltip: type.name,
    }))
  }

  function setActivityTypes(types) {
    activityTypes.value = types
  }

  function fetchActivityTypes(config = {}) {
    setLoading(true)

    Innoclapps.request(
      '/activity-types',
      Object.assign(
        {},
        {
          params: {
            per_page: 100,
          },
        },
        config
      )
    )
      .then(({ data }) => (activityTypes.value = data.data))
      .finally(() => setLoading(false))
  }

  return {
    activityTypes,
    typesByName,
    typesAreBeingFetched,
    typesForIconPicker,

    findTypeById,
    findTypeByFlag,
    formatTypesForIcons,
    setActivityTypes,

    fetchActivityTypes,
  }
}
