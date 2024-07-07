
import { useRoute, useRouter } from 'vue-router'

export function useFloatingResourceModal() {
  const router = useRouter()
  const route = useRoute()

  function floatResource({ resourceName, resourceId, mode }) {
    router.push({
      query: {
        ...route.query,
        floating_resource: resourceName,
        floating_resource_id: resourceId,
        mode: mode,
      },
    })
  }

  function floatResourceInDetailMode(config) {
    floatResource({ ...config, mode: 'detail' })
  }

  function floatResourceInEditMode(config) {
    floatResource({ ...config, mode: 'edit' })
  }

  return {
    floatResource,
    floatResourceInEditMode,
    floatResourceInDetailMode,
  }
}
