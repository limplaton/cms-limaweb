
import { useApp } from '@/Core/composables/useApp'

export function useVoip() {
  const { scriptConfig } = useApp()

  const voip = Innoclapps.app.config.globalProperties.$voip

  const hasVoIPClient = scriptConfig('voip.client') !== null

  return { voip, hasVoIPClient }
}
