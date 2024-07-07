
import { computed } from 'vue'

import { useApp } from '@/Core/composables/useApp'
import { useTags } from '@/Core/composables/useTags'

export function useMessageTags() {
  const { findTagsByType } = useTags()
  const { scriptConfig } = useApp()

  const TAGS_TYPE = scriptConfig('mail.tags_type')

  async function syncTags(messageId, tags) {
    let { data: message } = await Innoclapps.request().post(
      `/emails/${messageId}/tags`,
      {
        tags,
      }
    )

    return message
  }

  const availableTags = computed(() => findTagsByType(TAGS_TYPE))

  return {
    syncTags,
    availableTags,
    TAGS_TYPE,
  }
}
