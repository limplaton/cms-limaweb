
import { toRef, watch } from 'vue'

/**
 * @type {import('vue').Ref<string|null>}
 */
const title = toRef(document?.title ?? null)

watch(title, newTitle => {
  if (newTitle) {
    document.title = newTitle
  }
})

/**
 * Composable for page title.
 *
 * @param {string|null} [newTitle=null] - The new title to set.
 * @returns {import('vue').Ref<string|null>}
 */
export function usePageTitle(newTitle = null) {
  /**
   * @type {import('vue').Ref<string|null>}
   */
  const t = toRef(newTitle)

  watch(
    t,
    v => {
      if (v) {
        title.value = v
      }
    },
    { immediate: true }
  )

  return title
}
