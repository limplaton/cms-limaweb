
import { computed } from 'vue'

import { useApp } from '@/Core/composables/useApp'

export function useSignature() {
  const { currentUser } = useApp()

  const signature = computed(() =>
    currentUser.value.mail_signature ? currentUser.value.mail_signature : ''
  )

  function addSignature(message = '') {
    return message + signature.value
  }

  return {
    addSignature,
    signature,
  }
}
